<?php
namespace App\Models;

use App\Core\Database;

class Documento {
    public const MAX_TAMANO = 5242880; // 5 MB
    private const MIME_extension = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public static function porFactura(int $facturaId): array {
        $stmt = Database::getConnection()->prepare(
            'SELECT d.*, u.nombre AS subido_por_nombre FROM documentos d
             JOIN usuarios u ON u.id = d.subido_por
             WHERE d.factura_id = :id ORDER BY d.creado_en DESC, d.id DESC'
        );
        $stmt->execute([':id' => $facturaId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = Database::getConnection()->prepare(
            'SELECT d.*, f.estado AS factura_estado FROM documentos d
             JOIN facturas f ON f.id = d.factura_id WHERE d.id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function ruta(string $nombreArchivo): ?string {
        if (preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $nombreArchivo) !== 1) {
            return null;
        }
        return dirname(__DIR__, 2) . '/storage/uploads/' . $nombreArchivo;
    }

    public static function guardar(array $archivo, int $facturaId, int $usuarioId): array {
        if (!is_array($archivo) || (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['error' => 'No se pudo subir el archivo. Intente de nuevo.'];
        }
        $tmp = (string) ($archivo['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['error' => 'No se pudo subir el archivo. Intente de nuevo.'];
        }
        $tamano = (int) ($archivo['size'] ?? 0);
        if ($tamano <= 0 || $tamano > self::MAX_TAMANO) {
            return ['error' => 'El archivo debe pesar entre 1 byte y 5 MB.'];
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $extension = self::MIME_extension[$mime] ?? null;
        if ($extension === null) {
            return ['error' => 'Solo se permiten archivos PDF, JPG o PNG.'];
        }
        $nombreOriginal = basename((string) ($archivo['name'] ?? 'documento'));
        $longitud = function_exists('mb_strlen') ? mb_strlen($nombreOriginal, 'UTF-8') : strlen($nombreOriginal);
        if ($nombreOriginal === '' || $longitud > 255) {
            return ['error' => 'El nombre del archivo no es válido.'];
        }

        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;
        $directorio = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true)) {
            return ['error' => 'No se pudo preparar el almacenamiento.'];
        }
        $ruta = $directorio . '/' . $nombreArchivo;
        if (!move_uploaded_file($tmp, $ruta)) {
            return ['error' => 'No se pudo guardar el archivo. Intente de nuevo.'];
        }

        $stmt = Database::getConnection()->prepare(
            'INSERT INTO documentos (factura_id, nombre_original, nombre_archivo, mime, tamano, subido_por)
             VALUES (:factura_id, :nombre_original, :nombre_archivo, :mime, :tamano, :subido_por) RETURNING id'
        );
        $stmt->execute([
            ':factura_id' => $facturaId,
            ':nombre_original' => $nombreOriginal,
            ':nombre_archivo' => $nombreArchivo,
            ':mime' => $mime,
            ':tamano' => $tamano,
            ':subido_por' => $usuarioId,
        ]);
        return ['error' => null, 'id' => (int) $stmt->fetchColumn()];
    }
}
