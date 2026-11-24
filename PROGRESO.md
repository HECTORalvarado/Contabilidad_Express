# PROGRESO

## Hecho
- Fase 0 completa: MVC, router, PDO, schema con trigger inmutable, seed idempotente, layout; `php -l` OK.
- Fase 1a: login/logout con CSRF, bloqueo tras 5 fallos (15 min), expiración por inactividad (30 min), RBAC (`Auth::require`), `Audit::log` en login/fallos/logout, modelo `Usuario`, vista y rutas.
- Fase 1b: módulo de usuarios y bitácora delegado a worker-a — pendiente de verificación.

## A medias o con error
- Ninguno.

## Falta
- Completar/verificar módulo de usuarios (admin) y pantalla de bitácora; luego Fase 2.

## Decisiones
- Base local `Contabilidad_express`; credenciales solo en `.env`.
- El menú muestra solo rutas existentes; se amplía en cada fase.
- Login con mensaje genérico; al 5.º fallo se bloquea y el contador vuelve a 0.
- Bitácora: administrador y auditor. Usuarios: solo administrador.
