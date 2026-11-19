<a class="back-link" href="<?= e(ruta('catalogo')) ?>">← Volver al catálogo</a>
<div class="page-heading"><div><h1>Eliminar libro</h1><p>Confirma esta operación antes de continuar.</p></div></div>
<section class="card book-form"><h2><?= e($libro['titulo']) ?></h2><p>ISBN: <?= e($libro['isbn']) ?></p>
<p>Los libros con préstamos registrados no pueden eliminarse para conservar el historial.</p>
<?php if ($errors): ?><div class="alert error" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach ?></div><?php endif ?>
<form method="post" action="<?= e(ruta('catalogo','eliminar',['id'=>$libro['id']])) ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="form-actions"><a class="button secondary" href="<?= e(ruta('catalogo')) ?>">Cancelar</a>
<button class="button primary" type="submit">Confirmar eliminación</button></div></form></section>