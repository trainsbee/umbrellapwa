<?php
declare(strict_types=1);

/**
 * Webhook called by the Supabase trigger (database/pwa-setup.sql) with
 * {event, id}. Authenticated with the shared secret in X-Webhook-Secret.
 */

require_once dirname(__DIR__) . '/includes/push.php';
require_once dirname(__DIR__) . '/includes/api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false]);
}
if (!defined('PUSH_WEBHOOK_SECRET') || !hash_equals(PUSH_WEBHOOK_SECRET, (string) ($_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? ''))) {
    json_response(401, ['ok' => false]);
}

$body = json_body();
$event = (string) ($body['event'] ?? '');
$id = (string) ($body['id'] ?? '');
if (!isset(PUSH_EVENTS[$event]) || !preg_match('/^[A-Za-z0-9-]{1,64}$/', $id)) {
    json_response(422, ['ok' => false, 'error' => 'Unknown event.']);
}

$built = push_build_event($event, $id);
if ($built === null) {
    json_response(404, ['ok' => false, 'error' => 'Record not found.']);
}

$recipients = push_recipients($event, $built['owners'], $built['managers']);
$result = push_send($recipients, $built['message']);
json_response(200, ['ok' => true, 'recipients' => count($recipients)] + $result);
