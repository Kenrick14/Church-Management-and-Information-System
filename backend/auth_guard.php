<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/supabase_client.php';

// Refresh if the access token expires within the next 60 seconds.
if (!empty($_SESSION['supabase_expires_at']) && time() > $_SESSION['supabase_expires_at'] - 60) {
    if (!empty($_SESSION['supabase_refresh_token'])) {
        $result = supabase_refresh_token($_SESSION['supabase_refresh_token']);

        if ($result['ok']) {
            $_SESSION['supabase_access_token']  = $result['data']['access_token'];
            $_SESSION['supabase_refresh_token'] = $result['data']['refresh_token'];
            $_SESSION['supabase_expires_at']    = time() + $result['data']['expires_in'];
        } else {
            session_unset();
            session_destroy();
            header('Location: ../frontend/login.php');
            exit();
        }
    }
}

if (empty($_SESSION['supabase_access_token'])) {
    header('Location: ../frontend/login.php');
    exit();
}
