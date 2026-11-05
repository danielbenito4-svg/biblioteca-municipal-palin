<div class="page-heading"><div><div class="eyebrow">LECTURAS PARA NUESTRA COMUNIDAD</div><h1>Catálogo de libros</h1><p>Encuentra tu próxima lectura y consulta los ejemplares disponibles.</p></div><span class="count-badge">Consulta en vivo</span></div>
<?php if ($flash): ?><div class="alert success" role="status"><?= e($flash) ?></div><?php endif ?>
<section class="card catalog-search" data-catalogo data-endpoint="<?= e(ruta('catalogo','buscar')) ?>" data-admin="<?= es_bibliotecario() ? '1' : '0' ?>" data-edit-url="<?= e(ruta('catalogo','editar')) ?>" data-login-url="<?= e(ruta('auth','login')) ?>">
    <label for="catalogo-q">Buscar por título, autor o ISBN</label>
    <div class="search-field"><span aria-hidden="true">⌕</span><input id="catalogo-q" type="search" maxlength="100" placeholder="Prueba con Asturias, Popol o DEMO-010…" autocomplete="off" aria-describedby="catalogo-estado" aria-controls="catalogo-resultados"></div>
    <p id="catalogo-estado" role="status" aria-live="polite">Escribe al menos 3 caracteres para buscar.</p>
    <noscript><div class="alert error">Activa JavaScript en el navegador para usar la búsqueda en vivo.</div></noscript>
</section>
<div id="catalogo-resultados" class="catalog-results" aria-busy="false"></div>
<div id="catalogo-vacio" class="catalog-empty"><span aria-hidden="true">⌕</span><h2>Una biblioteca por descubrir</h2><p>Busca un libro y consulta su disponibilidad al instante.</p></div>
