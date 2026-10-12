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

/**
 * Latest snapshot of a day.
 *
 * @return array{id: string, count: int, updated_at: string}|null
 */
function left_behind_snapshot(string $date): ?array
{
    $rows = supabase_select('opportunity_imports', 'registered_date=eq.' . $date
        . '&select=id,row_count,created_at&order=created_at.desc&limit=1');
    if ($rows === []) {
        return null;
    }
    return ['id' => (string) $rows[0]['id'], 'count' => (int) $rows[0]['row_count'], 'updated_at' => (string) $rows[0]['created_at']];
}

/** Average debt of the leads in a snapshot. */
function left_behind_average_debt(string $importId): float
{
    $sum = 0.0;
    $count = 0;
    foreach (supabase_select('opportunities', 'import_id=eq.' . rawurlencode($importId) . '&select=total_debt&limit=5000') as $row) {
        if (isset($row['total_debt']) && is_numeric($row['total_debt'])) {
            $sum += (float) $row['total_debt'];
            $count++;
        }
    }
    return $count > 0 ? $sum / $count : 0.0;
}

/**
 * Leads left behind in the latest snapshot of every day in [$from, $to].
 *
 * @return list<array{name: string, phone: string, debt: string, status: string, created_at: string}>
 */
function left_behind_records(string $from, string $to, int $limit = 500): array
{
    $latestPerDay = [];
    foreach (supabase_select('opportunity_imports', 'registered_date=gte.' . $from . '&registered_date=lte.' . $to
        . '&select=id,registered_date&order=registered_date.desc,created_at.desc') as $row) {
        $latestPerDay[$row['registered_date']] ??= (string) $row['id'];
    }
    if ($latestPerDay === []) {
        return [];
    }

    $rows = supabase_select('opportunities', 'select=phone,client_name,total_debt,status,created_at'
        . '&import_id=in.(' . implode(',', array_map('rawurlencode', $latestPerDay)) . ')'
        . '&order=created_at.desc&limit=' . $limit);

    return array_map(fn (array $row): array => [
        'name' => (string) ($row['client_name'] ?? ''),
        'phone' => (string) ($row['phone'] ?? ''),
        'debt' => isset($row['total_debt']) ? (string) $row['total_debt'] : '',
        'status' => (string) ($row['status'] ?? ''),
        'created_at' => (string) ($row['created_at'] ?? ''),
    ], $rows);
}
