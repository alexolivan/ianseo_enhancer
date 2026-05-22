<?php
// wrapper/config.sample.php
// Archivo de configuración de ejemplo para Ianseo Enhancer.
// Copia este archivo a 'config.php' y completa con tus datos reales.

// ==========================================
// 1. SEGURIDAD Y SESIONES
// ==========================================
// Clave secreta de la aplicación (usada para firmar cookies/sesiones)
// No la cambies una vez en producción o desconectarás a todos los usuarios
// Puedes generar la clave con: openssl rand -hex 32
define('APP_SECRET', 'reemplaza_esta_cadena_con_tu_secreto_generado');
define('COOKIE_NAME', 'wrapper_cookie');
define('SESSION_NAME', 'WRAPPER_PORTAL_SESSION');

// Cuenta de rescate "Break-Glass" (Superadmin inborrable)
define('SUPERADMIN_USER', 'superadmin');
// Hash bcrypt de la contraseña del superadmin
// Puedes generar el hash en tu terminal con: php -r "echo password_hash('tu_clave_aqui', PASSWORD_DEFAULT);"
// (Ejemplo: hash para la clave 'admin123')
define('SUPERADMIN_PASS', '$2y$10$tZ92uW7yYhV0hJjOqCqPRe30L1.vS/hB8mFwQZly.7v1tY9O7H.m2');

// ==========================================
// 2. RUTAS DEL SISTEMA Y LOGGING
// ==========================================
// Ruta base física absoluta en el servidor
define('BASE_PATH', '/var/www/tu_directorio_ianseo/');
define('WRAPPER_PATH', BASE_PATH . 'wrapper/');

// Niveles permitidos: 'NONE', 'ERROR', 'INFO', 'DEBUG'
define('LOG_LEVEL', 'DEBUG');
// Archivo físico donde escupiremos los logs
define('LOG_FILE', WRAPPER_PATH . 'logs/enhancer.log');

// ==========================================
// 3. ENTORNO WEB Y VISUAL
// ==========================================
// Ruta relativa para construir los enlaces (href) en el menú de navegación HTML
define('BASE_URL', '/wrapper'); 

// Textos y colores que alimentarán los templates (header.php / footer.php)
define('APP_NAME', 'Ianseo Enhancer');
define('ORG_NAME', 'Nombre de tu Club'); 
define('THEME_COLOR', '#2c3e50');

// ==========================================
// 4. BASE DE DATOS UNIFICADA MARIADB
// ==========================================
// Credenciales para la conexión PDO que usarán todos los módulos
define('DB_HOST', 'localhost');
define('DB_NAME', 'ianseo_enhancer_db');
define('DB_USER', 'db_user');
define('DB_PASS', 'secure_password');
define('DB_CHARSET', 'utf8mb4');
