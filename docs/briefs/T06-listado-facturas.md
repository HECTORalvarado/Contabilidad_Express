# T06 — Listado y filtros de facturas

Objetivo: crear la vista del listado paginado de facturas con filtros, badges y paginación.

## Archivo EXACTO a crear (no toques ningún otro)
- `app/Views/facturas/index.php`

Prohibido modificar controladores, modelos, rutas, layout, PROGRESO.md u otros archivos.

## Qué leer (solo esto)
- Este brief.
- `docs/CONVENCIONES.md` completo.
- `PROMPT.md` secciones 5, 8, 9.
- `app/Controllers/FacturasController.php`, método `index()` (contrato de datos).
- `app/Views/usuarios/index.php` y `app/Views/terceros/index.php` como referencia de estilo.

## Datos que recibe la vista (ya los prepara el controlador)
- `$facturas`: array de filas con claves `id, tipo, numero_documento, fecha_emision, fecha_vencimiento, estado, total, tercero_nombre, saldo, vencida`.
- `$filtros`: array con `tipo` (''|CXP|CXC), `estado` (''|uno de los 5), `tercero_id` (int), `fecha_desde` (YYYY-MM-DD o ''), `fecha_hasta` (YYYY-MM-DD o ''), `numero` (string), `solo_vencidas` (bool), `pagina` (int).
- `$pagina`, `$paginas`, `$total` (int).
- `$terceros`: array de activos con `id, nombre, identificador_fiscal, tipo`.
- `$puedeRegistrar` (bool): si es true se muestra el botón «Nueva factura» y la acción «Abrir» por fila.

## Reglas
- Formulario GET a `/facturas` con: número (texto), tipo (select Todos/CXP/CXC), estado (select Todos + los 5 estados con etiquetas en español), tercero (select), fecha desde/hasta (date), casilla «Solo vencidas» (value="1"), botones Buscar y Limpiar. Los valores actuales deben quedar seleccionados/llenos desde `$filtros`.
- Tabla responsive con columnas: Tipo, Número, Tercero, Emisión, Vencimiento, Estado, Total, Saldo, Acciones.
- Formato: fechas `dd/mm/aaaa`, montos USD con 2 decimales y separador de miles; usa `number_format`.
- Badges de estado: pendiente_aprobacion → warning y texto «Pendiente de aprobación»; aprobada → primary; parcial → info; pagada → success; rechazada → danger. Si `$vencida` es truthy (`true`, `'t'`, `'1'`), añade un badge danger «Vencida» junto a la fecha de vencimiento.
- Acciones: si `$puedeRegistrar`, enlace «Abrir» a `/facturas/{id}/editar`; si no, guion. No muestres acciones de aprobación/pago todavía (eso es Fase 3).
- Paginación: 20 por página. Pie con «Página X de Y — N facturas», botones Anterior/Siguiente deshabilitados en los extremos y enlaces que conserven TODOS los filtros activos (`http_build_query`). Escapa las URLs con `htmlspecialchars`.
- Estado vacío: fila con colspan correcto y mensaje «No hay facturas con esos filtros.».
- `htmlspecialchars`/`View::e` en TODA salida; sin consultas SQL, sin CSS externo nuevo, sin JavaScript innecesario, Bootstrap 5. Sin placeholders/TODO. Español.

## Criterio de aceptación
- La vista renderiza sin errores con los datos del controlador; filtros y paginación conservan el estado; roles sin permiso de edición no ven botones de registro/edición.
- Respuesta final: archivo creado + resumen de máximo 3 líneas.
