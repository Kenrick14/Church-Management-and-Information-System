<?php
session_start();
require_once __DIR__ . '/supabase_client.php';

// If someone hits this URL directly (not via the login form), just
// send them back rather than silently doing nothing.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../frontend/login.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter both email and password.';
    $_SESSION['old_email'] = $email;
    header('Location: ../frontend/login.php');
    exit();
}

$result = supabase_sign_in($email, $password);

if (!$result['ok']) {
    $_SESSION['login_error'] = 'Invalid email or password.';
    $_SESSION['old_email'] = $email;
    header('Location: ../frontend/login.php');
    exit();
}

// Success — store the Supabase session.
$_SESSION['supabase_access_token']  = $result['data']['access_token'];
$_SESSION['supabase_refresh_token'] = $result['data']['refresh_token'];
$_SESSION['supabase_expires_at']    = time() + $result['data']['expires_in'];

// Pull this user's role + display name in one call, using
// PostgREST's embedded resource syntax to join `members`.
$profile = supabase_rest('GET', 'users', [
    'select' => '*,members(first_name,last_name)',
    'uid'    => 'eq.' . $result['data']['user']['id'],
]);

if ($profile['ok'] && !empty($profile['data'][0])) {
    $row = $profile['data'][0];
    $_SESSION['user_role']     = $row['role'] ?? null;
    $_SESSION['user_username'] = $row['username'] ?? $email;
    $_SESSION['user_mem_id']   = $row['mem_id'] ?? null;

    if (!empty($row['members']['first_name'])) {
        $_SESSION['user_display_name'] = trim($row['members']['first_name'] . ' ' . $row['members']['last_name']);
    } else {
        $_SESSION['user_display_name'] = $_SESSION['user_username'];
    }
}

// Clear any leftover error/old-email from a previous failed attempt.
unset($_SESSION['login_error'], $_SESSION['old_email']);

header('Location: ../frontend/dashboard.php');
exit();