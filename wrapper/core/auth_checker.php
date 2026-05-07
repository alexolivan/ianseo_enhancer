<?php
// wrapper/core/auth_checker.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/logger.php';

Logger::debug("=== NUEVA PETICIÓN INTERCEPTADA ===");
Logger::debug("Script solicitado: " . $_SERVER['PHP_SELF']);

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Rescate por cookie actualizada
if (!isset($_SESSION['user_logged']) && isset($_COOKIE[COOKIE_NAME])) {
    Logger::info("auth_checker: Evaluando cookie de rescate...");
    $cookie_data = base64_decode($_COOKIE[COOKIE_NAME]);

    if (strpos($cookie_data, '::') !== false) {
        list($saved_username, $saved_hmac) = explode('::', $cookie_data);
        $expected_hmac = hash_hmac('sha256', $saved_username, APP_SECRET);

        if (hash_equals($expected_hmac, $saved_hmac)) {
            $_SESSION['user_logged'] = true;
            $_SESSION['username'] = $saved_username;

            //Logger::info("¡RESCATE EXITOSO! Sesión restaurada vía cookie para '$saved_username'.");
	    // Recuperar el rol para que los scripts internos sepan qué permisos tienes
	    require_once __DIR__ . '/database/database.php';
	    try {
	        $pdo = Database::getInstance()->getConnection();
	        $stmt = $pdo->prepare("SELECT role FROM users WHERE username = ? AND is_active = 1");
	        $stmt->execute([$saved_username]);
	        $role = $stmt->fetchColumn();

	        if (!$role && $saved_username === SUPERADMIN_USER) {
	            $role = 'superadmin';
	        }

	        $_SESSION['role'] = $role ?: 'viewer';
	        Logger::info("auth_checker: Rol restaurado tras rescate de sesión.");
	    } catch (Exception $e) {
	         Logger::error("auth_checker: Fallo al restaurar rol.");
	    }

        } else {
            Logger::error("ALERTA: Firma de cookie inválida.");
        }
    }
}

// Verificación final
if (!isset($_SESSION['user_logged']) || $_SESSION['user_logged'] !== true) {
    $current_script = basename($_SERVER['PHP_SELF']);
    $public_pages = ['index.php', 'auth.php', 'login.php'];

    Logger::debug("Usuario NO logueado. Evaluando script '$current_script'.");

    if (!in_array($current_script, $public_pages)) {
        Logger::info("BLOQUEO: Acceso denegado. Redirigiendo a index.php...");
        // Ruta absoluta desde la raíz para evitar problemas de carpetas anidadas
        header("Location: /index.php"); 
        exit;
    }
} else {
    Logger::debug("Acceso permitido a área protegida.");
}
?>
