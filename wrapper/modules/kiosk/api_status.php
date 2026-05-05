<?php
// wrapper/modules/kiosk/api_status.php

require_once __DIR__ . '/../../core/database/database.php';

// Indicamos que somos una API
header('Content-Type: application/json');

$pdo = Database::getInstance()->getConnection();
$token = $_GET['token'] ?? null;

if ($token) {
    $stmt = $pdo->prepare("SELECT channel_id FROM kiosk_displays WHERE token = ?");
    $stmt->execute([$token]);
} else {
    $stmt = $pdo->prepare("SELECT channel_id FROM kiosk_displays WHERE is_default = 1 LIMIT 1");
    $stmt->execute();
}

$result = $stmt->fetch(PDO::FETCH_ASSOC);

// Devolvemos el ID del canal asignado (o null si se quedó huérfana)
echo json_encode([
    'channel_id' => $result ? $result['channel_id'] : null
]);
