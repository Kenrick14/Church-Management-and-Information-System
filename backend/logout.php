<?php
session_start();
require_once __DIR__ . '/supabase_client.php';

if (!empty($_SESSION['supabase_access_token'])) {
    supabase_sign_out($_SESSION['supabase_access_token']);
}

session_unset();
session_destroy();

header('Location: ../frontend/login.php');
exit();
