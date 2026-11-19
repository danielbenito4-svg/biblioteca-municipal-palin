<div class="page-heading"><div><div class="eyebrow">INFORMACIÓN PARA DECISIONES</div><h1>Reportes de biblioteca</h1>
<p>Informes actualizados directamente desde préstamos, libros y lectores.</p></div></div>
<div class="card loan-list">
<form class="filters" method="get" action="index.php">
<input type="hidden" name="c" value="reportes"><label for="tipo">Reporte</label><select name="tipo" id="tipo">
<option value="vencidos" <?= $tipo==='vencidos'?'selected':'' ?>>Préstamos vencidos a una fecha</option>
<option value="populares" <?= $tipo==='populares'?'selected':'' ?>>10 libros más prestados del mes</option></select>
<label for="hasta">Fecha de corte</label><input type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>" max="<?= e(date('Y-m-d')) ?>">
<label for="mes">Mes</label><input type="month" id="mes" name="mes" value="<?= e($mes) ?>">
<button class="button primary" type="submit">Generar reporte</button></form></div>
<?php if($errors): ?><div class="alert error" role="alert"><?php foreach($errors as $error): ?><p><?= e($error) ?></p><?php endforeach ?></div><?php endif ?>
<section class="card loan-list">
<div class="card-top"><h2><?= $tipo==='vencidos'?'Vencidos a '.e($hasta):'Libros destacados de '.e($mes) ?></h2>
<span class="count-badge"><?= count($datos) ?> registros</span></div>
<div class="table-scroll"><table class="loans-table"><thead>
<?php if ($tipo==='vencidos'): ?><tr><th>#</th><th>Libro</th><th>Lector</th><th>Vencimiento</th><th>Días</th><th>Multa estimada</th></tr>
<?php else: ?><tr><th>#</th><th>Libro</th><th>Autor</th><th>Préstamos</th><th>Lectores diferentes</th></tr><?php endif ?>
</thead><tbody>
<?php foreach($datos as $n=>$d): ?>
<?php if($tipo==='vencidos'): ?><tr><td data-label="N.º"><?= e($d['id']) ?></td><td data-label="Libro"><?= e($d['titulo']) ?></td>
<td data-label="Lector"><?= e($d['lector']) ?> (<?= e($d['carne']) ?>)</td><td data-label="Vencimiento"><?= e($d['fecha_limite']) ?></td>
<td data-label="Días"><?= e($d['dias_atraso']) ?></td><td data-label="Multa estimada">Q <?= number_format((float)$d['multa_estimada'],2) ?></td></tr>
<?php else: ?><tr><td data-label="Puesto"><?= e($n+1) ?></td><td data-label="Libro"><?= e($d['titulo']) ?></td><td data-label="Autor"><?= e($d['autor']) ?></td>
<td data-label="Préstamos"><?= e($d['prestamos_mes']) ?></td><td data-label="Lectores diferentes"><?= e($d['lectores_distintos']) ?></td></tr><?php endif ?>
<?php endforeach ?>
<?php if(!$datos): ?><tr><td colspan="<?= $tipo==='vencidos'?6:5 ?>">No hay registros para el filtro seleccionado.</td></tr><?php endif ?>
</tbody></table></div></section>