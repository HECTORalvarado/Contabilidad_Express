<?php
/** @var string $content */
/** @var string|null $title */
/** @var array|null $usuario */
/** @var string $appName */
use App\Core\Flash;

$title = $title ?? $appName;
$rol = $usuario['rol_nombre'] ?? null;
$esAdmin = $rol === 'administrador';
$puedeVerTodo = in_array($rol, ['administrador', 'gerente', 'contador', 'tesorero', 'auditor'], true);
$esOperativo = in_array($rol, ['administrador', 'gerente', 'contador', 'auxiliar'], true);
$verCxp = $esOperativo || in_array($rol, ['encargado_cxp', 'auditor', 'tesorero'], true);
$verCxc = $esOperativo || in_array($rol, ['encargado_cxc', 'auditor', 'tesorero'], true);
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> · Contabilidad Express</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .sidebar { width: 240px; min-height: 100vh; background: #13293d; }
        .sidebar .nav-link { color: #c7d4e2; border-radius: .5rem; padding: .45rem .75rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #1d4e6f; color: #fff; }
        .sidebar .brand { color: #fff; font-weight: 600; letter-spacing: .3px; }
        .content { min-width: 0; flex: 1; }
        .card { border: 0; box-shadow: 0 1px 3px rgba(16, 24, 40, .08); }
        .table > :not(caption) > * > * { padding: .6rem .5rem; }
        .badge-estado { font-size: .78rem; }
        @media (max-width: 991.98px) {
            .sidebar { width: 100%; min-height: auto; }
            .layout { flex-direction: column !important; }
        }
    </style>
</head>
<body>
<div class="layout d-flex">
    <aside class="sidebar p-3">
        <div class="brand d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-calculator fs-4"></i>
            <span>Contabilidad<br><small class="fw-normal opacity-75">Express</small></span>
        </div>
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item"><a class="nav-link <?= ($_SERVER['REQUEST_URI'] ?? '/') === '/' ? 'active' : '' ?>" href="/"><i class="bi bi-house me-2"></i>Inicio</a></li>
            <?php if ($usuario): ?>
                <?php if ($esAdmin): ?>
                    <li class="nav-item"><a class="nav-link" href="/usuarios"><i class="bi bi-person-gear me-2"></i>Usuarios</a></li>
                <?php endif; ?>
                <?php if ($esAdmin || $rol === 'auditor'): ?>
                    <li class="nav-item"><a class="nav-link" href="/bitacora"><i class="bi bi-journal-text me-2"></i>Bitácora</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </aside>

    <div class="content d-flex flex-column">
        <header class="bg-white border-bottom px-4 py-2 d-flex justify-content-between align-items-center">
            <span class="text-secondary small"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($usuario): ?>
                <div class="d-flex align-items-center gap-3">
                    <span class="small text-end">
                        <strong><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <span class="text-secondary"><?= htmlspecialchars(str_replace('_', ' ', (string) $rol), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                    <form method="post" action="/logout" class="m-0">
                        <?= \App\Core\Csrf::field() ?>
                        <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Salir</button>
                    </form>
                </div>
            <?php else: ?>
                <span class="small text-secondary"><i class="bi bi-person me-1"></i>Sesión no iniciada</span>
            <?php endif; ?>
        </header>

        <main class="p-4 flex-grow-1">
            <?= Flash::render() ?>
            <?= $content ?>
        </main>

        <footer class="text-center text-secondary small py-3">
            Contabilidad Express, S.A. de C.V. · Montos en USD · <?= date('Y') ?>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
