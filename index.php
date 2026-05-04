<?php 
require_once 'auth.php'; 
if (!esta_autenticado()) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal Club TAU - Ianseo Wrapper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // FRAME BUSTING: Si Ianseo intenta cargar este index en el iframe, 
        // forzamos a la ventana superior a recargarse.
        if (window.top !== window.self) {
            window.top.location.href = window.self.location.href;
        }
    </script>
    <style>
        body, html { height: 100%; margin: 0; overflow: hidden; }
        .navbar { height: 56px; }
        #main-iframe { width: 100%; height: calc(100vh - 56px); border: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
        <a class="navbar-brand" href="index.php">Club TAU</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="ianseo/index.php" target="main-frame">Ianseo</a></li>
                <li class="nav-item"><a class="nav-link" href="wrapper/modules/kiosko/" target="main-frame">Gestor Kiosko</a></li>
                <li class="nav-item"><a class="nav-link" href="wrapper/modules/parser/" target="main-frame">Parser AvaiBook</a></li>
            </ul>
            <span class="navbar-text me-3 text-light">Sesión Activa</span>
            <a href="auth.php?logout=1" class="btn btn-outline-danger btn-sm">Salir</a>
        </div>
    </nav>
    <iframe name="main-frame" id="main-iframe" src="ianseo/index.php"></iframe>
</body>
</html>
