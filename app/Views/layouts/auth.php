<?php
use App\Core\Flash;
/** @var string $appName */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Iniciar sesión', ENT_QUOTES, 'UTF-8') ?> · Contabilidad Express</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: #13293d; display: flex; align-items: center; }
        .card { border: 0; box-shadow: 0 10px 30px rgba(0, 0, 0, .25); }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-sm-10 col-md-7 col-lg-5">
            <div class="text-center text-white mb-4">
                <i class="bi bi-calculator display-5"></i>
                <h1 class="h4 mt-2 mb-0">Contabilidad Express</h1>
                <p class="small opacity-75 mb-0"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?= Flash::render() ?>
            <?= $content ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
