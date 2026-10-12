<?php
declare(strict_types=1);

/**
 * Closing Report: close rate per agent of one manager's team (HN and US),
 * same rules as the Umbrella CRM automation module.
 *
 *  - Team: active agents whose manager is CLOSING_REPORT_MANAGER (config.local.php).
 *  - Closed: the agent's sales (SIGNED or THREE_DAYS_WAIT) created in the range.
 *  - Left: the agent's opportunities reported in the range that never reached SIGNED.
 *  - Calls: inbound, outbound and talk time from closing_report_entries.
 *  - An agent is listed only if they have call entries in the range.
 *
 * Unlike the old app, every filter runs in Supabase (it used to download the
 * whole opportunities table) and the three data queries run in parallel.
 */

require_once __DIR__ . '/sales-data.php';

const CLOSING_REPORT_COUNTRIES = ['HN' => 'Honduras', 'US' => 'United States'];

/** Days of a range as YYYY-MM-DD strings. */
function range_days(string $from, string $to): array
{
    $days = [];
    for ($d = new DateTimeImmutable($from); $d->format('Y-m-d') <= $to; $d = $d->modify('+1 day')) {
        $days[] = $d->format('Y-m-d');
    }
    return $days;
}

/** Follows PostgREST paging (1,000 rows per page) when a result is cut off. */
function select_remaining_pages(string $path, array $firstPage, int $pageSize = 1000): array
{
    $rows = $firstPage;
    for ($offset = $pageSize; count($rows) === $offset; $offset += $pageSize) {
        $page = supabase_select_many(['page' => $path . '&limit=' . $pageSize . '&offset=' . $offset])['page'];
        if ($page === []) {
            break;
        }
        $rows = array_merge($rows, $page);
    }
    return $rows;
}

/**
 * @return array{ok: bool, error?: string, countries?: array<string, list<array{agent: string, closed: int,
 *   left: int, opportunities: int, inbound: int, outbound: int, calls: int, duration: int}>>}
 */
function closing_report(string $from, string $to): array
{
    if (!defined('CLOSING_REPORT_MANAGER') || CLOSING_REPORT_MANAGER === '') {
        return ['ok' => false, 'error' => 'The Closing Report is not configured.'];
    }

    // 1) The manager and the whole team in one request
    $managerRows = supabase_select_many(['team' => 'users?username=eq.' . rawurlencode(CLOSING_REPORT_MANAGER)
        . '&select=id,team:users!manager_id(id,names,surnames,country,status)&limit=1'])['team'];
    if ($managerRows === []) {
        return ['ok' => false, 'error' => 'The report manager was not found.'];
    }

    $agents = [];
    foreach ($managerRows[0]['team'] ?? [] as $u) {
        $country = strtoupper(trim((string) ($u['country'] ?? '')));
        if ((int) ($u['status'] ?? 0) === 1 && isset(CLOSING_REPORT_COUNTRIES[$country])) {
            $name = trim(($u['names'] ?? '') . ' ' . ($u['surnames'] ?? ''));
            $agents[(string) $u['id']] = ['name' => $name !== '' ? $name : 'No name', 'country' => $country];
        }
    }
    if ($agents === []) {
        return ['ok' => true, 'countries' => array_fill_keys(array_keys(CLOSING_REPORT_COUNTRIES), [])];
    }

    // 2) Closed sales, open opportunities and call entries, all at the same time
    $ids = implode(',', array_keys($agents));
    $reportedInRange = 'or(' . implode(',', array_map(fn ($d) => 'report_dates.cs.["' . $d . '"]', range_days($from, $to))) . ')';
    $paths = [
        'closed' => 'sales?select=assigned_to&assigned_to=in.(' . $ids . ')&status=in.(' . implode(',', SALE_STATUSES) . ')&' . created_at_range($from, $to),
        'left' => 'opportunities?select=sales_agent_id&sales_agent_id=in.(' . $ids . ')'
            . '&and=' . rawurlencode('(or(hour_presence.is.null,hour_presence.not.cs.["SIGNED"]),' . $reportedInRange . ')'),
        'entries' => 'closing_report_entries?select=agent_id,inbound,outbound,duration_seconds&agent_id=in.(' . $ids . ')'
            . '&work_date=gte.' . $from . '&work_date=lte.' . $to,
    ];
    $data = supabase_select_many(array_map(fn ($p) => $p . '&limit=1000', $paths));
    foreach ($paths as $key => $path) {
        $data[$key] = select_remaining_pages($path, $data[$key]);
    }

    $closed = array_count_values(array_filter(array_map(fn ($r) => (string) ($r['assigned_to'] ?? ''), $data['closed'])));
    $left = array_count_values(array_filter(array_map(fn ($r) => (string) ($r['sales_agent_id'] ?? ''), $data['left'])));
    $calls = [];
    foreach ($data['entries'] as $r) {
        $id = (string) ($r['agent_id'] ?? '');
        $calls[$id]['inbound'] = ($calls[$id]['inbound'] ?? 0) + (int) ($r['inbound'] ?? 0);
        $calls[$id]['outbound'] = ($calls[$id]['outbound'] ?? 0) + (int) ($r['outbound'] ?? 0);
        $calls[$id]['duration'] = ($calls[$id]['duration'] ?? 0) + (int) ($r['duration_seconds'] ?? 0);
    }

    $countries = array_fill_keys(array_keys(CLOSING_REPORT_COUNTRIES), []);
    foreach ($agents as $id => $agent) {
        if (!isset($calls[$id])) {
            continue; // listed only with call entries in the range
        }
        $c = (int) ($closed[$id] ?? 0);
        $l = (int) ($left[$id] ?? 0);
        $countries[$agent['country']][] = [
            'agent' => $agent['name'],
            'closed' => $c,
            'left' => $l,
            'opportunities' => $c + $l,
            'inbound' => $calls[$id]['inbound'],
            'outbound' => $calls[$id]['outbound'],
            'calls' => $calls[$id]['inbound'] + $calls[$id]['outbound'],
            'duration' => $calls[$id]['duration'],
        ];
    }
    foreach ($countries as &$rows) {
        usort($rows, fn ($a, $b) => strcasecmp($a['agent'], $b['agent']));
    }
    unset($rows);

    return ['ok' => true, 'countries' => $countries];
}

/** Percentage rounded to an integer (0 when there is nothing to divide). */
function percent(int $part, int $whole): int
{
    return $whole > 0 ? (int) round(100 * $part / $whole) : 0;
}

/** Color of a rate: 15%+ good, 10%+ fair, otherwise poor. */
function rate_tone(int $rate): string
{
    return $rate >= 15 ? 'green' : ($rate >= 10 ? 'amber' : 'red');
}

/** "01:23:45" */
function duration_label(int $seconds): string
{
    return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
}
