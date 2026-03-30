<?php
require_once __DIR__ . '/../../config/session.php';

// Destroy session
session_unset();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, app_session_cookie_path());
}

// Redirect to login page
header('Location: ' . app_index_url(['login' => '1', 'logout' => '1']));
exit;
?>

