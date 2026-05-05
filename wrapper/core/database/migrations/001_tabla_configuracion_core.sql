-- Migración 001: Tabla para almacenar configuraciones globales del Wrapper
CREATE TABLE ecosystem_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertamos un valor de prueba para confirmar que funciona
INSERT INTO ecosystem_config (config_key, config_value) VALUES ('version_ecosistema', '1.0.0');
