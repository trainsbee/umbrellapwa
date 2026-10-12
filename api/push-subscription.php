<?php
declare(strict_types=1);

/**
 * The signed-in user's device subscription.
 * POST   {subscription}  saves this device
 * DELETE {endpoint}      removes this device
 */

require_once dirname(__DIR__) . '/includes/push.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/api.php';

$user = api_user();
api_require_csrf();
$body = json_body();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    push_save_subscription($user['id'], (array) ($body['subscription'] ?? []), (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))
        ? json_response(200, ['ok' => true])
        : json_response(422, ['ok' => false, 'error' => 'This device could not be registered.']);
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    push_delete_subscription($user['id'], (string) ($body['endpoint'] ?? ''));
    json_response(200, ['ok' => true]);
}
json_response(405, ['ok' => false]);
