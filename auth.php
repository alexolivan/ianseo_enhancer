<?php
// auth.php
require_once 'wrapper/config.php';
require_once __DIR__ . '/wrapper/core/logger.php'; // Cargamos nuestro Logger

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    Logger::debug("auth.php: Sesión iniciada o recuperada. ID: " . session_id());
}

// Lógica de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    Logger::debug("auth.php: Intento de login detectado por POST.");
    
    if ($_POST['password'] === AUTH_PASSWORD) {
        $_SESSION['user_logged'] = true;

        $tiempo_expiracion = time() + (86400 * 30);
        setcookie(COOKIE_NAME, COOKIE_SECRET, $tiempo_expiracion, "/", "", false, true);

        Logger::info("auth.php: LOGIN EXITOSO. Sesión y cookie creadas. Redirigiendo a index.php...");
        header("Location: index.php");
        exit;
    } else {
        Logger::error("auth.php: Intento de login FALLIDO (contraseña incorrecta).");
    }
}

// Lógica de Logout
if (isset($_GET['logout'])) {
    Logger::info("auth.php: Petición de LOGOUT manual detectada. Destruyendo credenciales...");
    $_SESSION = array();
    session_destroy();
    setcookie(COOKIE_NAME, "", time() - 3600, "/");
    
    Logger::debug("auth.php: Logout completado. Redirigiendo a login.php...");
    header("Location: login.php");
    exit;
}

function esta_autenticado() {
    $estado = isset($_SESSION['user_logged']) && $_SESSION['user_logged'] === true;
    Logger::debug("auth.php: Función esta_autenticado() evaluada. Resultado: " . ($estado ? "TRUE" : "FALSE"));
    return $estado;
}
?>
