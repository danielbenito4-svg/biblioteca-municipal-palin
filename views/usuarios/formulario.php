<a class="back-link" href="<?= e(ruta('usuarios')) ?>">← Volver a usuarios</a>
<div class="page-heading"><div><h1><?= e($title) ?></h1><p>Asigna permisos y credenciales.</p></div></div>
<?php if($errors): ?><div class="alert error" role="alert"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form class="card book-form" method="post" action="<?= e(ruta('usuarios','guardar',$registro['id']?['id'=>$registro['id']]:[])) ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="form-section">
<div class="field"><label for="usuario">Usuario *</label><input id="usuario" name="usuario" pattern="[A-Za-z0-9_.-]{3,50}" maxlength="50" value="<?= e($registro['usuario']) ?>" required></div>
<div class="field"><label for="nombre">Nombre completo *</label><input id="nombre" name="nombre" maxlength="120" value="<?= e($registro['nombre']) ?>" required></div>
<div class="field"><label for="password">Contraseña <?= $registro['id']?'(dejar vacía para conservar)':'*' ?></label>
<input id="password" name="password" type="password" minlength="10" maxlength="128" autocomplete="new-password" <?= $registro['id']?'':'required' ?>></div>
<div class="date-grid"><div class="field"><label for="rol">Rol *</label><select id="rol" name="rol">
<?php foreach(['bibliotecario'=>'Bibliotecario','auxiliar'=>'Auxiliar'] as $val=>$label): ?>
<option value="<?= e($val) ?>" <?= $registro['rol']===$val?'selected':'' ?>><?= e($label) ?></option><?php endforeach ?></select></div>
<div class="field"><label for="activo">Estado *</label><select id="activo" name="activo">
<option value="1" <?= (int)$registro['activo']===1?'selected':'' ?>>Activo</option>
<option value="0" <?= (int)$registro['activo']===0?'selected':'' ?>>Inactivo</option></select></div></div></div>
<div class="form-actions"><a class="button secondary" href="<?= e(ruta('usuarios')) ?>">Cancelar</a>
<button class="button primary" type="submit">Guardar usuario</button></div></form>