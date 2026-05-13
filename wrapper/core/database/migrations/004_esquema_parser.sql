-- 1. LIMPIEZA DEL PASADO (Borramos todo rastro de tablas viejas, huérfanas o erróneas)
DROP TABLE IF EXISTS parser_profile_rules;
DROP TABLE IF EXISTS parser_profile_mappings;
DROP TABLE IF EXISTS parser_import_profiles;

-- 2. LOS FORMATOS (Contenedor Padre)
CREATE TABLE IF NOT EXISTS parser_import_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. MAPEO DE COLUMNAS Y MODOS DE OPERACIÓN
CREATE TABLE IF NOT EXISTS parser_profile_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    ianseo_field VARCHAR(50) NOT NULL,
    csv_column_index INT NOT NULL,
    process_mode ENUM('passthrough', 'mapping') DEFAULT 'passthrough',
    FOREIGN KEY (profile_id) REFERENCES parser_import_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_field_per_profile (profile_id, ianseo_field)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. DICCIONARIOS LOCALES POLIMÓRFICOS (Reglas internas de cada formato)
CREATE TABLE IF NOT EXISTS parser_profile_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    ianseo_field VARCHAR(50) NOT NULL,  -- Identifica si es 'division', 'gender', 'affil_1', etc.
    input_value VARCHAR(100) NOT NULL,  -- Lo que lee del CSV crudo
    output_value VARCHAR(100) NOT NULL, -- Salida principal (Código Ianseo, ej: 'R', 'M', '2401')
    secondary_output VARCHAR(150) DEFAULT NULL, -- Salida extra opcional (Nombre Oficial para afiliaciones)
    FOREIGN KEY (profile_id) REFERENCES parser_import_profiles(id) ON DELETE CASCADE,
    INDEX idx_engine (profile_id, ianseo_field, input_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
