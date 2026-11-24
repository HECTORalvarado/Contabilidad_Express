<?php
namespace App\Models;

use App\Core\Database;

class Reporte {
    /** Una sola fila agregada para el dashboard; sin consultas por factura. */
    public static function resumen(): array {
        $sql = "WITH pagos_factura AS (
                    SELECT factura_id, SUM(monto) AS pagado
                    FROM pagos
                    GROUP BY factura_id
                ), saldos AS (
                    SELECT f.id, f.tipo, f.estado, f.fecha_vencimiento,
                           GREATEST(f.total - COALESCE(p.pagado, 0), 0) AS saldo
                    FROM facturas f
                    LEFT JOIN pagos_factura p ON p.factura_id = f.id
                    WHERE f.activo = TRUE
                )
                SELECT
                    COALESCE(SUM(s.saldo) FILTER (WHERE s.tipo = 'CXP' AND s.saldo > 0), 0) AS total_cxp,
                    COALESCE(SUM(s.saldo) FILTER (WHERE s.tipo = 'CXC' AND s.saldo > 0), 0) AS total_cxc,
                    COUNT(*) FILTER (WHERE s.tipo = 'CXP' AND s.saldo > 0 AND s.fecha_vencimiento < CURRENT_DATE) AS vencidas_cxp,
                    COUNT(*) FILTER (WHERE s.tipo = 'CXC' AND s.saldo > 0 AND s.fecha_vencimiento < CURRENT_DATE) AS vencidas_cxc,
                    COUNT(*) FILTER (WHERE s.estado = 'pendiente_aprobacion') AS pendientes_aprobacion,
                    (SELECT valor::integer FROM parametros WHERE clave = 'dias_alerta_vencimiento') AS dias_alerta
                FROM saldos s";
        $fila = Database::getConnection()->query($sql)->fetch();
        return $fila ?: [];
    }

    /** Hasta 10 próximas a vencer, en una sola consulta agregada. */
    public static function proximas(int $dias, int $limite = 10): array {
        $sql = "WITH pagos_factura AS (
                    SELECT factura_id, SUM(monto) AS pagado
                    FROM pagos
                    GROUP BY factura_id
                ), saldos AS (
                    SELECT f.id, f.tipo, f.tercero_id, f.numero_documento, f.fecha_vencimiento,
                           GREATEST(f.total - COALESCE(p.pagado, 0), 0) AS saldo
                    FROM facturas f
                    LEFT JOIN pagos_factura p ON p.factura_id = f.id
                    WHERE f.activo = TRUE
                )
                SELECT s.id, s.tipo, s.numero_documento, t.nombre AS tercero_nombre,
                       s.fecha_vencimiento, s.saldo,
                       (s.fecha_vencimiento - CURRENT_DATE) AS dias_para_vencer
                FROM saldos s
                JOIN terceros t ON t.id = s.tercero_id
                WHERE s.saldo > 0
                  AND s.fecha_vencimiento >= CURRENT_DATE
                  AND s.fecha_vencimiento <= CURRENT_DATE + CAST(:dias AS integer) * INTERVAL '1 day'
                ORDER BY s.fecha_vencimiento, s.id
                LIMIT :limite";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->bindValue(':dias', $dias, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Antigüedad de cartera CxC por cliente (saldos > 0). */
    public static function antiguedadCxc(): array {
        $sql = "WITH pagos_factura AS (
                    SELECT factura_id, SUM(monto) AS pagado
                    FROM pagos
                    GROUP BY factura_id
                ), saldos AS (
                    SELECT f.tercero_id,
                           GREATEST(f.total - COALESCE(p.pagado, 0), 0) AS saldo,
                           (CURRENT_DATE - f.fecha_vencimiento) AS dias_atraso
                    FROM facturas f
                    LEFT JOIN pagos_factura p ON p.factura_id = f.id
                    WHERE f.activo = TRUE AND f.tipo = 'CXC'
                      AND f.estado IN ('aprobada', 'parcial')
                )
                SELECT t.id AS tercero_id, t.nombre AS cliente, t.identificador_fiscal,
                       COALESCE(SUM(s.saldo) FILTER (WHERE s.dias_atraso <= 0), 0) AS corriente,
                       COALESCE(SUM(s.saldo) FILTER (WHERE s.dias_atraso BETWEEN 1 AND 30), 0) AS a1_30,
                       COALESCE(SUM(s.saldo) FILTER (WHERE s.dias_atraso BETWEEN 31 AND 60), 0) AS a31_60,
                       COALESCE(SUM(s.saldo) FILTER (WHERE s.dias_atraso BETWEEN 61 AND 90), 0) AS a61_90,
                       COALESCE(SUM(s.saldo) FILTER (WHERE s.dias_atraso > 90), 0) AS mas90,
                       SUM(s.saldo) AS total
                FROM saldos s
                JOIN terceros t ON t.id = s.tercero_id
                WHERE s.saldo > 0 AND t.activo = TRUE
                GROUP BY t.id, t.nombre, t.identificador_fiscal
                ORDER BY total DESC, t.nombre";
        return Database::getConnection()->query($sql)->fetchAll();
    }

    /** CxP con saldo pendiente, por proveedor y ordenadas por vencimiento. */
    public static function cxpPendientes(): array {
        $sql = "WITH pagos_factura AS (
                    SELECT factura_id, SUM(monto) AS pagado
                    FROM pagos
                    GROUP BY factura_id
                ), saldos AS (
                    SELECT f.id, f.tercero_id, f.numero_documento, f.fecha_emision,
                           f.fecha_vencimiento, f.total, f.estado,
                           GREATEST(f.total - COALESCE(p.pagado, 0), 0) AS saldo
                    FROM facturas f
                    LEFT JOIN pagos_factura p ON p.factura_id = f.id
                    WHERE f.activo = TRUE AND f.tipo = 'CXP'
                      AND f.estado IN ('aprobada', 'parcial')
                )
                SELECT t.nombre AS proveedor, t.identificador_fiscal,
                       s.numero_documento, s.fecha_emision, s.fecha_vencimiento,
                       s.total, s.saldo,
                       CASE WHEN s.fecha_vencimiento < CURRENT_DATE THEN 'vencida' ELSE 'pendiente' END AS situacion,
                       GREATEST(CURRENT_DATE - s.fecha_vencimiento, 0) AS dias_atraso
                FROM saldos s
                JOIN terceros t ON t.id = s.tercero_id
                WHERE s.saldo > 0 AND t.activo = TRUE
                ORDER BY s.fecha_vencimiento, t.nombre, s.id";
        return Database::getConnection()->query($sql)->fetchAll();
    }
}
