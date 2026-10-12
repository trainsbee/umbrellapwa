<?php
declare(strict_types=1);

/**
 * Sales data from the Umbrella CRM (table sales).
 * Only SIGNED and THREE_DAYS_WAIT count as a sale; SIGNING is a deal in
 * progress. California requires a 3-day wait by law, so THREE_DAYS_WAIT is
 * almost exclusively California clients.
 */

require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/format.php';

const SALE_STATUSES = ['SIGNED', 'THREE_DAYS_WAIT'];

/**
 * PostgREST filter for local days [$from, $to] (inclusive), converted to UTC.
 * created_at is stored in UTC; the business day runs on APP_TIMEZONE.
 */
function created_at_range(string $from, string $to): string
{
    $tz = new DateTimeZone(APP_TIMEZONE);
    $utc = new DateTimeZone('UTC');
    $start = (new DateTimeImmutable($from . ' 00:00:00', $tz))->setTimezone($utc);
    $end = (new DateTimeImmutable($to . ' 00:00:00', $tz))->modify('+1 day')->setTimezone($utc);
    return 'created_at=gte.' . rawurlencode($start->format('Y-m-d\TH:i:s\Z'))
        . '&created_at=lt.' . rawurlencode($end->format('Y-m-d\TH:i:s\Z'));
}

/** Query behind the day summary (one request; see sales_summary_from_rows). */
function sales_summary_path(string $date): string
{
    return 'sales?select=status,language,lead_source,processing:processing_id(program)'
        . '&' . created_at_range($date, $date)
        . '&status=in.(SIGNED,THREE_DAYS_WAIT,SIGNING)&limit=5000';
}

/**
 * Everything the dashboard needs about sales for one day.
 *
 * @return array{sales: int, spanish: int, english: int, signing: int, three_day_wait: int,
 *               signed: int, top_programs: array<string, int>, top_campaigns: array<string, int>}
 */
function sales_summary_from_rows(array $rows, int $top = 5): array
{

    $summary = ['sales' => 0, 'spanish' => 0, 'english' => 0, 'signing' => 0, 'three_day_wait' => 0, 'signed' => 0,
                'top_programs' => [], 'top_campaigns' => []];

    foreach ($rows as $row) {
        $status = (string) ($row['status'] ?? '');
        if ($status === 'SIGNING') {
            $summary['signing']++;
            continue;
        }
        $summary['sales']++;
        $status === 'THREE_DAYS_WAIT' ? $summary['three_day_wait']++ : $summary['signed']++;
        strtoupper((string) ($row['language'] ?? '')) === 'SPANISH' ? $summary['spanish']++ : $summary['english']++;

        $program = trim((string) ($row['processing']['program'] ?? ''));
        if ($program !== '') {
            $summary['top_programs'][$program] = ($summary['top_programs'][$program] ?? 0) + 1;
        }
        $campaign = trim((string) ($row['lead_source'] ?? ''));
        if ($campaign !== '') {
            $summary['top_campaigns'][$campaign] = ($summary['top_campaigns'][$campaign] ?? 0) + 1;
        }
    }

    arsort($summary['top_programs']);
    arsort($summary['top_campaigns']);
    $summary['top_programs'] = array_slice($summary['top_programs'], 0, $top, true);
    $summary['top_campaigns'] = array_slice($summary['top_campaigns'], 0, $top, true);
    return $summary;
}

/**
 * Sales in a date range. Without $status only real sales are listed.
 *
 * @return list<array{name: string, phone: string, debt: string, status: string, language: string,
 *                    campaign: string, program: string, created_at: string}>
 */
function sales_records(string $from, string $to, ?string $status = null, int $limit = 500): array
{
    $filter = $status !== null
        ? 'status=eq.' . rawurlencode($status)
        : 'status=in.(' . implode(',', SALE_STATUSES) . ')';

    $path = 'sales?select=client_full_name,debt,status,language,lead_source,created_at,processing:processing_id(phone,program)'
        . '&' . created_at_range($from, $to) . '&' . $filter
        . '&order=created_at.desc&limit=' . $limit;
    $rows = supabase_select_many(['rows' => $path], cache_ttl($to))['rows'];

    return array_map(fn (array $row): array => [
        'name' => (string) ($row['client_full_name'] ?? ''),
        'phone' => (string) ($row['processing']['phone'] ?? ''),
        'debt' => (string) ($row['debt'] ?? ''),
        'status' => (string) ($row['status'] ?? ''),
        'language' => (string) ($row['language'] ?? ''),
        'campaign' => (string) ($row['lead_source'] ?? ''),
        'program' => (string) ($row['processing']['program'] ?? ''),
        'created_at' => (string) ($row['created_at'] ?? ''),
    ], $rows);
}
