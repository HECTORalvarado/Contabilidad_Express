# T05 — CRUD de clientes y proveedores

Objetivo: implementar el alta, edición, desactivación y listado de terceros (clientes/proveedores), siguiendo el patrón de `UsuariosController`.

## Archivos EXACTOS a crear (no toques ningún otro)
- `app/Controllers/TercerosController.php`
- `app/Views/terceros/index.php`
- `app/Views/terceros/form.php`

Prohibido modificar: `public/index.php`, `app/Models/Tercero.php`, layout, PROGRESO.md, briefs, otros controladores o vistas. Las rutas y el menú los añade el orquestador.

## Qué leer (solo esto)
- Este brief.
- `docs/CONVENCIONES.md` completo.
- `PROMPT.md` secciones 2, 5, 7, 9.
- `app/Models/Tercero.php` (solo lectura; usa sus métodos tal cual).
- `app/Controllers/UsuariosController.php` como referencia de controlador; `app/Views/usuarios/index.php` y `app/Views/usuarios/form.php` como referencia de vistas.

## Reglas
- Métodos: `index`, `create`, `store`, `edit(string $id)`, `update(string $id)`, `toggle(string $id)`.
- `index`: `Auth::require()` (cualquier usuario autenticado). Filtro `tipo` de `$_GET` solo si es `cliente` o `proveedor`; llama a `Tercero::all($tipo)`. Búsqueda opcional por `$_GET['q']` (nombre o identificador fiscal) aplicada en PHP con `stripos`, sin SQL dinámico. Pasa a la vista: `terceros`, `tipo` y `q`, además de `title`.
- `create/store/edit/update/toggle`: `Auth::require(['administrador','contador'])` al inicio.
- CSRF: valida `Csrf::validateToken($_POST['csrf_token'] ?? null)` antes de cualquier POST; `Csrf::field()` en los formularios.
- Validación en servidor: `tipo` en cliente|proveedor (en `edit`/`update` el tipo es fijo, no editable); `nombre` 1-150; `identificador_fiscal` 1-40; `email` opcional, válido y <=150; `telefono` opcional <=30. Usa `Tercero::existeIdentificador($tipo, $identificador, $id)` para duplicados. Muestra errores con `Flash::error(implode(' ', $errores))` y redirige al formulario.
- Captura `PDOException`: código `23505` → mensaje amigable de duplicado; otro → mensaje genérico; detalle solo en `error_log`.
- Auditoría: `Audit::log('tercero_creado'|'tercero_editado'|'tercero_activado'|'tercero_desactivado', 'terceros', $id, $anterior, $nuevo, $motivo, Auth::id())`.
- `toggle` cambia `activo` con `Tercero::setActivo` (nunca DELETE). `Tercero::find` devuelve `null` → `Flash::error` y redirigir.
- Tras guardar: `Flash::success(...)` y `redirect('/terceros')` (método privado con `header`/`exit`).
- Vistas Bootstrap 5 en español; `htmlspecialchars`/`View::e` en TODA salida; `Csrf::field()` en cada POST; tabla responsive con badges de Activo/Inactivo, búsqueda y filtro por tipo; formulario con `required`/`maxlength`. La vista `form.php` recibe `registro` (array|null), `accion`, `esNuevo`.
- Sin librerías nuevas, sin placeholders/TODO, sin tocar credenciales ni .env.

## Criterio de aceptación
- Un administrador o contador puede crear, editar y activar/desactivar terceros; un auditor solo ve el listado y recibe 403 si fuerza una URL de edición.
- No se pueden duplicar tipo+identificador fiscal; el error se muestra en español.
- Todo POST lleva CSRF; cada acción relevante queda en bitácora; no hay DELETE.
- Respuesta final: lista de archivos creados + resumen de máximo 3 líneas.
