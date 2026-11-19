<div class="page-heading"><div><div class="eyebrow">CONTROL DE ACCESO</div><h1>Usuarios del sistema</h1>
<p>Solo el bibliotecario administra cuentas y roles.</p></div><a class="button primary" href="<?= e(ruta('usuarios','crear')) ?>">＋ Nuevo usuario</a></div>
<?php if($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif ?>
<section class="card loan-list"><div class="card-top"><h2>Personal registrado</h2><span class="count-badge"><?= count($usuarios) ?></span></div>
<form class="filters" action="index.php" method="get"><input type="hidden" name="c" value="usuarios">
<label class="visually-hidden" for="q">Buscar usuario</label><input id="q" name="q" value="<?= e($buscar) ?>" maxlength="100" placeholder="Buscar usuario">
<button type="submit" class="button secondary">Buscar</button></form>
<div class="table-scroll"><table class="loans-table"><thead><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($usuarios as $u): ?><tr><td data-label="Usuario"><?= e($u['usuario']) ?></td><td class="cell-title" data-label="Nombre"><?= e($u['nombre']) ?></td>
<td data-label="Rol"><?= e($u['rol']) ?></td><td data-label="Estado"><?= $u['activo']?'Activo':'Inactivo' ?></td><td data-label="Acciones"><div class="row-actions">
<a href="<?= e(ruta('usuarios','editar',['id'=>$u['id']])) ?>">Editar</a>
<?php if($u['activo'] && (int)$u['id']!==(int)usuario_actual()['id']): ?>
<a class="danger-link" href="<?= e(ruta('usuarios','confirmarDesactivar',['id'=>$u['id']])) ?>">Desactivar</a><?php endif ?>
</div></td></tr><?php endforeach ?>
<?php if(!$usuarios): ?><tr><td colspan="5">Sin resultados.</td></tr><?php endif ?>
</tbody></table></div></section>