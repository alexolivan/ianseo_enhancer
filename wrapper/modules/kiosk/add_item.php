<?php
// wrapper/modules/kiosk/add_item.php

require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';

$channel_id = $_GET['channel_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['url'])) {
    $pdo = Database::getInstance()->getConnection();
    $url = trim($_POST['url']);
    $duration = (int)$_POST['duration'];
    $ch_id = (int)$_POST['channel_id'];

    // Calculamos el último puesto de orden
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM kiosk_contents WHERE channel_id = ?");
    $stmt->execute([$ch_id]);
    $nextOrder = (int)$stmt->fetchColumn() + 1;

    $stmt = $pdo->prepare("INSERT INTO kiosk_contents (channel_id, url, duration, sort_order) VALUES (?, ?, ?, ?)");
    $stmt->execute([$ch_id, $url, $duration, $nextOrder]);
    
    Logger::info("Kiosko: Nueva URL añadida al canal $ch_id.");
    header("Location: index.php?edit_channel=" . $ch_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir URL - Gestor Kiosko</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 600px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom pt-3 pb-2">
            <h4 class="text-primary mb-0">Añadir Nuevo Contenido</h4>
        </div>
        <div class="card-body bg-light p-4">
            <form method="POST">
                <input type="hidden" name="channel_id" value="<?= htmlspecialchars($channel_id) ?>">

                <div class="mb-3">
                    <label class="form-label text-secondary fw-bold">URL del Contenido</label>
                    <input type="url" name="url" class="form-control border-primary" required placeholder="https://ejemplo.com/clasificacion">
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary fw-bold">Duración en Pantalla (segundos)</label>
                    <input type="number" name="duration" class="form-control" required value="30" min="5">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?edit_channel=<?= $channel_id ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar URL</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
