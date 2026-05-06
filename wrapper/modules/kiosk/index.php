<?php
// wrapper/modules/kiosk/index.php

require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$scriptName = basename($_SERVER['PHP_SELF']);

// --- LÓGICA DE REORDENACIÓN Y CANALES (Heredada de ayer) ---
if (isset($_GET['move']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $direction = $_GET['move'];
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
    // Redirigimos a la pestaña de programación
    header("Location: $scriptName?tab=programacion&edit_channel=" . $ch_id);
    exit;
}

// --- DATOS PARA LAS VISTAS ---
$channels = $pdo->query("SELECT * FROM kiosk_channels")->fetchAll(PDO::FETCH_ASSOC);
$displays = $pdo->query("SELECT d.*, c.name as channel_name FROM kiosk_displays d LEFT JOIN kiosk_channels c ON d.channel_id = c.id ORDER BY d.is_default DESC, d.name ASC")->fetchAll(PDO::FETCH_ASSOC);

$editChannelId = $_GET['edit_channel'] ?? ($channels[0]['id'] ?? 0);
$activeTab = $_GET['tab'] ?? 'pantallas';

$stmt = $pdo->prepare("SELECT * FROM kiosk_contents WHERE channel_id = ? ORDER BY sort_order ASC");
$stmt->execute([$editChannelId]);
$contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor Cartelería - Kiosko</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (La magia visual) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .nav-tabs .nav-link.active { font-weight: bold; color: #0d6efd; border-bottom: 3px solid #0d6efd; }
        .nav-tabs .nav-link { color: #6c757d; border: none; }
        .token-badge { font-family: monospace; font-size: 0.9em; background: #e9ecef; padding: 4px 8px; border-radius: 4px; border: 1px dashed #ced4da;}
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4 gap-3">
        <i class="bi bi-display text-primary" style="font-size: 2rem;"></i>
        <h2 class="mb-0 text-primary fw-bold">Cartelería Digital (Kiosko)</h2>
    </div>

    <!-- PESTAÑAS -->
    <ul class="nav nav-tabs mb-4" id="kioskTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'pantallas' ? 'active' : '' ?>" id="pantallas-tab" data-bs-toggle="tab" data-bs-target="#pantallas" type="button" role="tab">
                <i class="bi bi-tv me-2"></i>Control de Emisión (Pantallas)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'programacion' ? 'active' : '' ?>" id="programacion-tab" data-bs-toggle="tab" data-bs-target="#programacion" type="button" role="tab">
                <i class="bi bi-collection-play me-2"></i>Parrilla de Programación
            </button>
        </li>
    </ul>

    <!-- CONTENIDO DE LAS PESTAÑAS -->
    <div class="tab-content" id="kioskTabsContent">

        <!-- ========================================== -->
        <!-- PESTAÑA 1: PANTALLAS (CONTROL EN VIVO)     -->
        <!-- ========================================== -->
        <div class="tab-pane fade <?= $activeTab === 'pantallas' ? 'show active' : '' ?>" id="pantallas" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-secondary"><i class="bi bi-broadcast text-danger me-2"></i>Emisión en Vivo</h5>
		    <a href="add_display.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Añadir Pantalla</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="25%">Nombre de la Pantalla</th>
                                <th width="20%">Token (Identificador)</th>
                                <th width="30%">Canal Emitiendo</th>
                                <th width="25%" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($displays as $display): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold d-block"><?= htmlspecialchars($display['name']) ?></span>
                                    <?php if($display['is_default']): ?>
                                        <span class="badge bg-success" style="font-size: 0.7em;">Pantalla Principal (Por defecto)</span>
                                    <?php endif; ?>
                                </td>
				<td>
                                    <?php
                                    // Generar la URL absoluta dinámicamente
                                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                                    $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/player.php';
                                    $playerUrl = $display['is_default'] ? $baseUrl : $baseUrl . '?token=' . $display['token'];
                                    ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if(!$display['is_default']): ?>
                                            <span class="token-badge"><?= htmlspecialchars($display['token']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small"><em>URL Directa</em></span>
                                        <?php endif; ?>

                                        <button type="button" class="btn btn-sm btn-light border py-0 px-2" onclick="copyToClipboard('<?= $playerUrl ?>', this)" title="Copiar URL para la TV">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" action="update_display.php" class="d-flex gap-2">
                                        <input type="hidden" name="display_id" value="<?= $display['id'] ?>">
                                        <select name="channel_id" class="form-select form-select-sm border-primary">
                                            <option value="">-- Apagado (Standby) --</option>
                                            <?php foreach($channels as $c): ?>
                                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $display['channel_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($c['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm" title="Cambiar Canal"><i class="bi bi-send-check"></i></button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <!-- Botón de Preview -->
                                    <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="openPreview('<?= $display['token'] ?>', '<?= htmlspecialchars($display['name']) ?>')" title="Previsualizar">
                                        <i class="bi bi-eye"></i>
                                    </button>
				    <a href="edit_display.php?id=<?= $display['id'] ?>" class="btn btn-outline-secondary btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
				    <a href="delete_display.php?id=<?= $display['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Borrar esta pantalla del sistema?')" title="Borrar"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- PESTAÑA 2: PROGRAMACIÓN (CANALES Y URLS)   -->
        <!-- ========================================== -->
        <div class="tab-pane fade <?= $activeTab === 'programacion' ? 'show active' : '' ?>" id="programacion" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body bg-light border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <strong class="text-secondary">Editando Canal:</strong>
                        <select onchange="location.href='?tab=programacion&edit_channel='+this.value" class="form-select w-auto fw-bold">
                            <?php foreach($channels as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $editChannelId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="add_item.php?channel_id=<?= $editChannelId ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Añadir URL</a>
                        <a href="add_channel.php" class="btn btn-outline-success btn-sm"><i class="bi bi-folder-plus me-1"></i>Nuevo Canal</a>
                        <form action="delete_channel.php" method="POST" class="d-inline" onsubmit="return confirm('¿Borrar canal y TODO su contenido?');">
                            <input type="hidden" name="channel_id" value="<?= $editChannelId ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Borrar Canal"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">Orden</th>
                                <th width="55%">URL del Contenido</th>
                                <th width="15%">Duración</th>
                                <th width="20%" class="text-end">Acciones</th>
                            </tr>
                        </thead>
			<tbody>
                            <?php $orden_visual = 1; // Inicializamos el contador visual ?>
                            <?php foreach($contents as $row): ?>
                            <tr>
                                <td>
                                    <!-- Mostramos el contador visual en lugar de $row['sort_order'] -->
                                    <span class="fw-bold me-2"><?= $orden_visual++ ?></span>
                                    <a href="?tab=programacion&move=up&id=<?= $row['id'] ?>&edit_channel=<?= $editChannelId ?>" class="text-decoration-none text-secondary" title="Subir"><i class="bi bi-arrow-up-circle-fill"></i></a>
                                    <a href="?tab=programacion&move=down&id=<?= $row['id'] ?>&edit_channel=<?= $editChannelId ?>" class="text-decoration-none text-secondary" title="Bajar"><i class="bi bi-arrow-down-circle-fill"></i></a>
                                </td>
                                <td><a href="<?= htmlspecialchars($row['url']) ?>" target="_blank" class="text-decoration-none small text-truncate d-inline-block" style="max-width: 400px;"><i class="bi bi-link-45deg"></i> <?= htmlspecialchars($row['url']) ?></a></td>

                                <td><i class="bi bi-stopwatch text-muted me-1"></i><?= $row['duration'] ?>s</td>
                                <td class="text-end">
                                    <a href="edit_item.php?id=<?= $row['id'] ?>" class="btn btn-outline-secondary btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a href="delete_item.php?id=<?= $row['id'] ?>&channel=<?= $editChannelId ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Borrar este contenido?')" title="Borrar"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($contents)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Este canal está vacío.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL DE PREVISUALIZACIÓN (PREVIEW)        -->
<!-- ========================================== -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-dark border-0 shadow-lg">
      <div class="modal-header border-secondary border-bottom-0 pb-0">
        <h5 class="modal-title text-white"><i class="bi bi-tv me-2"></i><span id="previewTitle">Vista Previa</span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <!-- El contenedor que simula la pantalla -->
        <div class="ratio ratio-16x9 bg-black rounded overflow-hidden shadow">
            <iframe id="previewFrame" src="" allowfullscreen></iframe>
        </div>
        <div class="text-center mt-2 text-muted small">
            <i class="bi bi-info-circle me-1"></i> Esta es una previsualización en vivo. Las resoluciones pueden variar en la SmartTV física.
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS Bundle (necesario para pestañas y modales) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const previewFrame = document.getElementById('previewFrame');
    const previewTitle = document.getElementById('previewTitle');

    function openPreview(token, displayName) {
        // Configuramos el título
        previewTitle.textContent = 'Emisión actual: ' + displayName;

        // Construimos la URL del reproductor
        let url = 'player.php';
        if (token) {
            url += '?token=' + encodeURIComponent(token);
        }

        // Cargamos el iframe y abrimos el modal
        previewFrame.src = url;
        previewModal.show();
    }

    // Detener la reproducción cuando se cierra el modal para ahorrar recursos
    document.getElementById('previewModal').addEventListener('hidden.bs.modal', function () {
        previewFrame.src = '';
    });

    // Función para copiar la URL al portapapeles con feedback visual
    function copyToClipboard(text, btnElement) {
        navigator.clipboard.writeText(text).then(() => {
            const icon = btnElement.querySelector('i');
            // Cambiamos el icono temporalmente a un 'check' verde
            icon.className = 'bi bi-check-lg text-success';
            setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 2000);
        }).catch(err => {
            console.error('Error al copiar: ', err);
            alert('No se pudo copiar. Tu navegador puede estar bloqueando el portapapeles.');
        });
    }
</script>

</body>
</html>
