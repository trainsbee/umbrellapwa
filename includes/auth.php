<?php
declare(strict_types=1);

/**
 * Authentication against the Umbrella CRM users table (same username and
 * password as Umbrella). Sessions use secure cookies; forms carry a CSRF token.
 */

require_once __DIR__ . '/supabase.php';

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('umbrella_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/** CSRF token for the current session. */
function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool
{
    start_session();
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/**
 * Checks a username and password.
 *
 * @return array{ok: bool, user?: array, error?: string}
 */
function attempt_login(string $username, string $password): array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return ['ok' => false, 'error' => 'Enter your username and password.'];
    }
    if (!supabase_configured()) {
        return ['ok' => false, 'error' => 'Sign in is not available right now.'];
    }

    $rows = supabase_select('users', 'username=eq.' . rawurlencode($username)
        . '&select=id,username,password,names,surnames,status&limit=1');
    $row = $rows[0] ?? null;

    // Same message for unknown users and wrong passwords
    if ($row === null || !password_verify($password, (string) ($row['password'] ?? ''))) {
        return ['ok' => false, 'error' => 'Incorrect username or password.'];
    }
    if ((int) ($row['status'] ?? 0) !== 1) {
        return ['ok' => false, 'error' => 'This account is inactive.'];
    }

    $roles = [];
    foreach (supabase_select('user_roles', 'user_id=eq.' . rawurlencode((string) $row['id']) . '&select=roles(name)') as $r) {
        if (!empty($r['roles']['name'])) {
            $roles[] = (string) $r['roles']['name'];
        }
    }

    return ['ok' => true, 'user' => [
        'id' => (string) $row['id'],
        'username' => (string) $row['username'],
        'name' => trim(($row['names'] ?? '') . ' ' . ($row['surnames'] ?? '')),
        'roles' => $roles,
    ]];
}

function login_user(array $user): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
}

function current_user(): ?array
{
    start_session();
    return $_SESSION['user'] ?? null;
}

/** Returns the signed-in user or sends the visitor to the login page. */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function logout_user(): void
{
    start_session();
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    session_destroy();
}
