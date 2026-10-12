<?php
declare(strict_types=1);

/** Umbrella users as seen by the Users & access screens. */

require_once __DIR__ . '/push.php';

/**
 * Every active Umbrella user with app access, admin flag, alert count and devices.
 *
 * @return list<array{id: string, name: string, username: string, admin: bool, access: bool, alerts: int, devices: int}>
 */
function app_users(): array
{
    $data = supabase_select_many([
        'users' => 'users?select=id,names,surnames,username&status=eq.1&order=names.asc&limit=2000',
        'access' => 'pwa_access?select=user_id&limit=5000',
        'admins' => 'user_roles?select=user_id,roles!inner(name)&roles.name=in.(' . implode(',', ADMIN_ROLES) . ')&limit=5000',
        'rules' => 'pwa_notification_rules?select=user_id&limit=10000',
        'devices' => 'pwa_push_subscriptions?select=user_id&limit=10000',
    ]);
    $ids = fn (array $rows) => array_map(fn ($r) => (string) $r['user_id'], $rows);
    $access = array_fill_keys($ids($data['access']), true);
    $admins = array_fill_keys($ids($data['admins']), true);
    $alerts = array_count_values($ids($data['rules']));
    $devices = array_count_values($ids($data['devices']));

    return array_map(function (array $u) use ($access, $admins, $alerts, $devices): array {
        $id = (string) $u['id'];
        $name = trim(($u['names'] ?? '') . ' ' . ($u['surnames'] ?? ''));
        return [
            'id' => $id,
            'name' => $name !== '' ? $name : (string) $u['username'],
            'username' => (string) ($u['username'] ?? ''),
            'admin' => isset($admins[$id]),
            'access' => isset($admins[$id]) || isset($access[$id]),
            'alerts' => $alerts[$id] ?? 0,
            'devices' => $devices[$id] ?? 0,
        ];
    }, $data['users']);
}

/** One active user, or null. */
function app_user(string $id): ?array
{
    foreach (app_users() as $u) {
        if ($u['id'] === $id) {
            return $u;
        }
    }
    return null;
}
