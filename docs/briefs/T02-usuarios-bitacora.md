# T02 — Módulo de usuarios y pantalla de bitácora

## Objetivo
Implementar, siguiendo el patrón existente, la gestión de usuarios (solo administrador) y la consulta filtrable de la bitácora (administrador y auditor).

## Lee antes de escribir
- `PROMPT.md` secciones 5 (roles), 7.10 (bitácora), 8.3 y 8.7 (pantallas), 9 (seguridad).
- `docs/CONVENCIONES.md` completo.
- `app/Controllers/LoginController.php` (patrón de controlador: CSRF, Flash, Audit, redirect).
- `app/Models/Usuario.php` (métodos disponibles; NO lo modifiques).
- `app/Views/auth/login.php` y `app/Views/layouts/main.php` (patrón de vistas/layout).
- `database/schema.sql` líneas 1–18 (usuarios/roles) y 85–111 (bitacora).

## Archivos exactos (crea SOLO estos)
1. `app/Controllers/UsuariosController.php`
2. `app/Views/usuarios/index.php`
3. `app/Views/usuarios/form.php`
4. `app/Controllers/BitacoraController.php`
5. `app/Views/bitacora/index.php`

Prohibido tocar cualquier otro archivo (`public/index.php`, Core, modelos, layout). Si falta algo, repórtalo y no lo edites.

## Rutas que el orquestador registrará (usa estas URLs literales)
- `GET /usuarios` → `UsuariosController::index`
- `GET /usuarios/crear` → `create`
- `POST /usuarios` → `store`
- `GET /usuarios/{id}/editar` → `edit`
- `POST /usuarios/{id}` → `update`
- `POST /usuarios/{id}/estado` → `toggle`
- `POST /usuarios/{id}/desbloquear` → `unlock`
- `POST /usuarios/{id}/password` → `resetPassword`
- `GET /bitacora` → `BitacoraController::index`

Los métodos reciben el id como parámetro string del router; conviértelo a `int`.

## Reglas funcionales
### Usuarios (solo `administrador`)
- `Auth::require('administrador')` al inicio de CADA método, incluidos los POST.
- Listado: nombre, email, rol, estado (activo/inactivo), bloqueo vigente (`bloqueado_hasta > NOW()`), intentos fallidos. Acciones: editar; activar/desactivar (POST); desbloquear (POST).
- Crear/editar: nombre (máx. 120), email válido único (máx. 150), rol (uno de `Usuario::roles()`). Contraseña obligatoria al crear (mín. 8); en edición NO se cambia (va en el formulario de restablecer).
- Validación en servidor; errores al usuario en español y sin detalle técnico. Captura `PDOException` con código `23505` (email duplicado) y muestra mensaje amigable.
- No permitir que el administrador se desactive a sí mismo ni que se cambie su propio rol.
- Restablecer contraseña: POST, nueva contraseña mín. 8, `Usuario::setPassword()`; no mostrar ni registrar la contraseña.
- Tras cada mutación: `Audit::log()` con acción `usuario_creado`, `usuario_editado`, `usuario_activado`, `usuario_desactivado`, `usuario_desbloqueado` o `usuario_password_reset`; entidad `usuarios`; `entidad_id`; `valor_anterior`/`valor_nuevo` sin `password_hash`.
- CSRF: `Csrf::field()` en cada formulario y `Csrf::validateToken()` antes de procesar POST; si falla, Flash de error y redirect.
- Flash de éxito/error y `Location` + `exit` (usa un `private function redirect(string $ruta)`).

### Bitácora (`administrador` y `auditor`)
- `Auth::require(['administrador', 'auditor'])`; solo lectura.
- Listado descendente por fecha, 50 por página (`?page=N`), con join a `usuarios` para mostrar nombre/email (puede ser null).
- Filtros GET: `usuario_id`, `accion`, `entidad`, `desde`, `hasta` (fechas `Y-m-d` desde `<input type="date">`); combínalos con AND y parámetros PDO. Muestra resumen de filtros y botón "Limpiar".
- Columnas: fecha (`d/m/Y H:i`), usuario, acción, entidad + id, motivo, IP, y valor anterior/nuevo (JSON decodificado en `<pre>` pequeño y escapado). Si no hay JSON, muestra `—`.
- Paginación que conserve los filtros en los enlaces.

## Seguridad obligatoria
- Solo PDO con consultas preparadas (los filtros opcionales se agregan con placeholders y array de parámetros).
- Toda salida con `App\Core\View::e()` o `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- Nada de `password_hash` en vistas ni en respuestas.
- Sin `TODO`, sin placeholders, sin librerías nuevas.

## Criterio de aceptación
- `php -l` sin errores en los 5 archivos.
- Un administrador puede crear, editar, activar/desactivar, desbloquear y restablecer contraseña; un rol distinto recibe 403 incluso escribiendo la URL.
- La bitácora filtra y pagina sin errores con y sin filtros.
- Cada cambio deja fila en `bitacora` mediante `Audit::log`.

## Respuesta final
Lista de archivos creados + resumen de máximo 3 líneas. No pegues el código.
