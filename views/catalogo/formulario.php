<a class="back-link" href="<?= e(ruta('catalogo')) ?>">← Volver al catálogo</a>
<div class="page-heading"><div><div class="eyebrow">ADMINISTRACIÓN DEL CATÁLOGO</div><h1>Editar libro</h1><p>Actualiza los datos y el total de ejemplares de la biblioteca.</p></div></div>
<?php if ($errors): ?><div class="alert error" role="alert"><strong>No se pudo guardar el libro.</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form class="card book-form" method="post" action="<?= e(ruta('catalogo','guardar',['id'=>$libro['id']])) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="form-section">
        <?php foreach (['isbn'=>['ISBN',20], 'titulo'=>['Título',150], 'autor'=>['Autor',150], 'categoria'=>['Categoría',80]] as $field=>$config): ?>
        <div class="field"><label for="<?= e($field) ?>"><?= e($config[0]) ?> *</label><input id="<?= e($field) ?>" name="<?= e($field) ?>" value="<?= e($libro[$field]) ?>" maxlength="<?= e($config[1]) ?>" required aria-invalid="<?= isset($errors[$field]) ? 'true' : 'false' ?>"></div>
        <?php endforeach ?>
        <div class="date-grid"><div class="field"><label for="anio">Año *</label><input id="anio" type="number" name="anio" value="<?= e($libro['anio']) ?>" min="1" max="9999" required></div><div class="field"><label for="ejemplares_totales">Ejemplares totales *</label><input id="ejemplares_totales" type="number" name="ejemplares_totales" value="<?= e($libro['ejemplares_totales']) ?>" min="0" max="1000000" required><small>El total debe cubrir los ejemplares sin devolver.</small></div></div>
    </div>
    <div class="form-actions"><a class="button secondary" href="<?= e(ruta('catalogo')) ?>">Cancelar</a><button class="button primary" type="submit">Guardar libro</button></div>
</form>
