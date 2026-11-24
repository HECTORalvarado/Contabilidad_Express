<?php
use App\Core\Csrf;
?>
<div class="card p-4">
    <h2 class="h5 mb-1">Iniciar sesión</h2>
    <p class="text-secondary small mb-4">Acceda con el correo y la contraseña de su cuenta.</p>

    <form method="post" action="/login" novalidate>
        <?= Csrf::field() ?>
        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="email" name="email" maxlength="150"
                   value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   autocomplete="username" required autofocus>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" minlength="8"
                   autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>
    <p class="text-secondary small text-center mt-3 mb-0">Tras 5 intentos fallidos la cuenta se bloquea 15 minutos.</p>
</div>
