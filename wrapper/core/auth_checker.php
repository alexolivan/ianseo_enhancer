<?php
//wrapper/core/auth_checker.php

require_once __DIR__ . '/../config.php';

// 1. Guardamos el nombre de sesión por defecto (normalmente PHPSESSID)
$default_session_name = session_name();

// 2. Iniciamos la sesión exclusiva de nuestro Wrapper
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// 3. Lógica de Rescate por cookie
if (!isset($_SESSION['user_logged']) && isset($_COOKIE[COOKIE_NAME])) {
    if ($_COOKIE[COOKIE_NAME] === COOKIE_SECRET) {
        $_SESSION['user_logged'] = true;
        if (LOG_ENABLED) error_log("PORTERO: Sesión restaurada.");
    }
}

// 4. Verificamos el estado de autenticación y lo guardamos en una variable
$is_logged_in = isset($_SESSION['user_logged']) && $_SESSION['user_logged'] === true;

// 5. ¡LA MAGIA DEL AISLAMIENTO!: Guardamos y cerramos nuestra sesión.
// Esto libera a PHP para que Ianseo pueda iniciar su propia sesión sin dar error.
session_write_close();

// 6. Restauramos el nombre de sesión por defecto para no confundir a Ianseo
session_name($default_session_name);

// 7. Aplicamos la restricción de acceso usando la variable que guardamos
if (!$is_logged_in) {
    $current_script = basename($_SERVER['PHP_SELF']);
    $public_pages = ['index.php', 'auth.php', 'login.php'];

    if (!in_array($current_script, $public_pages)) {
        header("Location: /index.php");
        exit;
    }
}
