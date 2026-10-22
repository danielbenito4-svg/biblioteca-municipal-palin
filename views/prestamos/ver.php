<?php $status=estado($prestamo); ?>
<a class="back-link" href="<?= e(url()) ?>">← Volver a préstamos</a>
<div class="page-heading"><div><div class="eyebrow">PRÉSTAMO #<?= e(str_pad((string)$prestamo['id'],3,'0',STR_PAD_LEFT)) ?></div><h1><?= e($prestamo['titulo']) ?></h1><p><?= e($prestamo['autor']) ?></p></div><span class="status <?= e($status) ?>"><i aria-hidden="true"></i><?= e(estado_texto($status)) ?></span></div>
<section class="card detail-card"><h2>Información del préstamo</h2><dl class="detail-grid">
    <div><dt>Lector</dt><dd><?= e($prestamo['lector']) ?><small><?= e($prestamo['carne']) ?></small></dd></div><div><dt>ISBN de catálogo</dt><dd><?= e($prestamo['isbn']) ?></dd></div>
    <div><dt>Fecha de préstamo</dt><dd><?= e(fecha($prestamo['fecha_prestamo'])) ?></dd></div><div><dt>Fecha límite</dt><dd><?= e(fecha($prestamo['fecha_limite'])) ?></dd></div>
    <div><dt>Fecha de devolución</dt><dd><?= e(fecha($prestamo['fecha_devolucion'])) ?></dd></div><div><dt><?= $status==='devuelto'?'Multa registrada':'Multa acumulada a hoy' ?></dt><dd>Q <?= number_format(multa_visible($prestamo),2) ?></dd></div>
    <div><dt>Registrado por</dt><dd><?= e($prestamo['operador']) ?></dd></div>
</dl><div class="detail-actions"><a class="button primary" href="<?= e(url('editar',['id'=>$prestamo['id']])) ?>"><?= $status==='devuelto'?'Editar préstamo':'Editar / registrar devolución' ?></a><a class="button secondary" href="<?= e(url()) ?>">Volver al listado</a></div></section>
