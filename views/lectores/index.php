<div class="page-heading"><div><div class="eyebrow">REGISTRO DE COMUNIDAD</div><h1>Lectores</h1>
<p>Consulta estudiantes y vecinos inscritos en la biblioteca.</p></div>
<?php if (es_bibliotecario()): ?><a class="button primary" href="<?= e(ruta('lectores','crear')) ?>">＋ Nuevo lector</a><?php endif ?></div>
<?php if ($flash): ?><div class="alert success" role="status"><?= e($flash) ?></div><?php endif ?>
<section class="card loan-list"><div class="card-top"><h2>Lectores registrados</h2><span class="count-badge"><?= count($lectores) ?> resultados</span></div>
<form class="filters" method="get" action="index.php">
<input type="hidden" name="c" value="lectores"><input type="hidden" name="a" value="index">
<div class="search-field"><label class="visually-hidden" for="q">Buscar nombre o carné</label>
<input id="q" name="q" maxlength="100" value="<?= e($buscar) ?>" placeholder="Nombre o carné"></div>
<label for="estado" class="visually-hidden">Estado</label><select id="estado" name="estado">
<option value="">Todos</option><option value="activo" <?= $estado==='activo'?'selected':'' ?>>Activos</option>
<option value="inactivo" <?= $estado==='inactivo'?'selected':'' ?>>Inactivos</option></select>
<button class="button secondary" type="submit">Buscar</button></form>
<div class="table-scroll"><table class="loans-table"><thead><tr><th>Carné</th><th>Nombre</th><th>Teléfono</th>
<th>Tipo</th><th>Estado</th><th>Historial</th><?php if (es_bibliotecario()): ?><th>Acciones</th><?php endif ?></tr></thead><tbody>
<?php foreach ($lectores as $r): ?><tr>
<td><?= e($r['carne']) ?></td><td class="cell-title"><?= e($r['nombre']) ?></td>
<td><?= e($r['telefono']?:'Sin teléfono') ?></td><td><?= e($r['tipo']) ?></td>
<td><?= e($r['estado']) ?></td><td><?= e($r['total_prestamos']) ?> préstamos</td>
<?php if (es_bibliotecario()): ?><td><div class="row-actions">
<a href="<?= e(ruta('lectores','editar',['id'=>$r['id']])) ?>">Editar</a>
<a class="danger-link" href="<?= e(ruta('lectores','confirmarEliminar',['id'=>$r['id']])) ?>">Eliminar</a>
</div></td><?php endif ?></tr><?php endforeach ?>
<?php if (!$lectores): ?><tr><td colspan="<?= es_bibliotecario()?7:6 ?>">No se encontraron lectores.</td></tr><?php endif ?>
</tbody></table></div></section>