<a class="back-link" href="<?= e(ruta('lectores')) ?>">← Volver a lectores</a>
<div class="page-heading"><div><h1><?= e($title) ?></h1><p>Datos del lector.</p></div></div>
<?php if($errors): ?><div class="alert error" role="alert"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form class="card book-form" method="post" action="<?= e(ruta('lectores','guardar',$lector['id']?['id'=>$lector['id']]:[])) ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="form-section">
<div class="field"><label for="carne">Carné *</label><input id="carne" name="carne" maxlength="20" pattern="[A-Za-z0-9-]{2,20}" value="<?= e($lector['carne']) ?>" required></div>
<div class="field"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" maxlength="120" value="<?= e($lector['nombre']) ?>" required></div>
<div class="field"><label for="telefono">Teléfono (opcional)</label><input id="telefono" name="telefono" maxlength="9" pattern="[0-9]{4}-?[0-9]{4}" value="<?= e($lector['telefono']??'') ?>"></div>
<div class="date-grid"><div class="field"><label for="tipo">Tipo *</label><select id="tipo" name="tipo" required>
<?php foreach(['estudiante'=>'Estudiante','vecino'=>'Vecino'] as $val=>$label): ?>
<option value="<?= e($val) ?>" <?= $lector['tipo']===$val?'selected':'' ?>><?= e($label) ?></option><?php endforeach ?></select></div>
<div class="field"><label for="estado">Estado *</label><select id="estado" name="estado" required>
<?php foreach(['activo'=>'Activo','inactivo'=>'Inactivo'] as $val=>$label): ?>
<option value="<?= e($val) ?>" <?= $lector['estado']===$val?'selected':'' ?>><?= e($label) ?></option><?php endforeach ?></select></div></div></div>
<div class="form-actions"><a class="button secondary" href="<?= e(ruta('lectores')) ?>">Cancelar</a>
<button class="button primary" type="submit">Guardar lector</button></div></form>