<?php
// wrapper/core/logger.php
require_once __DIR__ . '/../config.php';

class Logger {
    const LEVEL_ERROR = 1;
    const LEVEL_INFO = 2;
    const LEVEL_DEBUG = 3;

    private static function getNumericLevel($levelStr) {
        switch (strtoupper($levelStr)) {
            case 'DEBUG': return self::LEVEL_DEBUG;
            case 'INFO':  return self::LEVEL_INFO;
            case 'ERROR': return self::LEVEL_ERROR;
            default:      return 0; // NONE
        }
    }

    public static function log($levelStr, $message) {
        $currentLevel = self::getNumericLevel(defined('LOG_LEVEL') ? LOG_LEVEL : 'INFO');
        $messageLevel = self::getNumericLevel($levelStr);

        // Solo registramos si el nivel del mensaje es menor o igual al nivel configurado
        if ($messageLevel > 0 && $messageLevel <= $currentLevel) {
            $timestamp = date('Y-m-d H:i:s');
            // Formato: [2026-05-05 10:30:00] [DEBUG] El portero ha rescatado la sesión
            $formattedMessage = "[$timestamp] [$levelStr] $message\n";

            if (defined('LOG_FILE') && LOG_FILE) {
                // El 3 le dice a error_log que añada el mensaje al archivo especificado
                error_log($formattedMessage, 3, LOG_FILE);
            } else {
                // Fallback al error.log nativo de Nginx/PHP-FPM
                error_log("IanseoEnhancer [$levelStr]: $message");
            }
        }
    }

    // Funciones "atajo" para usar en el código
    public static function error($message) { self::log('ERROR', $message); }
    public static function info($message)  { self::log('INFO', $message); }
    public static function debug($message) { self::log('DEBUG', $message); }
}
?>
