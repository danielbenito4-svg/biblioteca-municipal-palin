<div class="page-heading"><div><div class="eyebrow">CIRCULACIÓN · BIBLIOTECA MUNICIPAL</div><h1>Préstamos</h1><p>Consulta los libros prestados y mantén al día las devoluciones.</p></div><a class="button primary" href="<?= e(url('crear')) ?>"><span aria-hidden="true">＋</span> Nuevo préstamo</a></div>
<?php if ($flash): ?><div class="alert success" role="status"><?= e($flash) ?></div><?php endif ?>
<section class="stats" aria-label="Resumen de préstamos">
    <article class="stat"><div class="stat-label">Total registrados <span aria-hidden="true">▤</span></div><strong><?= e($resumen['total']) ?></strong><span>Préstamos en el historial</span></article>
    <article class="stat"><div class="stat-label">Sin devolver <span aria-hidden="true">↗</span></div><strong><?= e($resumen['activos']) ?></strong><span>Ejemplares fuera de biblioteca</span></article>
    <article class="stat warning"><div class="stat-label">Vencidos <span aria-hidden="true">◷</span></div><strong><?= e($resumen['vencidos']) ?></strong><span>Requieren una devolución</span></article>
    <article class="stat"><div class="stat-label">Devueltos <span aria-hidden="true">✓</span></div><strong><?= e($resumen['devueltos']) ?></strong><span>Préstamos finalizados</span></article>
</section>
<section class="card loan-list">
    <div class="card-top"><div><h2>Registro de préstamos</h2><p>Busca por libro, lector, carné o ISBN.</p></div><span class="count-badge"><?= count($prestamos) ?> resultados</span></div>
    <form class="filters" method="get" action="index.php" role="search">
        <input type="hidden" name="c" value="prestamos"><input type="hidden" name="a" value="index">
        <div class="search-field"><label class="visually-hidden" for="q">Buscar préstamos</label><span aria-hidden="true">⌕</span><input id="q" type="search" name="q" maxlength="100" value="<?= e($busqueda) ?>" placeholder="Buscar libro, lector, carné o ISBN…"></div>
        <div><label class="visually-hidden" for="estado">Filtrar por estado</label><select name="estado" id="estado"><option value="">Todos los estados</option value="activo" <?= $filtroEstado==='activo'?'selected':'' ?>>Sin devolver</option><option value="vencido" <?= $filtroEstado==='vencido'?'selected':'' ?>>Vencidos</option><option value="devuelto" <?= $filtroEstado==='devuelto'?'selected':'' ?>>Devueltos</option></select></div>
        <button class="button secondary" type="submit">Buscar</button><?php if ($busqueda!=='' || $filtroEstado!==''): ?><a class="text-link" href="<?= e(url()) ?>">Limpiar</a><?php endif ?>
    </form>
    <div class="table-scroll"><table class="loans-table"><thead><tr><th>N.º</th><th>Libro</th><th>Lector</th><th>Plazo</th><th>Estado</th><th>Multa</th><th class="actions-head">Acciones</th></tr></thead><tbody>
    <?php foreach ($prestamos as $p): $status=estado($p); ?>
        <tr id="prestamo-<?= e($p['id']) ?>" <?= (string)($_GET['destacado'] ?? '')===(string)$p['id']?'class="highlight"':'' ?>>
            <td class="record-id">#<?= e(str_pad((string)$p['id'],3,'0',STR_PAD_LEFT)) ?></td>
            <td><span class="cell-title"><?= e($p['titulo']) ?></span><small><?= e($p['autor']) ?></small></td>
            <td><span class="cell-title"><?= e($p['lector']) ?></span><small><?= e($p['carne']) ?></small></td>
            <td class="date-cell"><?= e(fecha($p['fecha_prestamo'])) ?><small>Vence <?= e(fecha($p['fecha_limite'])) ?></small></td>
            <td><span class="status <?= e($status) ?>"><i aria-hidden="true"></i><?= e(estado_texto($status)) ?></span></td>
            <td class="money">Q <?= number_format(multa_visible($p),2) ?></td>
            <td><div class="row-actions"><a href="<?= e(url('ver',['id'=>$p['id']])) ?>" aria-label="Ver préstamo <?= e($p['id']) ?>">Ver</a><a href="<?= e(url('editar',['id'=>$p['id']])) ?>" aria-label="Editar préstamo <?= e($p['id']) ?>">Editar</a><a class="danger-link" href="<?= e(url('confirmarEliminar',['id'=>$p['id']])) ?>" aria-label="Eliminar préstamo <?= e($p['id']) ?>">Eliminar</a></div></td>
        </tr>
    <?php endforeach ?>
    <?php if (!$prestamos): ?><tr><td colspan="7"><div class="empty-state"><h3>No hay préstamos que coincidan</h3><p>Prueba con otro nombre o cambia los filtros.</p><a href="<?= e(url()) ?>">Ver todos los préstamos</a></div></td></tr><?php endif ?>
    </tbody></table></div>
    <div class="table-foot"><span><?= count($prestamos) ?> de <?= e($resumen['total']) ?> préstamos</span><span>Multa por atraso: Q 2.00 por día</span></div>
</section>
