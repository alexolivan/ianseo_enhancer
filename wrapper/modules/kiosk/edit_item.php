<?php
// wrapper/modules/kiosk/edit_item.php

require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';

$id = $_GET['id'] ?? 0;
$pdo = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['url'])) {
    $url = trim($_POST['url']);
    $duration = (int)$_POST['duration'];
    $id_post = (int)$_POST['id'];

    // Necesitamos el channel_id para saber a dónde redirigir al terminar
    $stmt = $pdo->prepare("SELECT channel_id FROM kiosk_contents WHERE id = ?");
    $stmt->execute([$id_post]);
    $ch_id = $stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE kiosk_contents SET url = ?, duration = ? WHERE id = ?");
    $stmt->execute([$url, $duration, $id_post]);

    Logger::info("Kiosko: Contenido ID $id_post actualizado.");
    header("Location: index.php?tab=programacion&edit_channel=" . $ch_id);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM kiosk_contents WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Contenido no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar URL - Gestor Kiosko</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 600px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom pt-3 pb-2">
            <h4 class="text-primary mb-0">Editar Contenido</h4>
        </div>
        <div class="card-body bg-light p-4">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">

                <div class="mb-3">
                    <label class="form-label text-secondary fw-bold">URL del Contenido</label>
                    <input type="url" name="url" class="form-control border-primary" required value="<?= htmlspecialchars($item['url']) ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary fw-bold">Duración en Pantalla (segundos)</label>
                    <input type="number" name="duration" class="form-control" required value="<?= $item['duration'] ?>" min="5">
                </div>

                <div class="d-flex justify-content-end gap-2">
		    <a href="index.php?tab=programacion&edit_channel=<?= $item['channel_id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
