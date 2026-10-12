<?php
declare(strict_types=1);

/**
 * Push notifications (standard Web Push with VAPID).
 *
 * Flow: a trigger in Supabase posts {event, id} to api/push-event.php when a
 * processing is created, a client starts signing or signs, or compliance is
 * completed. This file loads that record, works out who should be told
 * (pwa_notification_rules: event, scope, muted; users must have app access)
 * and sends the notification to every device they subscribed.
 *
 * Notifications never include the client's name or phone: they show on the
 * lock screen.
 */

require_once __DIR__ . '/access.php';
require_once __DIR__ . '/format.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/** Event => [label, description, Feather icon]. */
const PUSH_EVENTS = [
    'processing.new' => ['New processing', 'A new lead enters Processing', 'phone-call'],
    'sales.signing' => ['Client signing', 'A client starts signing the contract', 'edit-3'],
    'sales.signed' => ['Client signed', 'A client signs (includes California 3-day wait)', 'check-circle'],
    'compliance.completed' => ['Compliance completed', 'A sale passes compliance', 'shield'],
];

/** Scope => label. */
const PUSH_SCOPES = [
    'all' => 'Whole operation',
    'team' => 'Their team only',
    'own' => 'Their own clients only',
];

function push_configured(): bool
{
    return defined('VAPID_PUBLIC_KEY') && defined('VAPID_PRIVATE_KEY') && VAPID_PUBLIC_KEY !== '' && VAPID_PRIVATE_KEY !== '';
}

/* ---------------------------------------------------------------- rules */

/** @return array<string, array{scope: string, muted: bool}> event => rule */
function push_rules_for(string $userId): array
{
    $rules = [];
    foreach (supabase_select('pwa_notification_rules', 'select=event,scope,muted&user_id=eq.' . rawurlencode($userId)) as $r) {
        $rules[(string) $r['event']] = ['scope' => (string) $r['scope'], 'muted' => (bool) $r['muted']];
    }
    return $rules;
}

/**
 * Replaces the events an admin enabled for a user. Keeps the user's own
 * "muted" choice for events that stay enabled.
 *
 * @param array<string, string> $enabled event => scope
 */
function push_save_rules(string $userId, array $enabled): bool
{
    $id = rawurlencode($userId);
    $current = push_rules_for($userId);
    $removed = array_diff(array_keys($current), array_keys($enabled));
    if ($removed !== []) {
        supabase_request('DELETE', 'pwa_notification_rules?user_id=eq.' . $id . '&event=in.(' . implode(',', $removed) . ')', null, ['Prefer: return=minimal']);
    }
    if ($enabled === []) {
        return true;
    }
    $rows = [];
    foreach ($enabled as $event => $scope) {
        $rows[] = ['user_id' => $userId, 'event' => $event, 'scope' => $scope,
                   'muted' => $current[$event]['muted'] ?? false, 'updated_at' => gmdate('c')];
    }
    $res = supabase_request('POST', 'pwa_notification_rules?on_conflict=user_id,event', $rows,
        ['Prefer: resolution=merge-duplicates,return=minimal']);
    return $res['status'] >= 200 && $res['status'] < 300;
}

/** The user's own mute switch for an event an admin enabled. */
function push_set_muted(string $userId, string $event, bool $muted): bool
{
    $res = supabase_request('PATCH', 'pwa_notification_rules?user_id=eq.' . rawurlencode($userId) . '&event=eq.' . rawurlencode($event),
        ['muted' => $muted, 'updated_at' => gmdate('c')], ['Prefer: return=minimal']);
    return $res['status'] >= 200 && $res['status'] < 300;
}

/* --------------------------------------------------------- subscriptions */

function push_save_subscription(string $userId, array $subscription, string $userAgent): bool
{
    $endpoint = (string) ($subscription['endpoint'] ?? '');
    $keys = $subscription['keys'] ?? [];
    if (!preg_match('#^https://#', $endpoint) || empty($keys['p256dh']) || empty($keys['auth'])) {
        return false;
    }
    $res = supabase_request('POST', 'pwa_push_subscriptions?on_conflict=endpoint', [
        'user_id' => $userId,
        'endpoint' => $endpoint,
        'p256dh' => (string) $keys['p256dh'],
        'auth' => (string) $keys['auth'],
        'user_agent' => mb_substr($userAgent, 0, 300),
    ], ['Prefer: resolution=merge-duplicates,return=minimal']);
    return $res['status'] >= 200 && $res['status'] < 300;
}

function push_delete_subscription(string $userId, string $endpoint): bool
{
    $res = supabase_request('DELETE', 'pwa_push_subscriptions?user_id=eq.' . rawurlencode($userId) . '&endpoint=eq.' . rawurlencode($endpoint),
        null, ['Prefer: return=minimal']);
    return $res['status'] >= 200 && $res['status'] < 300;
}

/** Number of devices subscribed per user. @return array<string, int> */
function push_device_counts(array $userIds = []): array
{
    $query = 'select=user_id';
    if ($userIds !== []) {
        $query .= '&user_id=in.(' . implode(',', array_map('rawurlencode', $userIds)) . ')';
    }
    return array_count_values(array_map(fn ($r) => (string) $r['user_id'], supabase_select('pwa_push_subscriptions', $query)));
}

/* ---------------------------------------------------------------- send */

/**
 * Sends one notification to every device of the given users.
 * Expired devices (404 / 410 from the push service) are removed.
 *
 * @param list<string> $userIds
 * @param array{title: string, body: string, url: string, tag?: string} $message
 * @return array{devices: int, sent: int, removed: int}
 */
function push_send(array $userIds, array $message): array
{
    $result = ['devices' => 0, 'sent' => 0, 'removed' => 0];
    if ($userIds === [] || !push_configured()) {
        return $result;
    }

    $subscriptions = supabase_select('pwa_push_subscriptions', 'select=id,endpoint,p256dh,auth&user_id=in.('
        . implode(',', array_map('rawurlencode', array_unique($userIds))) . ')');
    $result['devices'] = count($subscriptions);
    if ($subscriptions === []) {
        return $result;
    }

    $webPush = new WebPush(['VAPID' => [
        'subject' => VAPID_SUBJECT,
        'publicKey' => VAPID_PUBLIC_KEY,
        'privateKey' => VAPID_PRIVATE_KEY,
    ]], ['TTL' => 3600, 'urgency' => 'high', 'topic' => substr(preg_replace('/[^A-Za-z0-9_-]/', '', $message['tag'] ?? 'umbrella'), 0, 32)]);

    $payload = json_encode($message);
    $ids = [];
    foreach ($subscriptions as $s) {
        $webPush->queueNotification(Subscription::create([
            'endpoint' => $s['endpoint'],
            'publicKey' => $s['p256dh'],
            'authToken' => $s['auth'],
            'contentEncoding' => 'aes128gcm',
        ]), $payload);
        $ids[$s['endpoint']] = $s['id'];
    }

    $sentIds = [];
    $expiredIds = [];
    foreach ($webPush->flush() as $report) {
        $id = $ids[$report->getEndpoint()] ?? null;
        if ($report->isSuccess()) {
            $sentIds[] = $id;
        } elseif ($report->isSubscriptionExpired()) {
            $expiredIds[] = $id;
        }
        if (!$report->isSuccess()) {
            // Leaves a trace of why a device stopped receiving (no payload, no keys)
            $status = $report->getResponse()?->getStatusCode() ?? 0;
            error_log('push failed: ' . parse_url($report->getEndpoint(), PHP_URL_HOST) . ' status ' . $status . ' ' . $report->getReason());
        }
    }

    if ($expiredIds !== []) {
        supabase_request('DELETE', 'pwa_push_subscriptions?id=in.(' . implode(',', $expiredIds) . ')', null, ['Prefer: return=minimal']);
    }
    if ($sentIds !== []) {
        supabase_request('PATCH', 'pwa_push_subscriptions?id=in.(' . implode(',', $sentIds) . ')', ['last_used_at' => gmdate('c')], ['Prefer: return=minimal']);
    }
    $result['sent'] = count($sentIds);
    $result['removed'] = count($expiredIds);
    return $result;
}

/* ------------------------------------------------------------- events */

/** First name and last initial: "Lorena N." */
function push_agent_label(?array $user): string
{
    if ($user === null) {
        return '';
    }
    $first = explode(' ', trim((string) ($user['names'] ?? '')))[0] ?? '';
    $last = trim((string) ($user['surnames'] ?? ''));
    return trim($first . ($last !== '' ? ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.' : ''));
}

/** First element of an embed (object or list). */
function push_embed($embed): ?array
{
    if (is_array($embed) && isset($embed[0]) && is_array($embed[0])) {
        return $embed[0];
    }
    return is_array($embed) && $embed !== [] ? $embed : null;
}

/**
 * Loads the record behind an event and builds the message plus who owns it.
 *
 * @return array{message: array, owner: ?string, manager: ?string}|null
 */
function push_build_event(string $event, string $id): ?array
{
    $id = rawurlencode($id);
    $agent = 'names,surnames,manager_id';
    $today = today();

    if ($event === 'processing.new') {
        $row = supabase_select('processing', "select=id,program,created_by,creator:created_by($agent)&id=eq.$id&limit=1")[0] ?? null;
        if ($row === null) {
            return null;
        }
        $creator = push_embed($row['creator'] ?? null);
        return [
            'owner' => $row['created_by'] ?? null,
            'manager' => $creator['manager_id'] ?? null,
            'message' => [
                'title' => 'New processing',
                'body' => implode(' · ', array_filter([$row['program'] ?? '', ($l = push_agent_label($creator)) !== '' ? 'by ' . $l : ''])),
                'url' => 'home.php',
                'tag' => 'processing-' . $row['id'],
            ],
        ];
    }

    if ($event === 'sales.signing' || $event === 'sales.signed') {
        $row = supabase_select('sales', "select=id,status,debt,assigned_to,sales_manager_id,agent:assigned_to($agent),processing(program)&id=eq.$id&limit=1")[0] ?? null;
    } elseif ($event === 'compliance.completed') {
        $compliance = supabase_select('compliance', "select=id,sale:sales_id(id,status,debt,assigned_to,sales_manager_id,agent:assigned_to($agent),processing(program))&id=eq.$id&limit=1")[0] ?? null;
        $row = push_embed($compliance['sale'] ?? null);
    } else {
        return null;
    }
    if ($row === null) {
        return null;
    }

    $program = (string) (push_embed($row['processing'] ?? null)['program'] ?? '');
    $agentLabel = push_agent_label(push_embed($row['agent'] ?? null));
    $titles = [
        'sales.signing' => 'Client signing',
        'sales.signed' => ($row['status'] ?? '') === 'THREE_DAYS_WAIT' ? 'Client signed · 3-day wait' : 'Client signed',
        'compliance.completed' => 'Compliance completed',
    ];
    $urls = [
        'sales.signing' => 'sales.php?from=' . $today . '&to=' . $today . '&status=SIGNING',
        'sales.signed' => 'sales.php?from=' . $today . '&to=' . $today,
        'compliance.completed' => 'sales.php?from=' . $today . '&to=' . $today,
    ];

    return [
        'owner' => $row['assigned_to'] ?? null,
        'manager' => $row['sales_manager_id'] ?? (push_embed($row['agent'] ?? null)['manager_id'] ?? null),
        'message' => [
            'title' => $titles[$event],
            'body' => implode(' · ', array_filter([$program, money($row['debt'] ?? null) !== '—' ? money($row['debt']) : '', $agentLabel])),
            'url' => $urls[$event],
            'tag' => str_replace('.', '-', $event) . '-' . $row['id'],
        ],
    ];
}

/**
 * Users who should get an event: an enabled, unmuted rule whose scope
 * matches, and access to the app (or an admin role).
 *
 * @return list<string>
 */
function push_recipients(string $event, ?string $owner, ?string $manager): array
{
    $rules = supabase_select('pwa_notification_rules', 'select=user_id,scope&muted=is.false&event=eq.' . rawurlencode($event));
    $candidates = [];
    foreach ($rules as $r) {
        $userId = (string) $r['user_id'];
        $match = match ((string) $r['scope']) {
            'all' => true,
            'team' => $manager !== null && $userId === (string) $manager,
            'own' => $owner !== null && $userId === (string) $owner,
            default => false,
        };
        if ($match) {
            $candidates[] = $userId;
        }
    }
    if ($candidates === []) {
        return [];
    }

    $in = 'in.(' . implode(',', array_map('rawurlencode', $candidates)) . ')';
    $data = supabase_select_many([
        'access' => 'pwa_access?select=user_id&user_id=' . $in,
        'admins' => 'user_roles?select=user_id,roles!inner(name)&roles.name=in.(' . implode(',', ADMIN_ROLES) . ')&user_id=' . $in,
    ]);
    $allowed = array_fill_keys(array_map(fn ($r) => (string) $r['user_id'], array_merge($data['access'], $data['admins'])), true);
    return array_values(array_filter($candidates, fn ($id) => isset($allowed[$id])));
}
