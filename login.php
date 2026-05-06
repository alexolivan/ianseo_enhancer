<?php
// login.php
require_once 'auth.php';
// Cargamos la configuración global
require_once __DIR__ . '/wrapper/config.php'; 

if (esta_autenticado()) { header("Location: index.php"); exit; }

$logoPath = "wrapper/assets/logo.png"; 

if (file_exists($logoPath)) {
    $logoHtml = '<img src="' . $logoPath . '" alt="Logo ' . ORG_NAME . '" style="max-height: 85px;" class="mb-3 rounded">';
} else {
    // Usamos tu THEME_COLOR para el icono si no hay imagen
    $logoHtml = '<div class="mb-3"><i class="bi bi-bullseye" style="font-size: 4rem; color: ' . THEME_COLOR . ';"></i></div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso <?= ORG_NAME ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; display: flex; align-items: center; height: 100vh; }
        .card-login { width: 100%; max-width: 400px; margin: auto; border-radius: 1rem; border: none; }
        
        /* Botón dinámico usando tu THEME_COLOR */
        .btn-corporate {
            background-color: <?= THEME_COLOR ?>;
            border-color: <?= THEME_COLOR ?>;
            color: #fff;
            transition: all 0.2s;
        }
        .btn-corporate:hover {
            filter: brightness(0.85); /* Lo oscurece un poco al pasar el ratón */
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="card card-login shadow">
        <div class="card-body p-5 text-center">
            
            <?= $logoHtml ?>
            
            <h4 class="mb-1 text-dark fw-bold"><?= ORG_NAME ?></h4>
            <p class="text-muted mb-4 small"><?= APP_NAME ?></p>
            
	    <form method="POST" action="auth.php">
                <!-- NUEVO CAMPO: Usuario -->
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="username" class="form-control form-control-lg fs-6" placeholder="Nombre de usuario" required autofocus>
                </div>

                <div class="input-group mb-4">
                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    <input type="password" name="password" class="form-control form-control-lg fs-6" placeholder="Contraseña de acceso" required>
                </div>
                
                <button type="submit" class="btn btn-corporate btn-lg w-100 fs-6 fw-bold">
                    Entrar al Sistema <i class="bi bi-arrow-right-short fs-5 align-middle"></i>
                </button>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger py-2 mt-3 small fw-bold d-flex align-items-center justify-content-center" role="alert">
                        <i class="bi bi-shield-lock-fill me-2 fs-5"></i> Credenciales incorrectas
                    </div>
                <?php endif; ?>
            </form>
            
        </div>
    </div>
</body>
</html>
