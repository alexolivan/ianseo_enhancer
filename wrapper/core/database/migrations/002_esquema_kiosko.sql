-- Migración 002: Estructura base del módulo Kiosko con soporte Multi-pantalla

CREATE TABLE kiosk_config (
  setting_key VARCHAR(50) NOT NULL PRIMARY KEY,
  setting_value VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kiosk_channels (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kiosk_contents (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  channel_id INT DEFAULT NULL,
  url TEXT NOT NULL,
  duration INT DEFAULT 30,
  sort_order INT DEFAULT 0,
  CONSTRAINT kiosk_contents_ibfk_1 FOREIGN KEY (channel_id) REFERENCES kiosk_channels (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kiosk_displays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    channel_id INT DEFAULT NULL,
    is_default TINYINT(1) DEFAULT 0, -- 1: TV por defecto (para URL limpia), 0: Requiere Token
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT kiosk_displays_ibfk_1 FOREIGN KEY (channel_id) REFERENCES kiosk_channels (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- DATOS INICIALES (Out of the box)
-- ==========================================

-- 1. Creamos un canal base
INSERT INTO kiosk_channels (id, name) VALUES (1, 'Canal Principal');

-- 2. Creamos una pantalla por defecto conectada a ese canal
INSERT INTO kiosk_displays (name, token, channel_id, is_default)
VALUES ('TV Principal (Por Defecto)', 'default-tv-token-12345', 1, 1);
