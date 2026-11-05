<section class="login-card card">
    <div class="login-story">
        <div class="eyebrow">PALÍN · ESCUINTLA</div>
        <div class="login-monogram" aria-hidden="true">B<span>P</span></div>
        <h1>Las historias<br>nos acercan.</h1>
        <p>Biblioteca Municipal de Palín</p>
        <div class="login-story-foot">Un espacio para leer, aprender y compartir.</div>
    </div>
    <div class="login-form-wrap">
        <div class="eyebrow">GESTIÓN DE BIBLIOTECA</div>
        <h2>Bienvenido de nuevo</h2>
        <p>Ingresa con tu cuenta para continuar.</p>
        <?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif ?>
        <form method="post" action="<?= e(ruta('auth','ingresar')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="field"><label for="usuario">Usuario</label><input id="usuario" name="usuario" value="<?= e($username) ?>" required maxlength="50" autocomplete="username" autofocus></div>
            <div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" required maxlength="1024" autocomplete="current-password"></div>
            <button class="button primary login-submit" type="submit">Iniciar sesión <span aria-hidden="true">→</span></button>
        </form>
        <p class="login-help">Si necesitas acceso, consulta al bibliotecario.</p>
    </div>
</section>
