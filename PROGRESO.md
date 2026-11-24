# PROGRESO
## Hecho
- Fases 0 y 1: MVC, BD con trigger inmutable, seed, login/CSRF/bloqueo/RBAC/bitácora y usuarios.
- Orquestador Big Pickle; workers a=mimo-v2.6-flash-free, b=deepseek (agotado), c=hy4, d=beam.
- T03 modelos Tercero/Factura/Documento: IVA por parámetro, duplicados, saldo, vencidas, MIME real 5 MB.
- T04 alta/edición de facturas y subida/descarga de documentos; prueba SQL con rollback.
- T05 CRUD de terceros (worker-a/b sin cuota 429, worker-c con 1 corrección); T06 listado, filtros y paginación de facturas (worker-c). Fase 2 completa.
- Verificación: php -l 33 archivos OK; HTTP 200 en /, /terceros, /facturas, /facturas/crear, /bitacora; auditor 403 en altas.
## A medias o con error
- Ninguno.
## Falta
- Fase 3: aprobación/rechazo, pagos/cobros con FOR UPDATE, detalle de factura y revisión crítica (3.10).
## Decisiones
- BD local `Contabilidad_express`; credenciales solo en `.env`; bitácora se reinicia con `database/seed.php`.
- Terceros: administrador y contador editan; cualquier usuario autenticado lista.
