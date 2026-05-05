<?php
// wrapper/modules/kiosko/index.php

require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$scriptName = basename($_SERVER['PHP_SELF']); // Para los enlaces (index.php)

// --- LÓGICA DE REORDENACIÓN ---
if (isset($_GET['move']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $direction = $_GET['move']; // 'up' o 'down'
    $ch_id = (int)$_GET['edit_channel'];

    $stmt = $pdo->prepare("SELECT sort_order FROM kiosk_contents WHERE id = ?");
    $stmt->execute([$id]);
    $currentOrder = $stmt->fetchColumn();

    if ($direction === 'up') {
        $stmt = $pdo->prepare("SELECT id, sort_order FROM kiosk_contents WHERE channel_id = ? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1");
    } else {
        $stmt = $pdo->prepare("SELECT id, sort_order FROM kiosk_contents WHERE channel_id = ? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1");
    }
    
    $stmt->execute([$ch_id, $currentOrder]);
    $neighbor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($neighbor) {
        $pdo->prepare("UPDATE kiosk_contents SET sort_order = ? WHERE id = ?")->execute([$neighbor['sort_order'], $id]);
        $pdo->prepare("UPDATE kiosk_contents SET sort_order = ? WHERE id = ?")->execute([$currentOrder, $neighbor['id']]);
        Logger::info("Kiosko: Orden modificado para el contenido ID $id.");
    }
    header("Location: $scriptName?edit_channel=" . $ch_id);
    exit;
}

// --- LÓGICA DE ACCIONES ---
if (isset($_POST['set_active'])) {
    // Ahora actualizamos la TV por defecto en lugar de una variable global
    $stmt = $pdo->prepare("UPDATE kiosk_displays SET channel_id = ? WHERE is_default = 1");
    $stmt->execute([$_POST['channel_id']]);
    Logger::info("Kiosko: Emisión de la TV por defecto cambiada al canal " . $_POST['channel_id']);
    header("Location: $scriptName?msg=Emisión+actualizada"); 
    exit;
}

// --- DATOS PARA LA VISTA ---
$channels = $pdo->query("SELECT * FROM kiosk_channels")->fetchAll(PDO::FETCH_ASSOC);

// Obtenemos el canal asignado a la TV por defecto
$activeChannelId = $pdo->query("SELECT channel_id FROM kiosk_displays WHERE is_default = 1")->fetchColumn();
$editChannelId = $_GET['edit_channel'] ?? ($activeChannelId ?: ($channels[0]['id'] ?? 0));

$stmt = $pdo->prepare("SELECT * FROM kiosk_contents WHERE channel_id = ? ORDER BY sort_order ASC");
$stmt->execute([$editChannelId]);
$contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor Kiosko</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom pb-0 pt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0 text-primary">Gestor Kiosko</h3>
                <div>
                    <a href="add_channel.php" class="btn btn-outline-primary btn-sm">+ Nuevo Canal</a>
                </div>
            </div>
        </div>

        <div class="card-body bg-light border-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <form method="POST" class="d-flex align-items-center gap-2">
                        <strong class="text-secondary">Pantalla Principal emitiendo:</strong>
                        <select name="channel_id" class="form-select form-select-sm w-auto border-primary">
                            <?php foreach($channels as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $activeChannelId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="set_active" class="btn btn-primary btn-sm">Actualizar TV</button>
                    </form>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <form action="delete_channel.php" method="POST" onsubmit="return confirm('¿Borrar canal y TODO su contenido?');">
                        <input type="hidden" name="channel_id" value="<?= $editChannelId ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Borrar Canal Actual</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 text-secondary">Editando Canal:</h5>
                    <select onchange="location.href='?edit_channel='+this.value" class="form-select form-select-sm w-auto">
                        <?php foreach($channels as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $editChannelId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <a href="add_item.php?channel_id=<?= $editChannelId ?>" class="btn btn-primary btn-sm">+ Añadir URL</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">Orden</th>
                            <th width="50%">URL del Contenido</th>
                            <th width="15%">Duración</th>
                            <th width="25%" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($contents as $row): ?>
                        <tr>
                            <td>
                                <span class="fw-bold me-2"><?= $row['sort_order'] ?></span>
                                <a href="?move=up&id=<?= $row['id'] ?>&edit_channel=<?= $editChannelId ?>" class="text-decoration-none" title="Subir">⬆️</a>
                                <a href="?move=down&id=<?= $row['id'] ?>&edit_channel=<?= $editChannelId ?>" class="text-decoration-none" title="Bajar">⬇️</a>
                            </td>
                            <td><span class="text-muted small" style="word-break: break-all;"><?= htmlspecialchars($row['url']) ?></span></td>
                            <td><?= $row['duration'] ?>s</td>
                            <td class="text-end">
                                <a href="edit_item.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm">Editar</a>
                                <a href="delete_item.php?id=<?= $row['id'] ?>&channel=<?= $editChannelId ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Borrar este contenido?')">Borrar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($contents)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Este canal no tiene contenidos.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
