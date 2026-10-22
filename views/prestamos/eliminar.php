<a class="back-link" href="<?= e(url()) ?>">← Volver a préstamos</a>
<section class="card delete-card"><div class="delete-symbol" aria-hidden="true">!</div><div class="eyebrow">CONFIRMACIÓN</div><h1>¿Eliminar este préstamo?</h1><p>Se eliminará el registro #<?= e($prestamo['id']) ?> del historial. Si el ejemplar sigue prestado, volverá a contarse como disponible.</p>
    <dl class="delete-summary"><div><dt>Libro</dt><dd><?= e($prestamo['titulo']) ?></dd></div><div><dt>Lector</dt><dd><?= e($prestamo['lector']) ?></dd></div><div><dt>Fecha de préstamo</dt><dd><?= e(fecha($prestamo['fecha_prestamo'])) ?></dd></div></dl>
    <form method="post" action="<?= e(url('eliminar',['id'=>$prestamo['id']])) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-actions"><a class="button secondary" href="<?= e(url()) ?>">Cancelar</a><button class="button danger" type="submit">Sí, eliminar préstamo</button></div></form>
</section>
