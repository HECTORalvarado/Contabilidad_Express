<?php
namespace App\Core;

class Audit {
    public static function log(string $accion, string $entidad, ?int $entidad_id = null, ?array $valor_anterior = null, ?array $valor_nuevo = null, ?string $motivo = null, ?int $usuario_id = null): void {
        try {
            $db = Database::getConnection();
            $usuario_id ??= Auth::id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            $stmt = $db->prepare(
                'INSERT INTO bitacora (usuario_id, accion, entidad, entidad_id, valor_anterior, valor_nuevo, motivo, ip)
                 VALUES (:usuario_id, :accion, :entidad, :entidad_id, :valor_anterior, :valor_nuevo, :motivo, :ip)'
            );

            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':accion' => $accion,
                ':entidad' => $entidad,
                ':entidad_id' => $entidad_id,
                ':valor_anterior' => $valor_anterior !== null ? json_encode($valor_anterior, JSON_UNESCAPED_UNICODE) : null,
                ':valor_nuevo' => $valor_nuevo !== null ? json_encode($valor_nuevo, JSON_UNESCAPED_UNICODE) : null,
                ':motivo' => $motivo,
                ':ip' => $ip,
            ]);
        } catch (\Throwable $e) {
            error_log('Error al registrar bitácora: ' . $e->getMessage());
        }
    }
}
