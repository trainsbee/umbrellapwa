<?php
declare(strict_types=1);

/**
 * Push notifications (standard Web Push with VAPID).
 *
 * Flow: a trigger in Supabase posts {event, id} to api/push-event.php when a
 * lead enters Processing, a client starts signing or signs, or compliance
 * starts, passes or cancels the verification. This file loads that client,
 * works out who should be told (pwa_notification_rules: event, scope, muted;
 * users must have app access) and sends the notification to every device
 * they subscribed. Tapping it opens the client's profile (client.php).
 */

require_once __DIR__ . '/access.php';
require_once __DIR__ . '/format.php';
require_once __DIR__ . '/client-data.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/** Pipeline stages the alerts are grouped by. */
const PUSH_STAGES = [
    'processing' => 'Processing',
    'sales' => 'Sales',
    'compliance' => 'Compliance',
];

/** Event => [label, description, Feather icon, stage], in pipeline order. */
const PUSH_EVENTS = [
    'processing.new' => ['Potential client added', 'A new lead enters Processing', 'user-plus', 'processing'],
    'sales.signing' => ['Client signing', 'The client starts signing the contract', 'edit-3', 'sales'],
    'sales.signed' => ['Signature completed', 'The client signs (includes California 3-day wait)', 'check-circle', 'sales'],
    'compliance.checking' => ['In verification', 'Compliance starts verifying the client', 'search', 'compliance'],
    'compliance.completed' => ['Verification passed', 'The client passes compliance', 'shield', 'compliance'],
    'compliance.cancelled' => ['Cancelled in verification', 'The client cancels during compliance', 'x-circle', 'compliance'],
];

/** Events of one stage. @return array<string, array> */
function push_events_in(string $stage): array
{
    return array_filter(PUSH_EVENTS, fn ($e) => $e[3] === $stage);
}

/** Scope => label. */
const PUSH_SCOPES = [
    'all' => 'Whole operation',
    'team' => 'Their team only',
    'own' => 'Their own clients (agent or data entry)',
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
 * Recipients' filter data and the message for an event, or null when the
 * record does not exist. Each event has its own emoji so they are easy to
 * tell apart on the lock screen.
 *
 * "Own" clients are the ones a user is the sales agent of or filled in at
 * Processing (the person who enters the client's data).
 *
 * @return array{owners: list<?string>, managers: list<?string>, message: array}|null
 */
function push_build_event(string $event, string $id): ?array
{
    $id = rawurlencode($id);
    $agent = 'names,surnames,manager_id';
    $saleFields = "id,processing_id,client_full_name,status,debt,us_state,assigned_to,sales_manager_id,agent:assigned_to($agent),"
        . "processing(program,created_by,creator:created_by($agent))";

    if ($event === 'processing.new') {
        $row = supabase_select('processing', "select=id,phone,program,created_by,creator:created_by($agent)&id=eq.$id&limit=1")[0] ?? null;
        if ($row === null) {
            return null;
        }
        $creator = push_embed($row['creator'] ?? null);
        $who = phone((string) ($row['phone'] ?? ''));
        $digits = preg_replace('/\D+/', '', (string) ($row['phone'] ?? ''));
        if ($digits !== '') {
            $lead = supabase_select('opportunities', 'select=client_name&phone=eq.' . $digits . '&order=file_date.desc&limit=1')[0] ?? null;
            $who = client_name_case((string) ($lead['client_name'] ?? '')) ?: $who;
        }
        return [
            'owners' => [$row['created_by'] ?? null],
            'managers' => [$creator['manager_id'] ?? null],
            'message' => [
                'title' => '🆕 Potential client added',
                'body' => implode(' · ', array_filter([$who, $row['program'] ?? '', ($l = push_agent_label($creator)) !== '' ? 'added by ' . $l : ''])),
                'url' => 'client.php?id=' . $row['id'],
                'tag' => 'processing-new-' . $row['id'],
            ],
        ];
    }

    if (str_starts_with($event, 'sales.')) {
        $row = supabase_select('sales', "select=$saleFields&id=eq.$id&limit=1")[0] ?? null;
    } elseif (str_starts_with($event, 'compliance.')) {
        $compliance = supabase_select('compliance', "select=id,sale:sales_id($saleFields)&id=eq.$id&limit=1")[0] ?? null;
        $row = push_embed($compliance['sale'] ?? null);
    } else {
        return null;
    }
    if ($row === null) {
        return null;
    }

    $name = client_name_case((string) ($row['client_full_name'] ?? '')) ?: 'The client';
    $debt = money($row['debt'] ?? null);
    $state = strtoupper(trim((string) ($row['us_state'] ?? '')));
    $processing = push_embed($row['processing'] ?? null) ?? [];
    $creator = push_embed($processing['creator'] ?? null);
    // Second line of the notification: who filled in the data first, then the agent
    $team = array_filter([
        ($l = push_agent_label($creator)) !== '' ? 'Filled by ' . $l : '',
        ($l = push_agent_label(push_embed($row['agent'] ?? null))) !== '' ? 'Agent ' . $l : '',
    ]);
    $agentPart = $team !== [] ? "\n" . implode(' · ', $team) : '';
    $details = implode(' · ', array_filter([$debt !== '—' ? $debt : '', $state, (string) ($processing['program'] ?? '')]));

    [$title, $body] = match ($event) {
        'sales.signing' => ['✍️ Client signing', "$name is signing." . ($details !== '' ? " $details." : '') . $agentPart],
        'sales.signed' => [
            ($row['status'] ?? '') === 'THREE_DAYS_WAIT' ? '✅ Signature completed · CA 3-day wait' : '✅ Signature completed',
            $name . ($debt !== '—' ? ", with a $debt debt" . ($state !== '' ? " from $state" : '') . ',' : '') . ' has completed the signature.' . $agentPart,
        ],
        'compliance.checking' => ['🔎 In verification', "$name is being verified." . $agentPart],
        'compliance.completed' => ['🛡️ Verification passed', "$name has completed the verification successfully." . $agentPart],
        'compliance.cancelled' => ['❌ Cancelled in verification', "$name cancelled during verification." . $agentPart],
        default => [null, null],
    };
    if ($title === null) {
        return null;
    }

    return [
        'owners' => [$row['assigned_to'] ?? null, $processing['created_by'] ?? null],
        'managers' => [$row['sales_manager_id'] ?? (push_embed($row['agent'] ?? null)['manager_id'] ?? null), $creator['manager_id'] ?? null],
        'message' => [
            'title' => $title,
            'body' => $body,
            'url' => 'client.php?id=' . ($row['processing_id'] ?? ''),
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
function push_recipients(string $event, array $owners, array $managers): array
{
    $owners = array_map('strval', array_filter($owners));
    $managers = array_map('strval', array_filter($managers));
    $rules = supabase_select('pwa_notification_rules', 'select=user_id,scope&muted=is.false&event=eq.' . rawurlencode($event));
    $candidates = [];
    foreach ($rules as $r) {
        $userId = (string) $r['user_id'];
        $match = match ((string) $r['scope']) {
            'all' => true,
            'team' => in_array($userId, $managers, true),
            'own' => in_array($userId, $owners, true),
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
