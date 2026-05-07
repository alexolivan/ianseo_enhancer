<?php
// wrapper/core/users/save.php
require_once __DIR__ . '/../auth_checker.php';
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../database/database.php';

if (!es_admin()) die("Acceso Denegado");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['userpassword'];

    $pdo = Database::getInstance()->getConnection();
    $loggedUser = $_SESSION['username'] ?? 'System';

    try {
        if ($id) {
            // ACTUALIZAR USUARIO EXISTENTE
            if (!empty($password)) {
                // Cambia contraseña
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, password_hash=?, role=?, is_active=? WHERE id=?");
                $stmt->execute([$username, $hash, $role, $is_active, $id]);
                Logger::info("USUARIOS: '$loggedUser' modificó al usuario ID $id ($username) y cambió su contraseña. Rol: $role, Activo: $is_active");
            } else {
                // Solo actualiza datos
                $stmt = $pdo->prepare("UPDATE users SET username=?, role=?, is_active=? WHERE id=?");
                $stmt->execute([$username, $role, $is_active, $id]);
                Logger::info("USUARIOS: '$loggedUser' modificó al usuario ID $id ($username). Rol: $role, Activo: $is_active");
            }
        } else {
            // CREAR NUEVO USUARIO
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $role, $is_active]);
            $nuevoId = $pdo->lastInsertId();
            Logger::info("USUARIOS: '$loggedUser' creó un NUEVO usuario. ID: $nuevoId, Nombre: $username, Rol: $role");
        }
    } catch (PDOException $e) {
        Logger::error("USUARIOS: Error al guardar usuario '$username' por '$loggedUser'. Motivo: " . $e->getMessage());
        // Podríamos redirigir con error, pero para sysadmins, con el log nos vale por ahora
    }

    header("Location: index.php");
    exit;
}
