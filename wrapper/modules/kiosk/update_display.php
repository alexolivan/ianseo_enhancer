<?php
// wrapper/modules/kiosk/update_display.php

require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';
// require_once __DIR__ . '/../../core/logger.php'; // Descomenta si necesitas cargarlo manualmente

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['display_id'])) {
    $pdo = Database::getInstance()->getConnection();

    $display_id = (int)$_POST['display_id'];
    // Si viene vacío (la opción "-- Apagado --"), lo guardamos como NULL
    $channel_id = empty($_POST['channel_id']) ? null : (int)$_POST['channel_id'];

    $stmt = $pdo->prepare("UPDATE kiosk_displays SET channel_id = ? WHERE id = ?");
    $stmt->execute([$channel_id, $display_id]);

    // ¡LOGGER RESTAURADO Y MEJORADO!
    $estado = $channel_id ? "emitiendo el canal ID $channel_id" : "pasada a estado Standby (Apagado)";
    Logger::info("Kiosko: Pantalla ID $display_id $estado.");
}

// Devolvemos al usuario a la pestaña de Pantallas
header("Location: index.php?tab=pantallas");
exit;
