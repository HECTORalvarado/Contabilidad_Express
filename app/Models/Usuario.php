<?php
namespace App\Models;

use App\Core\Database;

class Usuario {
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 15;

    public static function findByEmail(string $email): ?array {
        $stmt = Database::getConnection()->prepare(
            'SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE lower(u.email) = lower(:email)
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array {
        $stmt = Database::getConnection()->prepare(
            'SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE u.id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array {
        return Database::getConnection()
            ->query('SELECT u.id, u.nombre, u.email, u.activo, u.intentos_fallidos, u.bloqueado_hasta,
                            u.creado_en, r.nombre AS rol_nombre
                     FROM usuarios u JOIN roles r ON r.id = u.rol_id
                     ORDER BY u.nombre')
            ->fetchAll();
    }

    public static function roles(): array {
        return Database::getConnection()->query('SELECT id, nombre FROM roles ORDER BY id')->fetchAll();
    }

    public static function estaBloqueado(array $usuario): bool {
        return !empty($usuario['bloqueado_hasta']) && strtotime((string) $usuario['bloqueado_hasta']) > time();
    }

    public static function registrarIntentoFallido(int $id): void {
        $db = Database::getConnection();
        $u = self::find($id);
        if (!$u) {
            return;
        }
        $intentos = (int) $u['intentos_fallidos'] + 1;
        if ($intentos >= self::MAX_INTENTOS) {
            $stmt = $db->prepare(
                'UPDATE usuarios SET intentos_fallidos = 0,
                        bloqueado_hasta = NOW() + INTERVAL \'' . self::MINUTOS_BLOQUEO . ' minutes\'
                 WHERE id = :id'
            );
            $stmt->execute([':id' => $id]);
        } else {
            $stmt = $db->prepare('UPDATE usuarios SET intentos_fallidos = :intentos WHERE id = :id');
            $stmt->execute([':intentos' => $intentos, ':id' => $id]);
        }
    }

    public static function limpiarIntentos(int $id): void {
        $stmt = Database::getConnection()->prepare(
            'UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    public static function desbloquear(int $id): void {
        self::limpiarIntentos($id);
    }

    public static function setActivo(int $id, bool $activo): void {
        $stmt = Database::getConnection()->prepare('UPDATE usuarios SET activo = :activo WHERE id = :id');
        $stmt->execute([':activo' => $activo ? 'true' : 'false', ':id' => $id]);
    }

    public static function setPassword(int $id, string $password): void {
        $stmt = Database::getConnection()->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
        $stmt->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]);
    }

    public static function create(string $nombre, string $email, string $password, int $rolId): int {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash, rol_id)
             VALUES (:nombre, :email, :hash, :rol_id)
             RETURNING id'
        );
        $stmt->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':rol_id' => $rolId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public static function update(int $id, string $nombre, string $email, int $rolId): void {
        $stmt = Database::getConnection()->prepare(
            'UPDATE usuarios SET nombre = :nombre, email = :email, rol_id = :rol_id WHERE id = :id'
        );
        $stmt->execute([':nombre' => $nombre, ':email' => $email, ':rol_id' => $rolId, ':id' => $id]);
    }
}
