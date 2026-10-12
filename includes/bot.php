<?php
declare(strict_types=1);

/**
 * Data bot (Orbita browser extension). It listens to the single "state" row
 * of public.events in real time: when run becomes true it pulls the calls of
 * [target_date, target_date_end) from Infuse into closing_report_entries,
 * then sets run back to false. Same contract as the Umbrella CRM.
 */

require_once __DIR__ . '/auth.php';

/** Roles allowed to ask the bot for data (same as Umbrella's automation access). */
const BOT_ROLES = ['ADMIN', 'ROLE_ADMIN', 'AUTOMATION'];

function can_ask_bot(array $user): bool
{
    return array_intersect(array_map('strtoupper', $user['roles'] ?? []), BOT_ROLES) !== [];
}

/** @return array{run: bool, updated_at: string, target_date: string, target_date_end: string, last_error: string}|null */
function bot_status(): ?array
{
    $res = supabase_request('GET', 'events?select=run,updated_at,target_date,target_date_end,last_error&order=created_at.desc&limit=1');
    if ($res['status'] !== 200 || !is_array($res['data'])) {
        return null;
    }
    $row = $res['data'][0] ?? [];
    return [
        'run' => (bool) ($row['run'] ?? false),
        'updated_at' => (string) ($row['updated_at'] ?? ''),
        'target_date' => (string) ($row['target_date'] ?? ''),
        'target_date_end' => (string) ($row['target_date_end'] ?? ''),
        'last_error' => (string) ($row['last_error'] ?? ''),
    ];
}

/**
 * Asks the bot to fetch [$from, $to] (inclusive local days). Infuse takes a
 * half-open range, so the end date sent is the day after $to.
 */
function bot_request(string $from, string $to): bool
{
    $payload = [
        'run' => true,
        'target_date' => $from,
        'target_date_end' => (new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d'),
        'updated_at' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
    ];

    $latest = supabase_request('GET', 'events?select=id&order=created_at.desc&limit=1');
    $id = $latest['status'] === 200 ? ($latest['data'][0]['id'] ?? null) : null;

    $res = is_string($id) && $id !== ''
        ? supabase_request('PATCH', 'events?id=eq.' . rawurlencode($id), $payload, ['Prefer: return=minimal'])
        : supabase_request('POST', 'events', $payload, ['Prefer: return=minimal']);

    return $res['status'] >= 200 && $res['status'] < 300;
}
