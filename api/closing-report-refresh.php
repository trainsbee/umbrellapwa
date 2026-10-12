<?php
declare(strict_types=1);

/**
 * GET  -> current state of the data bot.
 * POST -> asks the bot to fetch calls for a date range. Body (JSON): {from, to}.
 *         Requires a session, a bot role and the X-CSRF-Token header.
 */

require_once dirname(__DIR__) . '/includes/bot.php';
require_once dirname(__DIR__) . '/includes/format.php';
require_once dirname(__DIR__) . '/includes/api.php';

$user = api_user();
if (!can_ask_bot($user)) {
    json_response(403, ['ok' => false, 'error' => 'Your role cannot ask the bot for data.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = bot_status();
    $status === null ? json_response(502, ['ok' => false, 'error' => 'Could not read the bot status.']) : json_response(200, ['ok' => true] + $status);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, ['ok' => false, 'error' => 'Method not allowed.']);
}
api_require_csrf();

$body = json_body();
$from = (string) ($body['from'] ?? '');
$to = (string) ($body['to'] ?? '');
if (!is_valid_date($from) || !is_valid_date($to) || $from > $to || $to > today()) {
    json_response(422, ['ok' => false, 'error' => 'Choose a valid date range.']);
}

bot_request($from, $to)
    ? json_response(200, ['ok' => true, 'requested_at' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM)])
    : json_response(502, ['ok' => false, 'error' => 'The request could not be sent to the bot.']);
