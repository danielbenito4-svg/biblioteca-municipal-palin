<div class="page-heading">
    <div><div class="eyebrow">LECTURAS PARA NUESTRA COMUNIDAD</div><h1>Catálogo de libros</h1><p>Consulta ejemplares y busca libros al instante.</p></div>
    <?php if (es_bibliotecario()): ?><a class="button primary" href="<?= e(ruta('catalogo','crear')) ?>">＋ Nuevo libro</a><?php endif ?>
</div>
<?php if ($flash): ?><div class="alert success" role="status"><?= e($flash) ?></div><?php endif ?>
<section class="card catalog-search" data-catalogo data-endpoint="<?= e(ruta('catalogo','buscar')) ?>"
 data-admin="<?= es_bibliotecario()?'1':'0' ?>" data-edit-url="<?= e(ruta('catalogo','editar')) ?>"
 data-login-url="<?= e(ruta('auth','login')) ?>">
    <label for="catalogo-q">Buscar en vivo por título, autor o ISBN (mínimo tres caracteres)</label>
    <div class="search-field"><span aria-hidden="true">⌕</span><input id="catalogo-q" type="search" maxlength="100"
        placeholder="Prueba con Asturias, Popol o DEMO-010…" autocomplete="off"
        aria-describedby="catalogo-estado" aria-controls="catalogo-resultados"></div>
    <p id="catalogo-estado" role="status" aria-live="polite">Escribe al menos 3 caracteres para buscar.</p>
</section>
<div id="catalogo-resultados" class="catalog-results" aria-busy="false"></div>
<section class="card loan-list" id="catalogo-listado">
    <div class="card-top"><h2>Libros registrados</h2><span class="count-badge"><?= count($libros) ?> libros</span></div>
    <div class="table-scroll"><table class="loans-table"><thead><tr>
        <th>ISBN</th><th>Título</th><th>Autor</th><th>Categoría</th><th>Disponibilidad</th><?php if(es_bibliotecario()): ?><th>Acciones</th><?php endif ?>
    </tr></thead><tbody>
    <?php foreach ($libros as $libro): ?><tr>
        <td><?= e($libro['isbn']) ?></td><td class="cell-title"><?= e($libro['titulo']) ?></td>
        <td><?= e($libro['autor']) ?></td><td><?= e($libro['categoria']) ?></td>
        <td><?= e($libro['disponibles']) ?> / <?= e($libro['ejemplares_totales']) ?></td>
        <?php if (es_bibliotecario()): ?><td><div class="row-actions">
            <a href="<?= e(ruta('catalogo','editar',['id'=>$libro['id']])) ?>">Editar</a>
            <a class="danger-link" href="<?= e(ruta('catalogo','confirmarEliminar',['id'=>$libro['id']])) ?>">Eliminar</a>
        </div></td><?php endif ?>
    </tr><?php endforeach ?>
    <?php if (!$libros): ?><tr><td colspan="<?= es_bibliotecario()?6:5 ?>">Sin resultados.</td></tr><?php endif ?>
    </tbody></table></div>
</section>