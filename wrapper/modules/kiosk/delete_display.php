<?php
// wrapper/modules/kiosk/delete_display.php

require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo = Database::getInstance()->getConnection();

    // Opcional: Podrías comprobar si es la TV por defecto y advertir, 
    // pero a nivel de base de datos no hay problema en borrarla.
    $stmt = $pdo->prepare("DELETE FROM kiosk_displays WHERE id = ?");
    $stmt->execute([$id]);

    Logger::info("Kiosko: Pantalla ID $id eliminada.");
}

// Devolvemos a la pestaña de pantallas
header("Location: index.php?tab=pantallas");
exit;
