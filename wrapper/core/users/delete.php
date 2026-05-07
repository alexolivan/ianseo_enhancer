<?php
// wrapper/core/users/delete.php
require_once __DIR__ . '/../auth_checker.php';
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../database/database.php';

if (!es_admin()) die("Acceso Denegado");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $loggedUser = $_SESSION['username'] ?? 'System';

    try {
        $pdo = Database::getInstance()->getConnection();

        // Obtenemos el nombre antes de borrarlo para el log
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetchColumn();

        if ($targetUser) {
            // Protección extra: No puedes borrarte a ti mismo
            if ($targetUser === $loggedUser) {
                Logger::error("USUARIOS: '$loggedUser' intentó borrarse a sí mismo. Operación bloqueada.");
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                Logger::info("USUARIOS: '$loggedUser' ELIMINÓ al usuario ID $id ($targetUser).");
            }
        }
    } catch (PDOException $e) {
        Logger::error("USUARIOS: Error al intentar eliminar ID $id. Motivo: " . $e->getMessage());
    }
}

header("Location: index.php");
exit;
