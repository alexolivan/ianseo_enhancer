<?php
// wrapper/modules/kiosk/add_channel.php

require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['name'])) {
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("INSERT INTO kiosk_channels (name) VALUES (?)");
    $stmt->execute([$_POST['name']]);
    
    $newId = $pdo->lastInsertId();
    Logger::info("Kiosko: Nuevo canal creado -> " . $_POST['name']);
    
    // Redirigimos al index y seleccionamos automáticamente el canal recién creado
    header("Location: index.php?edit_channel=" . $newId);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Canal - Gestor Kiosko</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 600px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom pt-3 pb-2">
            <h4 class="text-primary mb-0">Crear Nuevo Canal</h4>
        </div>
        <div class="card-body bg-light p-4">
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label text-secondary fw-bold">Nombre del Canal</label>
                    <input type="text" name="name" class="form-control border-primary" required placeholder="Ej: TV Recepción o Pantalla Pistas">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Canal</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
