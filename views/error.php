<section class="empty-page card">
    <div class="eyebrow">BIBLIOTECA DE PALÍN</div>
    <h1><?= e($title) ?></h1><p><?= e($message) ?></p>
    <a class="button primary" href="<?= e(usuario_actual() ? url() : ruta('auth','login')) ?>"><?= usuario_actual() ? 'Volver a préstamos' : 'Iniciar sesión' ?></a>
</section>
