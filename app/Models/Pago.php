<?php
namespace App\Models;

use App\Core\Database;

class Pago {
    public const METODOS = ['efectivo', 'transferencia', 'cheque', 'deposito'];

    public static function porFactura(int $facturaId): array {
        $stmt = Database::getConnection()->prepare(
            'SELECT p.*, u.nombre AS registrado_por_nombre FROM pagos p
             JOIN usuarios u ON u.id = p.registrado_por
             WHERE p.factura_id = :id ORDER BY p.fecha DESC, p.id DESC'
        );
        $stmt->execute([':id' => $facturaId]);
        return $stmt->fetchAll();
    }

    public static function registrar(int $facturaId, array $d, int $usuarioId): array {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare(
                'SELECT id, estado, total FROM facturas WHERE id = :id AND activo = TRUE FOR UPDATE'
            );
            $stmt->execute([':id' => $facturaId]);
            $factura = $stmt->fetch();
            if ($factura === false) {
                $db->rollBack();
                return ['error' => 'No se encontró la factura solicitada.'];
            }
            if (!in_array((string) $factura['estado'], ['aprobada', 'parcial'], true)) {
                $db->rollBack();
                return ['error' => 'Solo se pueden registrar pagos en facturas aprobadas o parciales.'];
            }

            $doc = $db->prepare('SELECT 1 FROM documentos WHERE factura_id = :id LIMIT 1');
            $doc->execute([':id' => $facturaId]);
            if ($doc->fetchColumn() === false) {
                $db->rollBack();
                return ['error' => 'La factura necesita al menos un documento de soporte.'];
            }

            $suma = $db->prepare('SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE factura_id = :id');
            $suma->execute([':id' => $facturaId]);
            $totalCent = (int) round((float) $factura['total'] * 100);
            $pagadoCent = (int) round((float) $suma->fetchColumn() * 100);
            $montoCent = (int) round((float) $d['monto'] * 100);
            $saldoCent = $totalCent - $pagadoCent;
            if ($saldoCent <= 0) {
                $db->rollBack();
                return ['error' => 'La factura ya está totalmente pagada.'];
            }
            if ($montoCent <= 0 || $montoCent > $saldoCent) {
                $db->rollBack();
                return ['error' => 'El monto debe ser mayor a cero y no puede exceder el saldo pendiente.'];
            }

            $insert = $db->prepare(
                'INSERT INTO pagos (factura_id, fecha, monto, metodo, referencia, registrado_por)
                 VALUES (:factura_id, :fecha, :monto, :metodo, :referencia, :registrado_por) RETURNING id'
            );
            $insert->execute([
                ':factura_id' => $facturaId,
                ':fecha' => $d['fecha'],
                ':monto' => number_format($montoCent / 100, 2, '.', ''),
                ':metodo' => $d['metodo'],
                ':referencia' => $d['referencia'] !== '' ? $d['referencia'] : null,
                ':registrado_por' => $usuarioId,
            ]);
            $pagoId = (int) $insert->fetchColumn();

            $restoCent = $saldoCent - $montoCent;
            $estado = $restoCent === 0 ? 'pagada' : 'parcial';
            $update = $db->prepare('UPDATE facturas SET estado = :estado WHERE id = :id');
            $update->execute([':estado' => $estado, ':id' => $facturaId]);
            $db->commit();

            return [
                'error' => null,
                'id' => $pagoId,
                'estado' => $estado,
                'monto' => number_format($montoCent / 100, 2, '.', ''),
                'saldo' => number_format($restoCent / 100, 2, '.', ''),
                'estado_anterior' => (string) $factura['estado'],
                'saldo_anterior' => number_format($saldoCent / 100, 2, '.', ''),
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al registrar pago: ' . $e->getMessage());
            return ['error' => 'No se pudo registrar el pago. Intente de nuevo.'];
        }
    }
}
