<?php
// wrapper/modules/kiosk/delete_item.php

require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';

if (isset($_GET['id']) && isset($_GET['channel'])) {
    $id = (int)$_GET['id'];
    $channel_id = (int)$_GET['channel'];

    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("DELETE FROM kiosk_contents WHERE id = ?");
    $stmt->execute([$id]);

    Logger::info("Kiosko: Contenido ID $id eliminado del canal $channel_id.");
}

header("Location: index.php?edit_channel=" . ($channel_id ?? ''));
exit;
