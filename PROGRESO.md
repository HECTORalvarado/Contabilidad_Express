# PROGRESO
## Hecho
- Fases 0-2: MVC, BD con trigger inmutable, seed, login/CSRF/bloqueo/RBAC/bitácora, usuarios; Tercero/Factura/Documento (IVA, duplicados, saldo, vencidas, MIME 5 MB); alta/edición, documentos y listado con filtros/paginación.
- Orquestador Big Pickle; workers a=flint-40b-free, b=iceman-free, c=hy4-flash-free, d=beam-free; reservas nemotron→mimo→deepseek (429: solo reintento); ping a/b confirmado. Verificación previa: php -l y HTTP 200 en rutas principales.
- Fase 3 (yo + worker-c/hy4): aprobar/rechazar y pagos con FOR UPDATE y reglas 1-10; controlador/rutas y vista show.php (1 corrección: import de Csrf); php -l OK.
- Revisión crítica 3.10: OMITIDA por indisponibilidad del modelo revisor; no se ejecutó.
- Fase 4: SQL agregada de `Reporte` escrita y probada; dashboard y reportes CSV (worker-c/hy4) verificados: php -l 45/45, HTTP 200/403 por rol (contador: password demo local distinta), CSV con BOM y escape de fórmulas.
## A medias o con error
- Ninguno. Pendiente: la revisión crítica de Fase 5 debe cubrir también aprobación y pagos (archivos de la Fase 3).
## Falta
- Fase 5 (permisos por rol + revisión crítica + README corto).
## Decisiones
- BD local `Contabilidad_express`; credenciales solo en `.env`; bitácora se reinicia con `database/seed.php`.
- Terceros: administrador y contador editan; cualquier usuario autenticado lista. Aprueban gerente/contador/admin (nunca el registrador); pagos/cobros solo encargado_cxp/cxc.
- Dashboard: admin/gerente/tesorero/auditor; reportes: +contador; ambos solo lectura. Cartera: activas aprobadas/parciales con saldo>0. CSV: BOM UTF-8, fputcsv y apóstrofo si celda empieza = + - @.
