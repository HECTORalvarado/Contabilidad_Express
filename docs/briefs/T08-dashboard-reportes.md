# T08 — Dashboard y reportes con CSV (worker-c)

## Objetivo
Crear el dashboard y las pantallas de reportes (CxC por antigüedad y CxP pendientes/vencidas) con exportación CSV segura. Solo lectura: nadie modifica datos desde estas pantallas.

## Archivos
- Crear: `app/Controllers/DashboardController.php`, `app/Controllers/ReportesController.php`.
- Crear: `app/Views/dashboard/index.php`, `app/Views/reportes/index.php`, `app/Views/reportes/antiguedad.php`, `app/Views/reportes/cxp.php`.
- Modificar: `public/index.php` (registrar rutas), `app/Views/layouts/main.php` (enlaces de navegación según rol).
- Prohibido tocar: modelos, `app/Core/*`, controladores existentes, otras vistas, `.env`, docs distintos a este brief. En particular NO modificar `app/Models/Reporte.php` ni `app/Core/Csv.php`.

## Qué leer
- `docs/CONVENCIONES.md`.
- PROMPT.md: secciones 2, 5, 6, 8, 9 y 10.
- Referencia de controlador de solo lectura: `app/Controllers/BitacoraController.php`.
- Referencia de vista con tabla, fechas y montos: `app/Views/facturas/index.php` y `app/Views/bitacora/index.php`.

## Datos disponibles (ya implementados por el orquestador)
Llama SOLO a estos métodos; NO escribas SQL ni consultas PDO:
- `\App\Models\Reporte::resumen()`: array con `total_cxp`, `total_cxc`, `vencidas_cxp`, `vencidas_cxc`, `pendientes_aprobacion`, `dias_alerta`. Ya es una consulta agregada de una sola fila.
- `\App\Models\Reporte::proximas(int $dias, int $limite = 10)`: filas con `id`, `tipo`, `numero_documento`, `tercero_nombre`, `fecha_vencimiento` (AAAA-MM-DD), `saldo`, `dias_para_vencer`. Ya está ordenada por vencimiento.
- `\App\Models\Reporte::antiguedadCxc()`: filas con `tercero_id`, `cliente`, `identificador_fiscal`, `corriente`, `a1_30`, `a31_60`, `a61_90`, `mas90`, `total`.
- `\App\Models\Reporte::cxpPendientes()`: filas con `proveedor`, `identificador_fiscal`, `numero_documento`, `fecha_emision`, `fecha_vencimiento`, `total`, `saldo`, `situacion` (`pendiente` o `vencida`), `dias_atraso`. Ya viene ordenada por vencimiento.
- `\App\Core\Csv::descargar(string $nombre, array $encabezados, array $filas)`: escribe la cabecera, BOM UTF-8, usa `fputcsv` y antepone `'` a toda celda de texto que empieza por `=`, `+`, `-` o `@`. Úsalo tal cual; NO reimplementes la lógica CSV.

## Permisos (verificar en servidor, no solo en la vista)
- Dashboard: `Auth::require(['administrador', 'gerente', 'tesorero', 'auditor'])` en `DashboardController::index`.
- Reportes (incluidas las 4 acciones y los 2 CSV): `Auth::require(['administrador', 'gerente', 'contador', 'tesorero', 'auditor'])` al inicio de CADA método de `ReportesController`.
- Tesorero y auditor son solo lectura: no hay POST, no hay CSRF, no hay `Audit::log()` en este módulo. Si un role no está en la lista, debe recibir 403 aunque escriba la URL directamente.
- En `layouts/main.php` muestra "Dashboard" solo a los roles de dashboard y "Reportes" solo a los roles de reportes; esto es cosmético, la autoridad es `Auth::require()`.

## Rutas a registrar en public/index.php
- `GET /dashboard` → `DashboardController::index`
- `GET /reportes` → `ReportesController::index`
- `GET /reportes/cxc-antiguedad` → `ReportesController::antiguedad`
- `GET /reportes/cxc-antiguedad/csv` → `ReportesController::exportarAntiguedad`
- `GET /reportes/cxp-pendientes` → `ReportesController::cxp`
- `GET /reportes/cxp-pendientes/csv` → `ReportesController::exportarCxp`

## Contenido de las pantallas
- `dashboard/index.php`: 5 tarjetas agregadas (total por pagar CxP, total por cobrar CxC, vencidas CxP, vencidas CxC, pendientes de aprobación) y tabla de las 10 próximas a vencer dentro de `$dias_alerta` (columnas: tipo, documento, tercero, vencimiento, días, saldo). Usa `$resumen`, `$proximas` y `$dias`; no consultes nada adicional. Si no hay próximas, mensaje claro.
- `reportes/index.php`: tarjetas con enlaces a los dos reportes y a sus CSV.
- `reportes/antiguedad.php`: tabla por cliente con columnas Corriente, 1-30, 31-60, 61-90, +90, Total; incluye enlace al CSV. Badge/alerta para saldos vencidos si aplica.
- `reportes/cxp.php`: tabla de CxP pendientes/vencidas por proveedor con documento, emisión, vencimiento, total, saldo, situación y días de atraso; badge `vencida`=danger, `pendiente`=warning; enlace al CSV.

## CSV
- Cabeceras exactas:
  - CxC: `['Cliente', 'Identificador fiscal', 'Corriente', '1-30', '31-60', '61-90', '+90', 'Total']`; archivo `cartera_cxc_antiguedad.csv`.
  - CxP: `['Proveedor', 'Identificador fiscal', 'Documento', 'Emisión', 'Vencimiento', 'Total', 'Saldo', 'Situación', 'Días atraso']`; archivo `cxp_pendientes.csv`.
- En CSV: fechas `dd/mm/aaaa`, montos con 2 decimales y punto decimal, sin símbolo `US$`; en las vistas sí usa `US$` y `dd/mm/aaaa`.
- No imprimas nada antes de llamar a `Csv::descargar()` y no renderices vista en las acciones de exportación.

## Reglas y criterio de aceptación
- `htmlspecialchars`/`View::e()` en toda salida; consultas preparadas ya resueltas en `Reporte`; sin lógica de negocio nueva.
- `php -l` sin errores en los 8 archivos creados/modificados.
- Sin TODO, placeholders ni archivos a medias. Respuesta final: lista de archivos + resumen máximo de 3 líneas.
