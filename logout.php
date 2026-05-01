<?php
require_once 'config.php';

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookie
setcookie('remember_token', '', time() - 3600, '/');

// Destroy the session
session_destroy();

// Redirect to landing page
header('Location: ' . url('index.php'));
exit;
?>