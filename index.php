<?php
declare(strict_types=1);
require __DIR__.'/config/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
$routes=['index'=>'GET','crear'=>'GET','editar'=>'GET','ver'=>'GET','guardar'=>'POST','confirmarEliminar'=>'GET','eliminar'=>'POST'];
$action=$_GET['a'] ?? 'index'; $controller=$_GET['c'] ?? 'prestamos';
if (!is_string($action) || !isset($routes[$action]) || $controller !== 'prestamos') {
    http_response_code(404);
    render('error',['title'=>'Página no encontrada','message'=>'La página que buscas no está disponible.']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== $routes[$action]) {
    http_response_code(405); header('Allow: '.$routes[$action]);
    render('error',['title'=>'Operación no permitida','message'=>'Utiliza los botones del sistema para realizar esta operación.']); exit;
}
if ($routes[$action]==='POST' && !csrf_valid()) {
    http_response_code(403);
    render('error',['title'=>'Actualiza el formulario','message'=>'No se pudo verificar la solicitud. Vuelve al listado y abre nuevamente el formulario.']); exit;
}
try {
    $db=Conexion::conectar();
    $handler=new PrestamoController(new Prestamo($db),new Catalogo($db));
    $handler->$action();
} catch (Throwable $e) {
    error_log((string)$e); http_response_code(500);
    render('error',['title'=>'No pudimos completar la operación',
        'message'=>'Inténtalo de nuevo. Si el problema continúa, comprueba que el servidor de base de datos esté iniciado y que la instalación esté completa.']);
}
