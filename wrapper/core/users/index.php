<?php
// wrapper/core/users/index.php
require_once __DIR__ . '/../auth_checker.php';
require_once __DIR__ . '/../../../auth.php'; // Para tener la función es_admin()
require_once __DIR__ . '/../database/database.php';

if (!es_admin()) {
    Logger::error("Intento de acceso denegado al listado de usuarios.");
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Acceso Denegado. Se requiere rol de Administrador.</h2>");
}

Logger::debug("Accediendo al panel de gestión de usuarios.");

$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->query("SELECT id, username, role, is_active, created_at FROM users ORDER BY id ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .btn-corporate { background-color: #0d6efd; color: white; }
        .btn-corporate:hover { background-color: #0b5ed7; color: white; }
        .text-corporate { color: #0d6efd; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-corporate"><i class="bi bi-people-fill me-2"></i>Gestión de Usuarios</h2>
        <a href="create.php" class="btn btn-corporate"><i class="bi bi-person-plus-fill me-2"></i>Nuevo Usuario</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Creado el</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr class="align-middle">
                        <td><?= htmlspecialchars($u['id']) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <?php if($u['role'] === 'admin'): ?>
                                <span class="badge bg-danger">Admin</span>
                            <?php elseif($u['role'] === 'editor'): ?>
                                <span class="badge bg-primary">Editor</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Viewer</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($u['is_active']): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                        <td class="text-end">
                            <a href="create.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-fill"></i></a>
                            <?php if ($u['username'] !== $_SESSION['username']): // Evitar borrarse a sí mismo ?>
                                <a href="delete.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas eliminar este usuario?');"><i class="bi bi-trash-fill"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
