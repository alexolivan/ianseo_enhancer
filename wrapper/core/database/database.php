<?php
// wrapper/core/database/database.php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../logger.php';

class Database {
    private static $instance = null;
    private $pdo;

    // El constructor es privado para forzar el patrón Singleton
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Máxima seguridad contra inyecciones SQL
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            Logger::debug("Database: Conexión PDO establecida correctamente.");
        } catch (PDOException $e) {
            Logger::error("Database: Error crítico de conexión - " . $e->getMessage());
            die("Error de base de datos. Revisa los logs.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function runMigrations() {
        Logger::info("Database: Iniciando motor de migraciones...");
        $pdo = $this->getConnection();

        // 1. El Notario: Nos aseguramos de que la tabla de control exista
        $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Consultamos qué migraciones ya están aplicadas
        $stmt = $pdo->query("SELECT migration_name FROM schema_migrations");
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 3. Escaneamos la carpeta de migraciones
        $migrationsPath = __DIR__ . '/migrations/';
        if (!is_dir($migrationsPath)) {
            Logger::error("Database: Directorio no encontrado - " . $migrationsPath);
            return;
        }

        $files = glob($migrationsPath . '*.sql');
        sort($files); // Asegura que siempre se ejecuten en orden estricto (001, 002...)

        $appliedCount = 0;

        foreach ($files as $file) {
            $migrationName = basename($file);

            if (!in_array($migrationName, $executed)) {
                Logger::debug("Database: Aplicando migración -> $migrationName");
                $sql = file_get_contents($file);

		try {
                    // Transacción: O se aplica todo el SQL, o no se aplica nada
                    $pdo->beginTransaction();
                    $pdo->exec($sql);

                    // Firmamos en el registro
                    $insert = $pdo->prepare("INSERT INTO schema_migrations (migration_name) VALUES (?)");
                    $insert->execute([$migrationName]);

                    // Solo hacemos commit si MariaDB no lo ha hecho implícitamente por nosotros
                    if ($pdo->inTransaction()) {
                        $pdo->commit();
                    }

                    Logger::info("Database: Migración EXITOSA -> $migrationName");
                    $appliedCount++;
                } catch (PDOException $e) {
                    // Solo hacemos rollback si la transacción sigue viva
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    Logger::error("Database: FALLO en migración $migrationName -> " . $e->getMessage());
                    die("Migración abortada por seguridad. Revisa los logs.\n");
                }

            }
        }

        if ($appliedCount === 0) {
            Logger::debug("Database: El esquema está actualizado. No hay nada nuevo que aplicar.");
        } else {
            Logger::info("Database: Proceso finalizado. Se aplicaron $appliedCount migraciones nuevas.");
        }
    }
}
?>
