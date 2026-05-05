<?php
// wrapper/core/auth_checker.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/logger.php'; // Cargamos el Logger

Logger::debug("=== NUEVA PETICIÓN INTERCEPTADA ===");
Logger::debug("Script solicitado: " . $_SERVER['PHP_SELF']);

// NUEVO SENSOR: ¿Qué cookies nos está enviando el navegador?
$cookie_keys = empty($_COOKIE) ? 'Ninguna' : implode(', ', array_keys($_COOKIE));
Logger::debug("Cookies detectadas en la petición: [$cookie_keys]");

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    Logger::debug("Sesión iniciada. ID: " . session_id());
} else {
    Logger::debug("Sesión ya estaba activa. ID: " . session_id());
}

// Rescate por cookie
if (!isset($_SESSION['user_logged']) && isset($_COOKIE[COOKIE_NAME])) {
    Logger::info("No hay variable 'user_logged', pero existe la cookie de rescate.");
    
    if ($_COOKIE[COOKIE_NAME] === COOKIE_SECRET) {
        $_SESSION['user_logged'] = true;
        Logger::info("¡RESCATE EXITOSO! Sesión restaurada vía cookie.");
    } else {
        Logger::error("ALERTA: Cookie de rescate detectada pero el SECRET no coincide.");
    }
} elseif (isset($_SESSION['user_logged'])) {
    Logger::debug("El usuario ya estaba logueado correctamente en sesión.");
}

// Verificación final
if (!isset($_SESSION['user_logged']) || $_SESSION['user_logged'] !== true) {
    $current_script = basename($_SERVER['PHP_SELF']);
    $public_pages = ['index.php', 'auth.php', 'login.php'];

    Logger::debug("Usuario NO logueado. Evaluando si el script '$current_script' es público.");

    if (!in_array($current_script, $public_pages)) {
        Logger::info("BLOQUEO: Acceso denegado a '$current_script'. Redirigiendo a index.php...");
        header("Location: /index.php");
        exit;
    } else {
        Logger::debug("Acceso permitido a página pública: '$current_script'.");
    }
} else {
    Logger::debug("Acceso permitido a área protegida.");
}
?>
