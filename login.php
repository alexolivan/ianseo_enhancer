<?php
//login.php
require_once 'auth.php';
if (esta_autenticado()) { header("Location: index.php"); exit; }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Club TAU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; display: flex; align-items: center; height: 100vh; }
        .card-login { width: 400px; margin: auto; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="card card-login shadow-lg">
        <div class="card-body p-5 text-center">
            <h4 class="mb-4">Gestión de Competición</h4>
            <form method="POST" action="auth.php">
                <input type="password" name="password" class="form-control mb-3" placeholder="Contraseña del Club" required autofocus>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
                <?php if (isset($_GET['error'])) echo "<p class='text-danger mt-3'>Contraseña incorrecta</p>"; ?>
            </form>
        </div>
    </div>
</body>
</html>
