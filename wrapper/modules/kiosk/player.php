<?php
// wrapper/modules/kiosk/player.php

require_once __DIR__ . '/../../core/database/database.php';

$pdo = Database::getInstance()->getConnection();
$token = $_GET['token'] ?? null;

// 1. Identificar a qué pantalla estamos alimentando
if ($token) {
    $stmt = $pdo->prepare("SELECT id, channel_id FROM kiosk_displays WHERE token = ?");
    $stmt->execute([$token]);
} else {
    // Fallback: Si no hay token, buscamos la pantalla por defecto
    $stmt = $pdo->prepare("SELECT id, channel_id FROM kiosk_displays WHERE is_default = 1 LIMIT 1");
    $stmt->execute();
}
$display = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$display) {
    die("<h1 style='color:white; font-family:sans-serif; text-align:center; margin-top:20%;'>Kiosko: Pantalla no registrada o token inválido.</h1><style>body{background:#0b132b;}</style>");
}

$currentChannelId = $display['channel_id'];

// 2. Obtener la lista de contenidos si la pantalla tiene un canal asignado
$playlist = [];
if ($currentChannelId) {
    $stmt = $pdo->prepare("SELECT url, duration FROM kiosk_contents WHERE channel_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$currentChannelId]);
    $playlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Bandera para saber si hay emisión
$isEmpty = empty($playlist);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosko Display</title>
    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background: #0b132b; color: #fff; font-family: sans-serif; display: flex; align-items: center; justify-content: center; }
        iframe { width: 100%; height: 100%; border: none; display: <?php echo $isEmpty ? 'none' : 'block'; ?>; }
        .standby { text-align: center; display: <?php echo $isEmpty ? 'block' : 'none'; ?>; }
        .standby h1 { font-weight: 300; color: #7393B3; margin-top: 20px; letter-spacing: 2px;}
        .standby p { color: #555; }
    </style>
</head>
<body>

    <div class="standby">
        <!-- Pantalla de cortesía sobria y corporativa -->
        <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#7393B3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect>
            <polyline points="17 2 12 7 7 2"></polyline>
        </svg>
        <h1>ESPERANDO EMISIÓN</h1>
        <p>Club TAU - Digital Signage</p>
    </div>

    <iframe id="kiosk-frame" src=""></iframe>

    <script>
    const playlist = <?php echo json_encode($playlist); ?>;
    const currentChannelId = <?php echo json_encode($currentChannelId); ?>;
    const displayToken = "<?php echo $token ? htmlspecialchars($token) : ''; ?>";

    let currentIndex = 0;
    const frame = document.getElementById('kiosk-frame');

    // Función para verificar si el administrador cambió el canal de esta TV
    async function checkChannelChange() {
        try {
            // Le pasamos el token a la API para que compruebe esta tele en concreto
            const url = displayToken ? 'api_status.php?token=' + encodeURIComponent(displayToken) : 'api_status.php';
            const response = await fetch(url);
            const data = await response.json();

            // Si el channel_id en la base de datos ya no coincide con el que estamos reproduciendo...
            if (data.channel_id !== currentChannelId) {
                console.log("Cambio de canal detectado por el servidor. Recargando TV...");
                window.location.reload();
            }
        } catch (e) {
            console.error("Error comprobando estado:", e);
        }
    }

    function playNext() {
        if (playlist.length === 0) return; // Si no hay nada, el bucle no empieza y se queda el logo.

        const item = playlist[currentIndex];
        frame.src = item.url;

        setTimeout(() => {
            currentIndex = (currentIndex + 1) % playlist.length;
            checkChannelChange();
            playNext();
        }, item.duration * 1000);
    }

    // Ping de seguridad cada 30 segundos (por si la URL actual es muy larga)
    setInterval(checkChannelChange, 30000);

    if (playlist.length > 0) {
        playNext();
    }
    </script>

</body>
</html>
