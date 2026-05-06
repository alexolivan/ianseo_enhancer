<?php
// wrapper/modules/kiosk/delete_channel.php

require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['channel_id'])) {
    $channel_id = (int)$_POST['channel_id'];

    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("DELETE FROM kiosk_channels WHERE id = ?");
    $stmt->execute([$channel_id]);

    Logger::info("Kiosko: Canal ID $channel_id eliminado (MariaDB en cascada limpió sus contenidos).");
}

header("Location: index.php?tab=programacion");
exit;
