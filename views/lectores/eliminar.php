<a class="back-link" href="<?= e(ruta('lectores')) ?>">← Volver a lectores</a>
<div class="page-heading"><div><h1>Eliminar o desactivar lector</h1></div></div>
<section class="card book-form"><h2><?= e($lector['nombre']) ?></h2>
<p>Si tiene préstamos históricos, se desactivará para conservar las relaciones.</p>
<?php if($errors): ?><div class="alert error"><?php foreach($errors as $error): ?><p><?= e($error) ?></p><?php endforeach ?></div><?php endif ?>
<form method="post" action="<?= e(ruta('lectores','eliminar',['id'=>$lector['id']])) ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-actions">
<a class="button secondary" href="<?= e(ruta('lectores')) ?>">Cancelar</a>
<button class="button primary" type="submit">Confirmar</button></div></form></section>