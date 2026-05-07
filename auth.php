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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_attempt'])) {
    Logger::debug("auth.php: Intento de login validado por marcador 'login_attempt'.");

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $autenticado = false;

    // A) Vía de Emergencia: Superadmin
    if ($username === SUPERADMIN_USER && password_verify($password, SUPERADMIN_PASS)) {
        $autenticado = true;
	$userRole = 'superadmin';
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
		$userRole = $user['role'];
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
	$_SESSION['role'] = $userRole;

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

                //Logger::info("auth.php: ¡RESCATE EN FRONTERA! Restaurando sesión para '$saved_username'.");
		// 2. NUEVO: Recuperar el rol de la DB para que no se pierda el botón de Admin
	        try {
		    $pdo = Database::getInstance()->getConnection();
	            $stmt = $pdo->prepare("SELECT role FROM users WHERE username = ? AND is_active = 1");
	             $stmt->execute([$saved_username]);
	            $role = $stmt->fetchColumn();

	            // Si no está en la DB (es el superadmin del config), le asignamos su rol
	            if (!$role && $saved_username === SUPERADMIN_USER) {
	                $role = 'superadmin';
	            }

	            $_SESSION['role'] = $role ?: 'viewer'; // Por defecto viewer si algo falla
	            Logger::info("auth.php: ¡RESCATE! Sesión y ROL (" . $_SESSION['role'] . ") restaurados para '$saved_username'.");
	    	} catch (Exception $e) {
		    Logger::error("Error recuperando rol en rescate: " . $e->getMessage());
		}

                return true;
            }
        }
    }
    return false;
}

function es_admin() {
    if (!esta_autenticado()) return false;

    // El rol está guardado en la sesión desde el momento del login
    $rol = $_SESSION['role'] ?? 'viewer';

    // Solo permitimos el paso a los admin de la DB o al superadmin intocable
    return ($rol === 'admin' || $rol === 'superadmin');
}

?>
