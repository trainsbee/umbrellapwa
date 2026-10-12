<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Sign out only on POST with a valid token, so a link or image cannot do it
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf'] ?? null)) {
    logout_user();
}
header('Location: login.php');
exit;
