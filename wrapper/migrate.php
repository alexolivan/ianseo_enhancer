<?php
// wrapper/migrate.php

// Permitimos que este script se ejecute desde la terminal de Debian
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la linea de comandos (CLI).");
}

require_once __DIR__ . '/core/database/database.php';

echo "Iniciando motor de migraciones del ecosistema...\n";

$db = Database::getInstance();
$db->runMigrations();

echo "Proceso completado. Revisa el archivo de logs para mas detalles.\n";
?>
