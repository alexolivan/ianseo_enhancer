<?php
// wrapper/modules/parser/api.php

// 1. CONTROL DE ACCESO Y CONFIGURACIÓN
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../core/auth_checker.php';
require_once __DIR__ . '/../../core/database/database.php';

// Cabecera JSON obligatoria
header('Content-Type: application/json; charset=utf-8');

// Verificación rápida de sesión
if (!esta_autenticado()) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Sesión no válida o no autenticado."]);
    exit;
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$pdo = Database::getInstance()->getConnection();

try {
    switch ($action) {
        case 'list_formats':
            $stmt = $pdo->query("SELECT id, name FROM parser_import_profiles ORDER BY name ASC");
            $formats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["status" => "success", "data" => $formats]);
            break;

        case 'get_format':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ID de formato no válido."]);
                exit;
            }

            // Obtener el perfil
            $stmt = $pdo->prepare("SELECT id, name FROM parser_import_profiles WHERE id = ?");
            $stmt->execute([$id]);
            $profile = $stmt->fetch();

            if (!$profile) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Formato no encontrado."]);
                exit;
            }

            // Mapeos
            $stmtMap = $pdo->prepare("SELECT ianseo_field, csv_column_index, process_mode FROM parser_profile_mappings WHERE profile_id = ?");
            $stmtMap->execute([$id]);
            $mappings = $stmtMap->fetchAll();

            // Reglas
            $stmtRule = $pdo->prepare("SELECT ianseo_field, input_value, output_value, secondary_output FROM parser_profile_rules WHERE profile_id = ?");
            $stmtRule->execute([$id]);
            $rules = $stmtRule->fetchAll();

            echo json_encode([
                "status" => "success",
                "data" => [
                    "id" => $profile['id'],
                    "name" => $profile['name'],
                    "mappings" => $mappings,
                    "rules" => $rules
                ]
            ]);
            break;

        case 'save_format':
            // Leemos el cuerpo JSON de la petición
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            if (!$data || empty($data['name'])) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "El nombre del formato es obligatorio."]);
                exit;
            }

            $name = trim($data['name']);
            $profileId = isset($data['id']) ? (int)$data['id'] : 0;
            $mappings = isset($data['mappings']) && is_array($data['mappings']) ? $data['mappings'] : [];
            $rules = isset($data['rules']) && is_array($data['rules']) ? $data['rules'] : [];

            // Iniciamos transacción para atomicidad total
            $pdo->beginTransaction();

            try {
                if ($profileId > 0) {
                    // Validamos que exista antes de actualizar
                    $stmtCheck = $pdo->prepare("SELECT id FROM parser_import_profiles WHERE id = ?");
                    $stmtCheck->execute([$profileId]);
                    if (!$stmtCheck->fetch()) {
                        $profileId = 0; // Si no existe, lo tratamos como inserción
                    }
                }

                if ($profileId > 0) {
                    // Actualización
                    // Verificamos si existe otro perfil con el mismo nombre para evitar violar la restricción UNIQUE
                    $stmtUnique = $pdo->prepare("SELECT id FROM parser_import_profiles WHERE name = ? AND id != ?");
                    $stmtUnique->execute([$name, $profileId]);
                    if ($stmtUnique->fetch()) {
                        throw new Exception("Ya existe otro formato con el nombre '$name'.");
                    }

                    $stmt = $pdo->prepare("UPDATE parser_import_profiles SET name = ? WHERE id = ?");
                    $stmt->execute([$name, $profileId]);

                    // Limpieza profunda de los hijos
                    $pdo->prepare("DELETE FROM parser_profile_mappings WHERE profile_id = ?")->execute([$profileId]);
                    $pdo->prepare("DELETE FROM parser_profile_rules WHERE profile_id = ?")->execute([$profileId]);
                } else {
                    // Inserción
                    // Verificamos unicidad de nombre
                    $stmtUnique = $pdo->prepare("SELECT id FROM parser_import_profiles WHERE name = ?");
                    $stmtUnique->execute([$name]);
                    if ($stmtUnique->fetch()) {
                        throw new Exception("Ya existe un formato con el nombre '$name'.");
                    }

                    $stmt = $pdo->prepare("INSERT INTO parser_import_profiles (name) VALUES (?)");
                    $stmt->execute([$name]);
                    $profileId = (int)$pdo->lastInsertId();
                }

                // Insertar mapeos
                $stmtMap = $pdo->prepare("INSERT INTO parser_profile_mappings (profile_id, ianseo_field, csv_column_index, process_mode) VALUES (?, ?, ?, ?)");
                foreach ($mappings as $map) {
                    if (empty($map['ianseo_field'])) continue;
                    $stmtMap->execute([
                        $profileId,
                        trim($map['ianseo_field']),
                        (int)$map['csv_column_index'],
                        trim($map['process_mode'] ?? 'passthrough')
                    ]);
                }

                // Insertar reglas diccionarios
                $stmtRule = $pdo->prepare("INSERT INTO parser_profile_rules (profile_id, ianseo_field, input_value, output_value, secondary_output) VALUES (?, ?, ?, ?, ?)");
                foreach ($rules as $rule) {
                    if (empty($rule['ianseo_field']) || !isset($rule['input_value']) || !isset($rule['output_value'])) continue;
                    $stmtRule->execute([
                        $profileId,
                        trim($rule['ianseo_field']),
                        trim($rule['input_value']),
                        trim($rule['output_value']),
                        !empty($rule['secondary_output']) ? trim($rule['secondary_output']) : null
                    ]);
                }

                $pdo->commit();
                echo json_encode([
                    "status" => "success",
                    "message" => "Formato guardado correctamente.",
                    "data" => ["id" => $profileId, "name" => $name]
                ]);
            } catch (Exception $txEx) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => $txEx->getMessage()]);
            }
            break;

        case 'delete_format':
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            $id = isset($data['id']) ? (int)$data['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ID de formato no válido para eliminación."]);
                exit;
            }

            // Al estar configurada la clave foránea como ON DELETE CASCADE, 
            // al eliminar de parser_import_profiles se eliminan mappings y rules automáticamente.
            $stmt = $pdo->prepare("DELETE FROM parser_import_profiles WHERE id = ?");
            $stmt->execute([$id]);

            Logger::info("Parser: Formato de importación ID $id eliminado correctamente.");
            echo json_encode(["status" => "success", "message" => "Formato eliminado con éxito de la base de datos."]);
            break;

        default:
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Acción REST no válida o ausente."]);
            break;
    }
} catch (PDOException $e) {
    Logger::error("Parser REST API: Error PDO crítico - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno del servidor de base de datos."]);
}
?>
