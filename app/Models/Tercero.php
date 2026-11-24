<?php
namespace App\Models;

use App\Core\Database;

class Tercero {
    public static function activos(string $tipo = ''): array {
        $sql = 'SELECT id, tipo, nombre, identificador_fiscal, email, telefono FROM terceros WHERE activo = TRUE';
        $params = [];
        if ($tipo !== '') {
            $sql .= ' AND tipo = :tipo';
            $params[':tipo'] = $tipo;
        }
        $sql .= ' ORDER BY nombre';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function all(string $tipo = ''): array {
        $sql = 'SELECT id, tipo, nombre, identificador_fiscal, email, telefono, activo, creado_en FROM terceros';
        $params = [];
        if ($tipo !== '') {
            $sql .= ' WHERE tipo = :tipo';
            $params[':tipo'] = $tipo;
        }
        $sql .= ' ORDER BY nombre';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = Database::getConnection()->prepare('SELECT * FROM terceros WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function existeIdentificador(string $tipo, string $identificador, int $excluirId = 0): bool {
        $stmt = Database::getConnection()->prepare(
            'SELECT 1 FROM terceros WHERE tipo = :tipo AND identificador_fiscal = :identificador AND id <> :excluir LIMIT 1'
        );
        $stmt->execute([':tipo' => $tipo, ':identificador' => $identificador, ':excluir' => $excluirId]);
        return $stmt->fetchColumn() !== false;
    }

    public static function create(string $tipo, string $nombre, string $identificador, ?string $email, ?string $telefono): int {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO terceros (tipo, nombre, identificador_fiscal, email, telefono)
             VALUES (:tipo, :nombre, :identificador, :email, :telefono) RETURNING id'
        );
        $stmt->execute([
            ':tipo' => $tipo,
            ':nombre' => $nombre,
            ':identificador' => $identificador,
            ':email' => $email,
            ':telefono' => $telefono,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public static function update(int $id, string $nombre, string $identificador, ?string $email, ?string $telefono): void {
        $stmt = Database::getConnection()->prepare(
            'UPDATE terceros SET nombre = :nombre, identificador_fiscal = :identificador,
                    email = :email, telefono = :telefono WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':identificador' => $identificador,
            ':email' => $email,
            ':telefono' => $telefono,
        ]);
    }

    public static function setActivo(int $id, bool $activo): void {
        $stmt = Database::getConnection()->prepare('UPDATE terceros SET activo = :activo WHERE id = :id');
        $stmt->execute([':id' => $id, ':activo' => $activo ? 'true' : 'false']);
    }
}
