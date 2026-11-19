<a class="back-link" href="<?= e(ruta('usuarios')) ?>">← Volver a usuarios</a>
<div class="page-heading"><div><h1>Desactivar usuario</h1></div></div>
<section class="card book-form"><h2><?= e($registro['nombre']??'Usuario inexistente') ?></h2>
<p>La cuenta dejará de poder iniciar sesión; su historial de préstamos se conserva.</p>
<?php if($errors): ?><div class="alert error"><?php foreach($errors as $error): ?><p><?= e($error) ?></p><?php endforeach ?></div><?php endif ?>
<form method="post" action="<?= e(ruta('usuarios','desactivar',['id'=>$registro['id']])) ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-actions">
<a class="button secondary" href="<?= e(ruta('usuarios')) ?>">Cancelar</a>
<button class="button primary" type="submit">Desactivar cuenta</button></div></form></section>