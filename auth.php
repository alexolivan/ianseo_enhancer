<?php
//auth.php
require_once 'wrapper/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Lógica de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === AUTH_PASSWORD) {
        $_SESSION['user_logged'] = true;

        $tiempo_expiracion = time() + (86400 * 30);
        setcookie(COOKIE_NAME, COOKIE_SECRET, $tiempo_expiracion, "/", "", false, true);

        header("Location: index.php");
        exit;
    }
}

// Lógica de Logout
if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    setcookie(COOKIE_NAME, "", time() - 3600, "/");
    header("Location: login.php");
    exit;
}

function esta_autenticado() {
    return isset($_SESSION['user_logged']) && $_SESSION['user_logged'] === true;
}
