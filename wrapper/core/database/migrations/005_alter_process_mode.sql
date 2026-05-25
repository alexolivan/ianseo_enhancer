-- Migración: Alterar columna process_mode a VARCHAR(50) para soportar todos los modos del frontend sin truncamiento.
ALTER TABLE parser_profile_mappings MODIFY COLUMN process_mode VARCHAR(50) NOT NULL DEFAULT 'passthrough';

-- Añadir columna description a la tabla parser_import_profiles si no existe
ALTER TABLE parser_import_profiles ADD COLUMN description VARCHAR(255) DEFAULT NULL AFTER name;
