<?php
namespace App\Core;

class Audit {
    public static function log(string $accion, string $entidad, ?int $entidad_id = null, ?array $valor_anterior = null, ?array $valor_nuevo = null, ?string $motivo = null): void {
        try {
            $db = Database::getConnection();
            $user = Auth::user();
            $usuario_id = $user['id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $stmt = $db->prepare("
                INSERT INTO bitacora (usuario_id, fecha, accion, entidad, entidad_id, valor_anterior, valor_nuevo, motivo, ip)
                VALUES (:usuario_id, NOW(), :accion, :entidad, :entidad_id, :valor_anterior, :valor_nuevo, :motivo, :ip)
            ");

            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':accion' => $accion,
                ':entidad' => $entidad,
                ':entidad_id' => $entidad_id,
                ':valor_anterior' => $valor_anterior ? json_encode($valor_anterior) : null,
                ':valor_nuevo' => $valor_nuevo ? json_encode($valor_nuevo) : null,
                ':motivo' => $motivo,
                ':ip' => $ip
            ]);
        } catch (\Exception $e) {
            // Error en bitácora no debe interrumpir el flujo principal, pero se puede registrar en error_log
            error_log("Error al registrar bitácora: " . $e->getMessage());
        }
    }
}
