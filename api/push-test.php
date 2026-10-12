<?php
declare(strict_types=1);

/**
 * POST {user_id?} sends a test notification. Users test their own devices;
 * admins can also test another user's devices.
 */

require_once dirname(__DIR__) . '/includes/push.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/api.php';

$user = api_user();
api_require_csrf();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false]);
}

$target = (string) (json_body()['user_id'] ?? $user['id']);
if ($target !== $user['id'] && !is_admin($user)) {
    json_response(403, ['ok' => false, 'error' => 'Only admins can test other users.']);
}

$result = push_send([$target], [
    'title' => 'UmbrellaApp',
    'body' => 'Test notification · it works on this device.',
    'url' => 'notifications.php',
    'tag' => 'test',
]);
$result['devices'] === 0
    ? json_response(200, ['ok' => false, 'error' => 'No devices have notifications turned on yet.'] + $result)
    : json_response(200, ['ok' => true] + $result);
