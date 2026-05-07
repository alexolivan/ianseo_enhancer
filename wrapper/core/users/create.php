<?php
// wrapper/core/users/create.php
require_once __DIR__ . '/../auth_checker.php';
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../database/database.php';

if (!es_admin()) die("Acceso Denegado");

$id = $_GET['id'] ?? null;
$usuario = ['username' => '', 'role' => 'editor', 'is_active' => 1];
$titulo = "Nuevo Usuario";

if ($id) {
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usuarioDB) {
        $usuario = $usuarioDB;
        $titulo = "Editar Usuario: " . htmlspecialchars($usuario['username']);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 600px;">
    <div class="card shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h4 class="mb-0 text-primary"><i class="bi bi-person-gear me-2"></i><?= $titulo ?></h4>
        </div>
        <div class="card-body">
            <form action="save.php" method="POST">
                <?php if ($id): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre de Usuario</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($usuario['username']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Contraseña</label>
                    <input type="password" name="userpassword" class="form-control" <?= $id ? '' : 'required' ?>>
                    <?php if ($id): ?>
                        <div class="form-text">Déjalo en blanco si no quieres cambiar la contraseña actual.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Rol</label>
                    <select name="role" class="form-select">
                        <option value="viewer" <?= $usuario['role'] === 'viewer' ? 'selected' : '' ?>>Viewer (Solo lectura)</option>
                        <option value="editor" <?= $usuario['role'] === 'editor' ? 'selected' : '' ?>>Editor (Kiosko y Parser)</option>
                        <option value="admin" <?= $usuario['role'] === 'admin' ? 'selected' : '' ?>>Admin (Gestión total)</option>
                    </select>
                </div>

                <div class="mb-4 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= $usuario['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive">Usuario Activo</label>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
