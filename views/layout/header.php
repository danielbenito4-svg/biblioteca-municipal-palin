<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> · Biblioteca de Palín</title>
    <meta name="theme-color" content="#174c3d">
    <link rel="stylesheet" href="public/css/app.css">
    <script src="public/js/app.js" defer></script>
</head>
<body>
<a class="skip" href="#contenido">Saltar al contenido</a>
<aside class="sidebar">
    <a class="brand" href="<?= e(url()) ?>" aria-label="Biblioteca de Palín, inicio">
        <span class="brand-icon" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M4 6c5-1 9 0 12 3 3-3 7-4 12-3v19c-5-1-9 0-12 3-3-3-7-4-12-3V6Z" stroke="currentColor" stroke-width="1.8"/><path d="M16 9v19" stroke="currentColor" stroke-width="1.8"/></svg></span>
        <span>Biblioteca<span class="brand-place">Municipal de Palín</span></span>
    </a>
    <div class="nav-caption">CIRCULACIÓN</div>
    <nav aria-label="Navegación principal">
        <a class="nav-link <?= ($action ?? 'index') === 'index' ? 'selected' : '' ?>" href="<?= e(url()) ?>"><span aria-hidden="true">▤</span> Préstamos</a>
        <a class="nav-link <?= ($action ?? '') === 'crear' ? 'selected' : '' ?>" href="<?= e(url('crear')) ?>"><span aria-hidden="true">＋</span> Registrar préstamo</a>
    </nav>
    <div class="sidebar-note"><span class="small-label">PALÍN, ESCUINTLA</span><p>Un espacio para leer,<br>aprender y compartir.</p><div class="shelf" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div></div>
</aside>
<div class="app-shell">
    <header class="topbar"><span>Gestión de biblioteca <span class="separator">/</span> <?= e($title) ?></span><span class="today"><?= e(date('d/m/Y')) ?><span class="avatar" aria-hidden="true">BP</span></span></header>
    <main id="contenido">
