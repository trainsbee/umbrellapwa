<?php
declare(strict_types=1);

/**
 * GET  -> current state of the data bot.
 * POST -> asks the bot to fetch calls for a date range. Body (JSON): {from, to}.
 *         Requires a session, a bot role and the X-CSRF-Token header.
 */

require_once dirname(__DIR__) . '/includes/bot.php';
require_once dirname(__DIR__) . '/includes/format.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $code, array $body): never
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}

$user = current_user();
if ($user === null) {
    respond(401, ['ok' => false, 'error' => 'Sign in again.']);
}
if (!can_ask_bot($user)) {
    respond(403, ['ok' => false, 'error' => 'Your role cannot ask the bot for data.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = bot_status();
    $status === null ? respond(502, ['ok' => false, 'error' => 'Could not read the bot status.']) : respond(200, ['ok' => true] + $status);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Method not allowed.']);
}
if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    respond(403, ['ok' => false, 'error' => 'Your session expired. Reload the page.']);
}

$body = json_decode((string) file_get_contents('php://input'), true);
$from = (string) ($body['from'] ?? '');
$to = (string) ($body['to'] ?? '');
if (!is_valid_date($from) || !is_valid_date($to) || $from > $to || $to > today()) {
    respond(422, ['ok' => false, 'error' => 'Choose a valid date range.']);
}

bot_request($from, $to)
    ? respond(200, ['ok' => true, 'requested_at' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM)])
    : respond(502, ['ok' => false, 'error' => 'The request could not be sent to the bot.']);
