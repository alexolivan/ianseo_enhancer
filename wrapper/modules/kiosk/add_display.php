<?php
// wrapper/modules/kiosk/add_display.php

require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';

$pdo = Database::getInstance()->getConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['name'])) {
    $name = trim($_POST['name']);
    $token = trim($_POST['token']);
    
    // Limpieza de token
    $token = strtoupper(preg_replace('/\s+/', '', $token));
    if (empty($token)) {
        $token = 'TAU-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }

    $is_default = isset($_POST['is_default']) ? 1 : 0;

    try {
        $pdo->beginTransaction();

        if ($is_default) {
            $pdo->exec("UPDATE kiosk_displays SET is_default = 0");
        }

        $stmt = $pdo->prepare("INSERT INTO kiosk_displays (name, token, is_default) VALUES (?, ?, ?)");
        $stmt->execute([$name, $token, $is_default]);

        $pdo->commit();
        Logger::info("Kiosko: Nueva pantalla creada. Nombre: $name, Token: $token.");
        
        header("Location: index.php?tab=pantallas");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) {
            $error = "Error: El Token '$token' ya está siendo usado.";
        } else {
            $error = "Error de base de datos al guardar.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Pantalla - Kiosko</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 600px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom pt-3 pb-2">
            <h4 class="text-primary mb-0"><i class="bi bi-display me-2"></i>Añadir Nueva Pantalla</h4>
        </div>
        <div class="card-body bg-light p-4">
            
            <?php if($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-secondary fw-bold">Nombre de Ubicación</label>
                    <input type="text" name="name" class="form-control border-primary" required placeholder="Ej: TV Cantina">
                </div>
                
                <div class="mb-4">
                    <label class="form-label text-secondary fw-bold">Token (Identificador Corto)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-key"></i></span>
                        <input type="text" name="token" class="form-control text-uppercase" style="font-family: monospace;" placeholder="Dejar en blanco para autogenerar">
                    </div>
                    <div class="form-text mt-1">Palabra corta que teclearás en la TV. Sin espacios.</div>
                </div>

                <div class="mb-4 p-3 border rounded bg-white">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_default" name="is_default">
                        <label class="form-check-label fw-bold text-secondary" for="is_default">
                            Convertir en Pantalla Principal (Por defecto)
                        </label>
                    </div>
                    <div class="form-text mt-1">Si está activado, la TV cargará sin token. (Reemplazará a la actual si ya existe una).</div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?tab=pantallas" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Crear Pantalla</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
