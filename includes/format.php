<?php
declare(strict_types=1);

/** Display helpers shared by the data pages. */

/** Validates a YYYY-MM-DD date. */
function is_valid_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

/** Date from the query string, or $default when missing or invalid. */
function date_param(string $name, string $default): string
{
    $value = (string) ($_GET[$name] ?? '');
    return is_valid_date($value) ? $value : $default;
}

function today(): string
{
    return (new DateTimeImmutable('now'))->format('Y-m-d');
}

/**
 * Cache lifetime for a range: today's numbers move, so 30 seconds; past days
 * barely change, so 5 minutes.
 */
function cache_ttl(string $to): int
{
    return $to >= today() ? 30 : 300;
}

/** "$12,450" (no cents). Empty input shows a dash. */
function money($value): string
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return '—';
    }
    return '$' . number_format((float) $value, 0);
}

/** "(504) 555-0123" for 10-digit numbers, otherwise as stored. */
function phone(string $value): string
{
    $digits = preg_replace('/\D+/', '', $value);
    return strlen($digits) === 10
        ? sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6))
        : $value;
}

/** Local time ("4:05 PM") of a UTC timestamp from Supabase. */
function local_time(?string $timestamp): string
{
    if ($timestamp === null || $timestamp === '') {
        return '';
    }
    return (new DateTimeImmutable($timestamp))->setTimezone(new DateTimeZone(APP_TIMEZONE))->format('g:i A');
}

/** "Oct 9" or "Oct 9 – Oct 11". */
function date_label(string $from, string $to): string
{
    $f = new DateTimeImmutable($from);
    $label = $f->format('M j');
    if ($to !== $from) {
        $label .= ' – ' . (new DateTimeImmutable($to))->format('M j');
    }
    return $label;
}

/** Human status label: THREE_DAYS_WAIT -> "3-day wait". */
function status_label(string $status): string
{
    $labels = ['SIGNED' => 'Signed', 'THREE_DAYS_WAIT' => '3-day wait', 'SIGNING' => 'Signing', 'PENDING' => 'Pending',
               'CANCELLED' => 'Cancelled', 'RETURNED' => 'Returned', 'REJECTED' => 'Rejected'];
    $key = strtoupper($status);
    return $labels[$key] ?? ucwords(strtolower(str_replace('_', ' ', $status)));
}

/** Color key for a status pill. */
function status_tone(string $status): string
{
    $tones = ['SIGNED' => 'green', 'THREE_DAYS_WAIT' => 'amber', 'SIGNING' => 'accent', 'CANCELLED' => 'red', 'REJECTED' => 'red'];
    return $tones[strtoupper($status)] ?? 'neutral';
}
