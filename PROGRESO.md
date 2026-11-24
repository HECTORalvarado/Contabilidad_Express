# PROGRESO
## Hecho
- Fases 0-2: MVC, BD con trigger inmutable, seed, login/CSRF/bloqueo/RBAC/bitácora, usuarios; Tercero/Factura/Documento (IVA, duplicados, saldo, vencidas, MIME 5 MB); alta/edición, documentos y listado con filtros/paginación.
- Orquestador Big Pickle; workers a=flint-40b-free, b=iceman-free, c=hy4-flash-free, d=beam-free; reservas nemotron→mimo→deepseek (429: solo reintento); ping a/b confirmado. Verificación previa: php -l 33 archivos OK; HTTP 200 en rutas principales.
- Fase 3 (yo + worker-c/hy4): aprobar/rechazar y pagos con FOR UPDATE y reglas 1-10; controlador/rutas y vista show.php (1 corrección: import de Csrf); php -l OK.
- Revisión crítica 3.10: OMITIDA por indisponibilidad del modelo revisor; no se ejecutó.
## A medias o con error
- Ninguno. Pendiente: la revisión crítica de Fase 5 debe cubrir también aprobación y pagos (archivos de la Fase 3).
## Falta
- Fase 4 (dashboard + reportes CSV) y Fase 5 (permisos + revisión crítica + README).
## Decisiones
- BD local `Contabilidad_express`; credenciales solo en `.env`; bitácora se reinicia con `database/seed.php`.
- Terceros: administrador y contador editan; cualquier usuario autenticado lista. Aprueban gerente/contador/admin (nunca el registrador); pagos/cobros solo encargado_cxp/cxc.
- Dashboard: administrador, gerente, tesorero, auditor (solo lectura). Reportes: administrador, gerente, contador, tesorero, auditor (solo lectura).
- Cartera: solo facturas activas aprobadas/parciales con saldo > 0; excluye pendientes y rechazadas. CSV: BOM UTF-8, fputcsv y apóstrofo si celda empieza = + - @.
