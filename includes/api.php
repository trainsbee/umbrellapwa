<?php
declare(strict_types=1);

/** Helpers shared by the JSON endpoints in api/. */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_response(int $code, array $body): never
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}

/** JSON request body as an array ([] when empty or invalid). */
function json_body(): array
{
    $body = json_decode((string) file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}

/** Signed-in user for an API call (401 otherwise). */
function api_user(): array
{
    $user = current_user();
    if ($user === null) {
        json_response(401, ['ok' => false, 'error' => 'Sign in again.']);
    }
    return $user;
}

/** Rejects state-changing calls without a valid X-CSRF-Token header. */
function api_require_csrf(): void
{
    if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        json_response(403, ['ok' => false, 'error' => 'Your session expired. Reload the page.']);
    }
}
