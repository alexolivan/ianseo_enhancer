<?php 
require_once 'auth.php'; 
// Cargamos las utilidades
require_once __DIR__ . '/wrapper/core/logger.php'; 
require_once __DIR__ . '/wrapper/config.php'; 

Logger::debug("=== CARGANDO PORTAL PRINCIPAL (index.php) ===");

if (!esta_autenticado()) { 
    Logger::info("Portal Principal: Usuario NO autenticado. Expulsando a login.php");
    header("Location: login.php"); 
    exit; 
}

Logger::debug("Portal Principal: Usuario autenticado correctamente. Renderizando iframe...");

$logoPath = "wrapper/assets/logo.png"; 

if (file_exists($logoPath)) {
    $logoHtml = '<img src="' . $logoPath . '" alt="Logo" height="32" class="d-inline-block align-text-top me-2 rounded">';
} else {
    $logoHtml = '<i class="bi bi-bullseye fs-4 me-2 text-white"></i>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal <?= ORG_NAME ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <script>
        if (window.top !== window.self) {
            window.top.location.href = window.self.location.href;
        }
    </script>
    <style>
        body, html { height: 100%; margin: 0; overflow: hidden; background-color: #f8f9fa; }
        
        /* La barra usa tu THEME_COLOR del config.php */
        .navbar-custom { background-color: <?= THEME_COLOR ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar { height: 60px; }
        
        #main-iframe { width: 100%; height: calc(100vh - 60px); border: none; display: block; }
        
        .nav-btn {
            border-radius: 6px;
            padding: 8px 16px !important;
            margin-right: 8px;
            color: rgba(255, 255, 255, 0.85);
            transition: all 0.2s ease-in-out;
        }
        .nav-btn:hover { background-color: rgba(255, 255, 255, 0.1); color: #ffffff; }
        .nav-btn.active { background-color: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 500; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom px-3">
        <a class="navbar-brand d-flex align-items-center text-white fw-bold" href="index.php" onclick="resetActive()">
            <?= $logoHtml ?>
            <!-- Utilizamos el ORG_NAME definido -->
            <span class="d-none d-sm-inline tracking-wide"><?= ORG_NAME ?></span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="topNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 mt-2 mt-lg-0">
                <li class="nav-item">
                    <!-- Uso de BASE_URL por si en el futuro cambias el directorio base -->
                    <a class="nav-link nav-btn active" href="ianseo/index.php" target="main-frame" onclick="setActive(this)">
                        <i class="bi bi-trophy me-2"></i>Torneos (Ianseo)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-btn" href="<?= BASE_URL ?>/modules/kiosk/" target="main-frame" onclick="setActive(this)">
                        <i class="bi bi-display me-2"></i>Gestor Pantallas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-btn" href="<?= BASE_URL ?>/modules/parser/" target="main-frame" onclick="setActive(this)">
                        <i class="bi bi-file-earmark-code me-2"></i>Parser AvaiBook
                    </a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center gap-3">
                <span class="text-light small opacity-75 d-none d-md-block"><i class="bi bi-person-circle me-1"></i>Admin</span>
                <a href="auth.php?logout=1" class="btn btn-outline-light btn-sm" title="Cerrar Sesión">
                    <i class="bi bi-box-arrow-right me-1"></i>Salir
                </a>
            </div>
        </div>
    </nav>

    <iframe name="main-frame" id="main-iframe" src="ianseo/index.php"></iframe>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setActive(clickedElement) {
            document.querySelectorAll('.nav-btn').forEach(btn => { btn.classList.remove('active'); });
            clickedElement.classList.add('active');
        }
        function resetActive() {
            setActive(document.querySelector('.nav-btn'));
        }
    </script>
</body>
</html>
