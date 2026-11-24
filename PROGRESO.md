# PROGRESO

## Hecho
- Fase 0 casi completa: estructura MVC, autoload, router con parámetros, View, Flash, layout Bootstrap y páginas de error.
- `php.ini` creado con pdo_pgsql, fileinfo, mbstring y openssl; PostgreSQL 18 operativo.
- `database/schema.sql` aplicado sin errores sobre base limpia; trigger de bitácora inmutable presente.
- `database/seed.php` delegado a worker-d y verificado: 8 roles/usuarios, 6 terceros, 8 facturas, 8 documentos, 4 pagos, 2 parámetros; idempotente.
- Agentes worker-a..d actualizados a big-pickle / mimo / hy4 / beam; prueba 3.3 de worker-a OK (docs/ping.txt creado y borrado).

## A medias o con error
- Ninguno detectable: `php -l` en 17 archivos sin errores; home y 404 responden 200/404.

## Falta
- Fase 1: login/logout, bloqueo tras 5 intentos, RBAC, `Auth::require`, bitácora, módulo de usuarios.

## Decisiones
- Base local `Contabilidad_express` (credenciales solo en `.env`, no versionado); `.env.example` queda genérico.
- `.env` cambió durante la sesión (base/contraseña) sin intervención del orquestador; hash estable desde entonces. Se vigila que los workers no lo toquen.
- `/login` se habilita en Fase 1; inicio sin enlaces muertos.
