<?php
declare(strict_types=1);

/**
 * Who can use the PWA. Admins (ADMIN / ROLE_ADMIN) always can, so the app
 * can never be left without an administrator; everyone else needs a row in
 * pwa_access, granted from the Users & access screen.
 */

require_once __DIR__ . '/supabase.php';

const ADMIN_ROLES = ['ADMIN', 'ROLE_ADMIN'];

/** Re-check access of an open session at most every 5 minutes. */
const ACCESS_RECHECK_SECONDS = 300;

function is_admin(array $user): bool
{
    return array_intersect(array_map('strtoupper', $user['roles'] ?? []), ADMIN_ROLES) !== [];
}

/** True when the user may use the app. Fails closed if the check cannot be made. */
function has_app_access(array $user): bool
{
    if (is_admin($user)) {
        return true;
    }
    $res = supabase_request('GET', 'pwa_access?select=user_id&user_id=eq.' . rawurlencode((string) $user['id']) . '&limit=1');
    return $res['status'] === 200 && is_array($res['data']) && $res['data'] !== [];
}

/** Gives a user access to the app (idempotent). */
function grant_app_access(string $userId, string $grantedBy): bool
{
    $res = supabase_request('POST', 'pwa_access?on_conflict=user_id', ['user_id' => $userId, 'granted_by' => $grantedBy],
        ['Prefer: resolution=ignore-duplicates,return=minimal']);
    return $res['status'] >= 200 && $res['status'] < 300;
}

/** Removes a user's access, their notification rules and their devices. */
function revoke_app_access(string $userId): bool
{
    $id = rawurlencode($userId);
    $ok = true;
    foreach (['pwa_access', 'pwa_notification_rules', 'pwa_push_subscriptions'] as $table) {
        $res = supabase_request('DELETE', $table . '?user_id=eq.' . $id, null, ['Prefer: return=minimal']);
        $ok = $ok && $res['status'] >= 200 && $res['status'] < 300;
    }
    return $ok;
}
