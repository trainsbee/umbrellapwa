<?php
declare(strict_types=1);

/**
 * Leader Board: same rules as the Umbrella CRM (SalesLeaderboardService).
 *
 *  - Sales: SIGNED and THREE_DAYS_WAIT created in the range (business time zone).
 *  - "OTHER LEADS" (lead_source) is split off first; every other sale goes to a
 *    segment: payday (program contains PAYDAY), english, spanish_hn (Spanish +
 *    country HN), spanish_us (other Spanish), or other.
 *  - Opportunities: leads reported in the range that never reached SIGNED,
 *    counted per agent in the same segments.
 *  - % = deals / (deals + opportunities), for every agent and for the board total.
 *    (Umbrella's board footer divides deals by opportunities, which can exceed
 *    100%; here the footer uses the same formula as the rows.)
 *
 * Unlike the original, everything comes from one round of parallel queries
 * with names embedded (Umbrella made up to ~15 sequential requests and read
 * the sales three times).
 */

require_once __DIR__ . '/closing-report-data.php';

// Embeds use the column form (alias:column(fields)) to get the referenced row:
// on the self-referencing users table, users!manager_id would return the
// subordinates instead of the manager.
const LEADERBOARD_ROLE = 'LEADERBOARD';
const OTHER_LEADS_SOURCE = 'OTHER LEADS';
const NO_AGENT_KEY = '__no_agent__';
const NO_MANAGER_KEY = '__no_manager__';

/** Boards in display order: segment => title. */
const LEADERBOARD_BOARDS = [
    'spanish_us' => 'Top Spanish US',
    'spanish_hn' => 'Top Spanish HN',
    'english' => 'Top English',
    'other_leads' => 'Top Other Leads',
];

const SEGMENT_LABELS = [
    'spanish_us' => 'Spanish US', 'spanish_hn' => 'Spanish HN', 'english' => 'English',
    'payday' => 'Payday', 'other' => 'Other', 'other_leads' => 'Other Leads',
];

function can_view_leaderboard(array $user): bool
{
    return has_any_role($user, [LEADERBOARD_ROLE]);
}

/** "$1,234" or "$1,234.56" -> float. Anything invalid or negative is 0. */
function debt_to_float($value): float
{
    $clean = preg_replace('/[^0-9.]/', '', str_replace(['$', ',', ' '], '', (string) $value));
    if ($clean === '' || $clean === '.' || !is_numeric($clean)) {
        return 0.0;
    }
    $n = (float) $clean;
    return is_finite($n) && $n > 0 ? $n : 0.0;
}

/** Segment of a sale or an opportunity (other leads are handled before this). */
function sale_segment(string $program, string $language, string $country): string
{
    if (str_contains(strtoupper($program), 'PAYDAY')) {
        return 'payday';
    }
    $language = strtoupper(trim($language));
    if ($language === 'ENGLISH') {
        return 'english';
    }
    if ($language === 'SPANISH') {
        return strtoupper(trim($country)) === 'HN' ? 'spanish_hn' : 'spanish_us';
    }
    return 'other';
}

function is_other_leads(?string $leadSource): bool
{
    return strtoupper(trim((string) $leadSource)) === OTHER_LEADS_SOURCE;
}

/** "Names Surnames", else username, else $fallback. */
function person_name(?array $user, string $fallback = ''): string
{
    $name = trim(($user['names'] ?? '') . ' ' . ($user['surnames'] ?? ''));
    return $name !== '' ? $name : ((string) ($user['username'] ?? '') !== '' ? (string) $user['username'] : $fallback);
}

/** "First L." for TO tags. */
function short_name(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    if (count($parts) < 2) {
        return $name;
    }
    return $parts[0] . ' ' . mb_strtoupper(mb_substr($parts[1], 0, 1)) . '.';
}

/** First element of a PostgREST embed (it can come as an object or a list). */
function embed_first($embed): ?array
{
    if (is_array($embed) && isset($embed[0]) && is_array($embed[0])) {
        return $embed[0];
    }
    return is_array($embed) && $embed !== [] ? $embed : null;
}

function passed_compliance($compliance): bool
{
    return (embed_first($compliance)['compliance_status'] ?? null) === 'COMPLETED';
}

/**
 * Everything the Leader Board shows for [$from, $to].
 *
 * @return array{boards: array, totals: array, top_programs: array, top_campaigns: array,
 *               signed: list<array>, no_sales_today: array}
 */
function leaderboard(string $from, string $to): array
{
    $today = today();
    $saleStatuses = 'status=in.(' . implode(',', SALE_STATUSES) . ')';
    $person = 'names,surnames,username';
    $reported = 'or(' . implode(',', array_map(fn ($d) => 'report_dates.cs.["' . $d . '"]', range_days($from, $to))) . ')';

    $paths = [
        'sales' => 'sales?select=id,client_full_name,assigned_to,sales_manager_id,to_id,debt,created_at,language,country,lead_source,status,'
            . "agent:assigned_to($person),manager:sales_manager_id($person),to_user:to_id($person),"
            . 'processing(phone,program),compliance(compliance_status)'
            . '&' . $saleStatuses . '&' . created_at_range($from, $to) . '&order=created_at.desc,id.desc',
        'opportunities' => "opportunities?select=sales_agent_id,language,country,program,lead_source,agent:sales_agent_id($person)"
            . '&sales_agent_id=not.is.null&order=sales_agent_id'
            . '&and=' . rawurlencode('(or(hour_presence.is.null,hour_presence.not.cs.["SIGNED"]),' . $reported . ')'),
        'salesTeam' => "user_roles?select=roles!inner(name),users(id,$person,manager_id,status,manager:manager_id($person))"
            . '&roles.name=eq.SALES&order=user_id',
    ];
    // Today's sellers: derived from the range when it is today, otherwise one more parallel query
    if ($from !== $today || $to !== $today) {
        $paths['todaySales'] = 'sales?select=assigned_to&' . $saleStatuses . '&' . created_at_range($today, $today);
    }

    $data = supabase_select_many(array_map(fn ($p) => $p . '&limit=1000', $paths));
    foreach ($paths as $key => $path) {
        $data[$key] = select_remaining_pages($path, $data[$key]);
    }

    // ---------- Sales by segment and agent ----------
    $agents = [];   // segment => agentKey => [deals, debt, to_ids]
    $names = [];    // agentKey => display name
    $totals = ['sales' => 0, 'debt' => 0.0, 'primary_sales' => 0, 'primary_debt' => 0.0, 'other_leads_sales' => 0,
               'other_leads_debt' => 0.0, 'classified' => 0, 'other_sales' => 0, 'incomplete_compliance' => 0];
    $programs = [];
    $campaigns = [];
    $signed = [];
    $sellers = [];

    foreach ($data['sales'] as $row) {
        $debt = debt_to_float($row['debt'] ?? null);
        $agentKey = (string) ($row['assigned_to'] ?? '') !== '' ? (string) $row['assigned_to'] : NO_AGENT_KEY;
        $agentName = $agentKey === NO_AGENT_KEY ? 'Unassigned' : person_name(embed_first($row['agent'] ?? null), $agentKey);
        $names[$agentKey] = $agentName;
        $sellers[$agentKey] = true;
        $processing = embed_first($row['processing'] ?? null) ?? [];
        $program = trim((string) ($processing['program'] ?? ''));
        $passed = passed_compliance($row['compliance'] ?? null);
        $otherLeads = is_other_leads($row['lead_source'] ?? null);
        $segment = $otherLeads ? 'other_leads' : sale_segment($program, (string) ($row['language'] ?? ''), (string) ($row['country'] ?? ''));

        $totals['sales']++;
        $totals['debt'] += $debt;
        $passed || $totals['incomplete_compliance']++;
        if ($otherLeads) {
            $totals['other_leads_sales']++;
            $totals['other_leads_debt'] += $debt;
        } else {
            $totals['primary_sales']++;
            $totals['primary_debt'] += $debt;
            $segment === 'other' ? $totals['other_sales']++ : $totals['classified']++;
        }

        $a = &$agents[$segment][$agentKey];
        $a['deals'] = ($a['deals'] ?? 0) + 1;
        $a['debt'] = ($a['debt'] ?? 0.0) + $debt;
        $toName = (string) ($row['to_id'] ?? '') !== '' ? person_name(embed_first($row['to_user'] ?? null), '') : '';
        if (!$otherLeads && $toName !== '') {
            $a['to'][$toName] = ($a['to'][$toName] ?? 0) + 1;
        }
        unset($a);

        $programLabel = $program !== '' ? $program : '(no program)';
        $campaignLabel = trim((string) ($row['lead_source'] ?? '')) ?: '(no campaign)';
        $programs[$programLabel]['sales'] = ($programs[$programLabel]['sales'] ?? 0) + 1;
        $programs[$programLabel]['incomplete'] = ($programs[$programLabel]['incomplete'] ?? 0) + ($passed ? 0 : 1);
        $campaigns[$campaignLabel]['sales'] = ($campaigns[$campaignLabel]['sales'] ?? 0) + 1;
        $campaigns[$campaignLabel]['incomplete'] = ($campaigns[$campaignLabel]['incomplete'] ?? 0) + ($passed ? 0 : 1);

        $signed[] = [
            'created_at' => (string) ($row['created_at'] ?? ''),
            'phone' => (string) ($processing['phone'] ?? ''),
            'client' => (string) ($row['client_full_name'] ?? ''),
            'program' => $program,
            'campaign' => (string) ($row['lead_source'] ?? ''),
            'agent' => $agentName,
            'manager' => (string) ($row['sales_manager_id'] ?? '') !== '' ? person_name(embed_first($row['manager'] ?? null), 'No manager') : 'No manager',
            'to' => $toName,
            'status' => (string) ($row['status'] ?? ''),
            'debt' => $debt,
            'segment' => SEGMENT_LABELS[$segment],
        ];
    }

    // ---------- Opportunities by segment and agent ----------
    $opportunities = [];  // segment => agentKey => count
    $opportunityTotal = 0;
    foreach ($data['opportunities'] as $row) {
        $agentKey = (string) ($row['sales_agent_id'] ?? '');
        if (!preg_match('/^[a-f0-9-]{36}$/', $agentKey)) {
            continue;
        }
        $segment = is_other_leads($row['lead_source'] ?? null)
            ? 'other_leads'
            : sale_segment((string) ($row['program'] ?? ''), (string) ($row['language'] ?? ''), (string) ($row['country'] ?? ''));
        $opportunities[$segment][$agentKey] = ($opportunities[$segment][$agentKey] ?? 0) + 1;
        $names[$agentKey] ??= person_name(embed_first($row['agent'] ?? null), $agentKey);
        $opportunityTotal++;
    }
    $opportunitiesWithSales = 0;
    foreach ($opportunities as $bySegment) {
        foreach ($bySegment as $agentKey => $count) {
            if (isset($sellers[$agentKey])) {
                $opportunitiesWithSales += $count;
            }
        }
    }

    // ---------- Boards ----------
    $boards = [];
    foreach (LEADERBOARD_BOARDS as $segment => $title) {
        $rows = [];
        foreach (array_unique(array_merge(array_keys($agents[$segment] ?? []), array_keys($opportunities[$segment] ?? []))) as $agentKey) {
            $deals = $agents[$segment][$agentKey]['deals'] ?? 0;
            $opps = $opportunities[$segment][$agentKey] ?? 0;
            $toCounts = $agents[$segment][$agentKey]['to'] ?? [];
            arsort($toCounts);
            $rows[] = [
                'agent' => $names[$agentKey] ?? $agentKey,
                'deals' => $deals,
                'debt' => round($agents[$segment][$agentKey]['debt'] ?? 0.0, 2),
                'opportunities' => $opps,
                'percent' => $deals + $opps > 0 ? round(100 * $deals / ($deals + $opps), 2) : 0.0,
                'to' => $toCounts,
            ];
        }
        if ($rows === []) {
            continue; // a board shows only with at least one agent
        }
        usort($rows, fn ($a, $b) => [$b['deals'], $b['debt']] <=> [$a['deals'], $a['debt']]);
        $deals = array_sum(array_column($rows, 'deals'));
        $opps = array_sum(array_column($rows, 'opportunities'));
        $boards[$segment] = [
            'title' => $title,
            'rows' => $rows,
            'deals' => $deals,
            'debt' => round(array_sum(array_column($rows, 'debt')), 2),
            'opportunities' => $opps,
            'percent' => $deals + $opps > 0 ? round(100 * $deals / ($deals + $opps), 2) : 0.0,
        ];
    }

    $totals['opportunities'] = $opportunityTotal;
    $totals['opportunities_with_sales'] = $opportunitiesWithSales;
    $totals['opportunities_no_sales'] = max(0, $opportunityTotal - $opportunitiesWithSales);
    $totals['pipeline'] = $totals['sales'] + $opportunityTotal;
    $totals['average_debt'] = $totals['sales'] > 0 ? round($totals['debt'] / $totals['sales'], 2) : 0.0;

    $byCount = fn ($a, $b) => $b['sales'] <=> $a['sales'];
    uasort($programs, $byCount);
    uasort($campaigns, $byCount);

    // ---------- Agents without sales today ----------
    $todaySellers = isset($data['todaySales'])
        ? array_fill_keys(array_filter(array_map(fn ($r) => (string) ($r['assigned_to'] ?? ''), $data['todaySales'])), true)
        : $sellers;
    $groups = [];
    $combined = defined('LEADERBOARD_COMBINED_MANAGERS') ? array_map('strtolower', LEADERBOARD_COMBINED_MANAGERS) : [];
    foreach ($data['salesTeam'] as $link) {
        $u = embed_first($link['users'] ?? null);
        if ($u === null || (int) ($u['status'] ?? 0) !== 1 || isset($todaySellers[(string) $u['id']])) {
            continue;
        }
        $manager = embed_first($u['manager'] ?? null);
        $managerKey = (string) ($u['manager_id'] ?? '') !== '' ? (string) $u['manager_id'] : NO_MANAGER_KEY;
        $managerName = $managerKey === NO_MANAGER_KEY ? 'No manager' : person_name($manager, 'No manager');
        if ($manager !== null && in_array(strtolower((string) ($manager['username'] ?? '')), $combined, true)) {
            $managerKey = '__combined__';
            $managerName = LEADERBOARD_COMBINED_LABEL;
        }
        $id = (string) $u['id'];
        $deals = 0;
        $debt = 0.0;
        foreach ($agents as $bySegment) {
            $deals += $bySegment[$id]['deals'] ?? 0;
            $debt += $bySegment[$id]['debt'] ?? 0.0;
        }
        $groups[$managerKey]['manager'] = $managerName;
        $groups[$managerKey]['agents'][] = ['name' => person_name($u, $id), 'deals' => $deals, 'debt' => $debt];
    }
    foreach ($groups as &$group) {
        usort($group['agents'], fn ($a, $b) => [$b['deals'], $b['debt'], $a['name']] <=> [$a['deals'], $a['debt'], $b['name']]);
    }
    unset($group);
    uasort($groups, function ($a, $b) {
        if (($a['manager'] === 'No manager') !== ($b['manager'] === 'No manager')) {
            return $a['manager'] === 'No manager' ? 1 : -1;
        }
        return strcasecmp($a['manager'], $b['manager']);
    });

    return [
        'boards' => $boards,
        'totals' => $totals,
        'top_programs' => array_slice($programs, 0, 5, true),
        'top_campaigns' => array_slice($campaigns, 0, 5, true),
        'signed' => $signed,
        'no_sales_today' => array_values($groups),
    ];
}

/** "$1,234" for whole amounts, "$1,234.56" otherwise. */
function money_exact(float $value): string
{
    return '$' . number_format($value, floor($value) == $value ? 0 : 2);
}

function initials_of(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
}
