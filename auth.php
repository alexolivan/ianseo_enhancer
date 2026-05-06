<?php
// auth.php
require_once __DIR__ . '/wrapper/config.php';
require_once __DIR__ . '/wrapper/core/database/database.php';
require_once __DIR__ . '/wrapper/core/logger.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    Logger::debug("auth.php: Sesión iniciada o recuperada. ID: " . session_id());
}

// Lógica de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    Logger::debug("auth.php: Intento de login detectado por POST.");
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $autenticado = false;

    // A) Vía de Emergencia: Superadmin
    if ($username === SUPERADMIN_USER && password_verify($password, SUPERADMIN_PASS)) {
        $autenticado = true;
        Logger::info("auth.php: Login de SUPERADMIN exitoso.");
    } 
    // B) Vía Normal: Base de datos
    else {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $autenticado = true;
                Logger::info("auth.php: Login del usuario '$username' exitoso.");
            } else {
                Logger::error("auth.php: Intento de login FALLIDO para '$username'.");
            }
        } catch (Exception $e) {
            Logger::error("auth.php: Error de DB en login: " . $e->getMessage());
        }
    }

    if ($autenticado) {
        session_regenerate_id(true);
        $_SESSION['user_logged'] = true; 
        $_SESSION['username'] = $username;

        // Nueva cookie firmada criptográficamente
        $tiempo_expiracion = time() + (86400 * 30);
        $firma = hash_hmac('sha256', $username, APP_SECRET);
        $cookie_token = base64_encode($username . '::' . $firma);
        
        setcookie(COOKIE_NAME, $cookie_token, $tiempo_expiracion, "/", "", false, true);

        Logger::info("auth.php: LOGIN EXITOSO. Sesión y cookie creadas. Redirigiendo a index.php...");
        header("Location: index.php");
        exit;
    } else {
        header("Location: login.php?error=1");
        exit;
    }
}

// Lógica de Logout
if (isset($_GET['logout'])) {
    Logger::info("auth.php: Petición de LOGOUT detectada.");
    $_SESSION = array();
    session_destroy();
    setcookie(COOKIE_NAME, "", time() - 3600, "/");
    header("Location: login.php");
    exit;
}

function esta_autenticado() {
    if (isset($_SESSION['user_logged']) && $_SESSION['user_logged'] === true) {
        return true;
    }
    
    // Validar cookie firmada
    if (isset($_COOKIE[COOKIE_NAME])) {
        $cookie_data = base64_decode($_COOKIE[COOKIE_NAME]);
        if (strpos($cookie_data, '::') !== false) {
            list($saved_username, $saved_hmac) = explode('::', $cookie_data);
            $expected_hmac = hash_hmac('sha256', $saved_username, APP_SECRET);

            if (hash_equals($expected_hmac, $saved_hmac)) {
                $_SESSION['user_logged'] = true;
                $_SESSION['username'] = $saved_username;
                Logger::info("auth.php: ¡RESCATE EN FRONTERA! Restaurando sesión para '$saved_username'.");
                return true;
            }
        }
    }
    return false;
}
?>
