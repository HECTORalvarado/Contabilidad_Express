# Convenciones del proyecto

## Estructura
- `public/index.php`: único front controller; aquí se registran las rutas.
- `app/Core`: Router, View, Auth, Csrf, Audit, Flash, Database.
- `app/Controllers`, `app/Models`, `app/Views`.
- `config/config.php` carga `.env`; nunca escribir credenciales en código.
- `database/schema.sql` y `database/seed.php`.
- `storage/uploads/` fuera de `public/`; `docs/briefs/` para briefs de workers.

## Nombres
- Clases PascalCase; métodos camelCase; tablas y columnas en español, snake_case.
- Controladores: `FacturasController` con métodos `index`, `create`, `store`, `show`, `edit`, `update`.
- Vistas: `app/Views/<modulo>/<accion>.php`; layout común `layouts/main.php`.
- Rutas en español y plural: `/facturas`, `/terceros`, `/usuarios`, `/reportes`, `/bitacora`.

## Base de datos
- PostgreSQL con PDO. Solo consultas preparadas; nunca concatenar valores.
- `Database::getConnection()` entrega la conexión única.
- Montos `NUMERIC(14,2)`; fechas `DATE`; fechas técnicas `TIMESTAMPTZ`.
- Saldo = total − suma(pagos); "vencida" se calcula, no se guarda.
- Eliminación lógica (`activo = false`); nunca `DELETE` de datos de negocio.
- Pagos dentro de transacción con `SELECT ... FOR UPDATE`.

## Seguridad
- `Auth::require('rol')` o `Auth::require(['rol1','rol2'])` al inicio de cada acción protegida.
- `Csrf::field()` en cada formulario POST; validar con `Csrf::validateToken($_POST['csrf_token'] ?? null)` antes de procesar.
- `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` en toda salida (o `View::e()`).
- `Audit::log($accion, $entidad, $entidad_id, $anterior, $nuevo, $motivo)` en acciones relevantes.
- Errores al usuario: mensajes genéricos; detalle solo en `error_log`.

## Vistas
- Bootstrap 5 por CDN; español; USD con 2 decimales; fechas `dd/mm/aaaa`.
- Estados con badges: pendiente_aprobacion (warning), aprobada (primary), parcial (info), pagada (success), rechazada (danger).
- `View::render('modulo/accion', ['title' => '...', ...])`; el layout recibe `$content`, `$title` y `$usuario`.
- `Flash::success()` / `Flash::error()` para mensajes tras redirección.
- Formularios con validación en servidor y `required`/`maxlength` en cliente.
