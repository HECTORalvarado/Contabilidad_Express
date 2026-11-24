# PROGRESO

## Hecho
- Fase 0 completa: MVC, router, PDO, schema con trigger inmutable, seed idempotente, layout; `php -l` OK.
- Fase 1 completa: login/logout con CSRF, bloqueo tras 5 fallos (15 min), inactividad 30 min, RBAC (`Auth::require`), `Audit::log` en login/fallos/logout, modelo `Usuario`.
- T02 worker-a (big-pickle): usuarios + bitácora OK; corregido endurecimiento de filtros GET. Probado: alta/edición/estado/desbloqueo/contraseña, 403 a contador y auditor, filtros y paginación.
- Seed re-ejecutado para dejar datos demo limpios (8 usuarios).
- Orquestador ahora Big Pickle (opencode/big-pickle); workers reasignados en PROMPT 3.1 y .opencode/agents; php -l app/ y config/ OK.

- T03 modelos Tercero, Factura y Documento OK (IVA desde parámetros, duplicados, saldo/vencidas, MIME real 5 MB); php -l OK.

- T04 registro/edición de facturas y documentos OK: FacturasController, form, rutas; IVA desde parámetro, duplicados 23505, MIME real; prueba SQL con rollback; php -l OK.

## A medias o con error
- Ninguno.

## Falta
- Fase 2: modelo de facturas (validaciones/duplicados), documentos (subida/descarga), CRUD de terceros y vistas/filtros de facturas.

## Decisiones
- Base local `Contabilidad_express`; credenciales solo en `.env`.
- El menú muestra solo rutas existentes; se amplía en cada fase.
- Login con mensaje genérico; al 5.º fallo se bloquea y el contador vuelve a 0.
- Bitácora: administrador y auditor. Usuarios: solo administrador.
- Bitácora sin borrado de pruebas: se reinicia con `database/seed.php` (TRUNCATE permitido; trigger bloquea UPDATE/DELETE).
