<?php
namespace App\Models;

use App\Core\Database;

class Factura {
    public const ESTADOS = ['pendiente_aprobacion', 'aprobada', 'rechazada', 'parcial', 'pagada'];

    public static function ivaPorcentaje(): float {
        $valor = Database::getConnection()->query("SELECT valor FROM parametros WHERE clave = 'iva_porcentaje'")->fetchColumn();
        return (float) ($valor !== false ? $valor : 0);
    }

    public static function diasAlerta(): int {
        $valor = Database::getConnection()->query("SELECT valor FROM parametros WHERE clave = 'dias_alerta_vencimiento'")->fetchColumn();
        return (int) ($valor !== false ? $valor : 0);
    }

    public static function find(int $id): ?array {
        $stmt = Database::getConnection()->prepare(
            'SELECT f.*, t.nombre AS tercero_nombre, t.identificador_fiscal, t.tipo AS tercero_tipo,
                    COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.factura_id = f.id), 0) AS pagado,
                    GREATEST(f.total - COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.factura_id = f.id), 0), 0) AS saldo
             FROM facturas f JOIN terceros t ON t.id = f.tercero_id
             WHERE f.id = :id AND f.activo = TRUE'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function calcularIva(string $subtotal): string {
        $base = number_format((float) $subtotal, 2, '.', '');
        return number_format((float) $base * self::ivaPorcentaje() / 100, 2, '.', '');
    }

    public static function existeDuplicado(string $tipo, int $terceroId, string $numero, string $fechaEmision, string $total, int $excluirId = 0): bool {
        $stmt = Database::getConnection()->prepare(
            'SELECT 1 FROM facturas
             WHERE tipo = :tipo AND tercero_id = :tercero_id AND numero_documento = :numero
               AND fecha_emision = :fecha_emision AND total = :total AND id <> :excluir LIMIT 1'
        );
        $stmt->execute([
            ':tipo' => $tipo,
            ':tercero_id' => $terceroId,
            ':numero' => $numero,
            ':fecha_emision' => $fechaEmision,
            ':total' => $total,
            ':excluir' => $excluirId,
        ]);
        return $stmt->fetchColumn() !== false;
    }

    public static function create(array $d, int $usuarioId): int {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO facturas (tipo, tercero_id, numero_documento, fecha_emision, fecha_vencimiento,
                    subtotal, iva, total, registrado_por)
             VALUES (:tipo, :tercero_id, :numero, :emision, :vencimiento,
                    :subtotal, :iva, :total, :registrado_por) RETURNING id'
        );
        $stmt->execute([
            ':tipo' => $d['tipo'],
            ':tercero_id' => $d['tercero_id'],
            ':numero' => $d['numero'],
            ':emision' => $d['emision'],
            ':vencimiento' => $d['vencimiento'],
            ':subtotal' => $d['subtotal'],
            ':iva' => $d['iva'],
            ':total' => $d['total'],
            ':registrado_por' => $usuarioId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public static function update(int $id, array $d): void {
        $stmt = Database::getConnection()->prepare(
            'UPDATE facturas SET tercero_id = :tercero_id, numero_documento = :numero,
                    fecha_emision = :emision, fecha_vencimiento = :vencimiento,
                    subtotal = :subtotal, iva = :iva, total = :total,
                    motivo_rechazo = NULL, estado = :estado
             WHERE id = :id AND activo = TRUE'
        );
        $stmt->execute([
            ':id' => $id,
            ':tercero_id' => $d['tercero_id'],
            ':numero' => $d['numero'],
            ':emision' => $d['emision'],
            ':vencimiento' => $d['vencimiento'],
            ':subtotal' => $d['subtotal'],
            ':iva' => $d['iva'],
            ':total' => $d['total'],
            ':estado' => $d['estado'],
        ]);
    }

    public static function listar(array $f, ?string $rol, int $usuarioId): array {
        $where = ['f.activo = TRUE'];
        $params = [];
        foreach ([
            'tipo' => 'f.tipo = :tipo',
            'estado' => 'f.estado = :estado',
            'fecha_desde' => 'f.fecha_emision >= :fecha_desde',
            'fecha_hasta' => 'f.fecha_emision <= :fecha_hasta',
        ] as $clave => $condicion) {
            if ($f[$clave] !== '') {
                $where[] = $condicion;
                $params[':' . $clave] = $f[$clave];
            }
        }
        if ($f['tercero_id'] > 0) {
            $where[] = 'f.tercero_id = :tercero_id';
            $params[':tercero_id'] = $f['tercero_id'];
        }
        if ($f['numero'] !== '') {
            $where[] = 'f.numero_documento ILIKE :numero';
            $params[':numero'] = '%' . $f['numero'] . '%';
        }
        if ($f['solo_vencidas']) {
            $where[] = "f.fecha_vencimiento < CURRENT_DATE AND
                GREATEST(f.total - COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.factura_id = f.id), 0), 0) > 0";
        }
        if ($rol === 'auxiliar') {
            $where[] = 'f.registrado_por = :usuario_id';
            $params[':usuario_id'] = $usuarioId;
        }

        $sqlWhere = implode(' AND ', $where);
        $db = Database::getConnection();
        $conteo = $db->prepare("SELECT COUNT(*) FROM facturas f WHERE $sqlWhere");
        $conteo->execute($params);
        $total = (int) $conteo->fetchColumn();
        $porPagina = 20;
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min(max(1, $f['pagina']), $paginas);

        $stmt = $db->prepare(
            "SELECT f.id, f.tipo, f.numero_documento, f.fecha_emision, f.fecha_vencimiento, f.estado,
                    f.total, t.nombre AS tercero_nombre,
                    GREATEST(f.total - COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.factura_id = f.id), 0), 0) AS saldo,
                    CASE WHEN f.fecha_vencimiento < CURRENT_DATE AND
                         GREATEST(f.total - COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.factura_id = f.id), 0), 0) > 0
                         THEN TRUE ELSE FALSE END AS vencida
             FROM facturas f JOIN terceros t ON t.id = f.tercero_id
             WHERE $sqlWhere
             ORDER BY f.fecha_emision DESC, f.id DESC LIMIT $porPagina OFFSET :off"
        );
        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }
        $stmt->bindValue(':off', ($pagina - 1) * $porPagina, \PDO::PARAM_INT);
        $stmt->execute();

        return ['filas' => $stmt->fetchAll(), 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas];
    }
}
