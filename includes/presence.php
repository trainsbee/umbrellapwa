<?php
declare(strict_types=1);

/**
 * Who is using the app.
 *
 *  - Last seen: when a signed-in user loads a page, the server stores the
 *    time in pwa_presence, at most once every PRESENCE_WRITE_SECONDS.
 *    It rides on page loads that already happen: the phone sends nothing extra.
 *  - Online now: while the app is on screen the phone joins a Supabase
 *    Realtime Presence channel (assets/js/pwa/presence.js). The channel
 *    only carries an opaque per-user token, never names or ids.
 *    Needs SUPABASE_ANON_KEY in config.local.php; without it only
 *    "last seen" works.
 */

require_once __DIR__ . '/supabase.php';

const PRESENCE_WRITE_SECONDS = 300;
const PRESENCE_CHANNEL = 'pwa-presence';

/** True when live presence (Realtime) is configured. */
function presence_live_enabled(): bool
{
    return defined('SUPABASE_ANON_KEY') && SUPABASE_ANON_KEY !== '';
}

/** Opaque token that stands for a user on the Realtime channel. */
function presence_token(string $userId): string
{
    return substr(hash_hmac('sha256', 'presence|' . $userId, SUPABASE_KEY), 0, 24);
}

/** "iPhone", "Android", "Mac", "Windows" or "Other" from the User-Agent. */
function presence_device(string $userAgent): string
{
    return match (true) {
        (bool) preg_match('/iPhone|iPad|iPod/i', $userAgent) => 'iPhone',
        (bool) preg_match('/Android/i', $userAgent) => 'Android',
        (bool) preg_match('/Macintosh|Mac OS X/i', $userAgent) => 'Mac',
        (bool) preg_match('/Windows/i', $userAgent) => 'Windows',
        default => 'Other',
    };
}

/** Stores "last seen" for the signed-in user (throttled through the session). */
function presence_touch(array $user): void
{
    $now = time();
    if ($now - (int) ($_SESSION['presence_at'] ?? 0) < PRESENCE_WRITE_SECONDS) {
        return;
    }
    $_SESSION['presence_at'] = $now;

    supabase_request('POST', 'pwa_presence?on_conflict=user_id', [
        'user_id' => $user['id'],
        'last_seen_at' => gmdate('c', $now),
        'device' => presence_device((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')),
    ], ['Prefer: resolution=merge-duplicates,return=minimal']);
}

/**
 * Settings the page hands to presence.js, or null when live presence is off.
 *
 * @return array{url: string, key: string, channel: string, token: string}|null
 */
function presence_client_config(array $user): ?array
{
    if (!presence_live_enabled()) {
        return null;
    }
    return [
        'url' => rtrim(SUPABASE_URL, '/'),
        'key' => SUPABASE_ANON_KEY,
        'channel' => PRESENCE_CHANNEL,
        'token' => presence_token((string) $user['id']),
    ];
}

/** user_id => ['last_seen_at' => string, 'device' => ?string] */
function presence_last_seen(): array
{
    $out = [];
    foreach (supabase_select('pwa_presence', 'select=user_id,last_seen_at,device&limit=5000') as $r) {
        $out[(string) $r['user_id']] = ['last_seen_at' => (string) $r['last_seen_at'], 'device' => $r['device'] ?? null];
    }
    return $out;
}

/** "Just now", "12 min ago", "Today 3:15 PM", "Yesterday 9:02 AM", "Oct 3". */
function presence_ago(?string $iso): string
{
    if ($iso === null || $iso === '') {
        return 'Never';
    }
    $t = strtotime($iso);
    $diff = time() - $t;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return intdiv($diff, 60) . ' min ago';
    }
    if (date('Y-m-d', $t) === date('Y-m-d')) {
        return 'Today ' . date('g:i A', $t);
    }
    if (date('Y-m-d', $t) === date('Y-m-d', strtotime('-1 day'))) {
        return 'Yesterday ' . date('g:i A', $t);
    }
    return date('M j', $t);
}
