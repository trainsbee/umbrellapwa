<?php
declare(strict_types=1);

/**
 * Left behind leads from the Umbrella CRM.
 * opportunity_imports gets an hourly snapshot of the day (row_count = leads
 * still left behind at that moment); opportunities holds one row per phone
 * linked to the import that last saw it. The latest snapshot of a day is the
 * source of truth for that day.
 */

require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/format.php';

/** Query for the latest snapshot of a day, with its leads' debts embedded (one request). */
function left_behind_snapshot_path(string $date): string
{
    return 'opportunity_imports?registered_date=eq.' . $date
        . '&select=id,row_count,created_at,opportunities(total_debt)&order=created_at.desc&limit=1';
}

/**
 * Latest snapshot of a day and the average debt of its leads.
 *
 * @return array{id: string, count: int, updated_at: string, average_debt: float}|null
 */
function left_behind_snapshot_from_rows(array $rows): ?array
{
    if ($rows === []) {
        return null;
    }
    $debts = array_values(array_filter(
        array_map(fn ($o) => is_numeric($o['total_debt'] ?? null) ? (float) $o['total_debt'] : null, $rows[0]['opportunities'] ?? []),
        fn ($d) => $d !== null
    ));
    return [
        'id' => (string) $rows[0]['id'],
        'count' => (int) $rows[0]['row_count'],
        'updated_at' => (string) $rows[0]['created_at'],
        'average_debt' => $debts !== [] ? array_sum($debts) / count($debts) : 0.0,
    ];
}

/**
 * Leads left behind in the latest snapshot of every day in [$from, $to]
 * (one request: every import of the range with its leads embedded).
 *
 * @return list<array{name: string, phone: string, debt: string, status: string, created_at: string}>
 */
function left_behind_records(string $from, string $to): array
{
    $imports = supabase_select_many(['imports' => 'opportunity_imports?registered_date=gte.' . $from . '&registered_date=lte.' . $to
        . '&select=registered_date,created_at,opportunities(phone,client_name,total_debt,status,created_at)'
        . '&order=registered_date.desc,created_at.desc'], cache_ttl($to))['imports'];

    $records = [];
    $seenDays = [];
    foreach ($imports as $import) {
        // Ordered newest first, so the first import of each day is its latest snapshot
        if (isset($seenDays[$import['registered_date']])) {
            continue;
        }
        $seenDays[$import['registered_date']] = true;
        foreach ($import['opportunities'] ?? [] as $row) {
            $records[] = [
                'name' => (string) ($row['client_name'] ?? ''),
                'phone' => (string) ($row['phone'] ?? ''),
                'debt' => isset($row['total_debt']) ? (string) $row['total_debt'] : '',
                'status' => (string) ($row['status'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }
    }
    usort($records, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return $records;
}
