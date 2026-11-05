<?php
declare(strict_types=1);
require __DIR__.'/config/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

// Método, roles autorizados, respuesta JSON. Declarar aquí cada ruta nueva.
$both = ['bibliotecario', 'auxiliar'];
$admin = ['bibliotecario'];
$routes = [
    'auth' => ['login' => ['GET', [], false], 'ingresar' => ['POST', [], false], 'salir' => ['POST', $both, false]],
    'prestamos' => ['index' => ['GET', $both, false], 'crear' => ['GET', $both, false],
        'editar' => ['GET', $both, false], 'ver' => ['GET', $both, false], 'guardar' => ['POST', $both, false],
        'confirmarEliminar' => ['GET', $admin, false], 'eliminar' => ['POST', $admin, false]],
    'catalogo' => ['index' => ['GET', $both, false], 'buscar' => ['GET', $both, true],
        'editar' => ['GET', $admin, false], 'guardar' => ['POST', $admin, false]],
];
$controller = $_GET['c'] ?? 'prestamos';
$action = $_GET['a'] ?? ($controller === 'auth' ? 'login' : 'index');
if (!is_string($controller) || !is_string($action) || !isset($routes[$controller][$action])) {
    http_response_code(404);
    render('error', ['title'=>'Página no encontrada', 'message'=>'La página que buscas no está disponible.']); exit;
}
[$method, $roles, $isJson] = $routes[$controller][$action];
function fail_request(int $status, string $title, string $message): void
{
    global $isJson;
    if ($isJson) { json_response(['error'=>$message], $status); }
    else { http_response_code($status); render('error', compact('title', 'message')); }
}
if ($_SERVER['REQUEST_METHOD'] !== $method) {
    header('Allow: '.$method);
    fail_request(405, 'Operación no permitida', 'Utiliza los botones del sistema para realizar esta operación.'); exit;
}
try {
    $db = Conexion::conectar();
    $usuarios = new Usuario($db);
    $currentUser = Autenticacion::actual($usuarios);
    if ($roles && !$currentUser) {
        if ($isJson) { json_response(['error'=>'Inicia sesión para consultar el catálogo.'], 401); }
        else { header('Location: '.ruta('auth', 'login'), true, 303); }
        exit;
    }
    if ($roles && !in_array($currentUser['rol'], $roles, true)) {
        fail_request(403, 'Acceso restringido', 'Tu rol no tiene permiso para realizar esta operación.'); exit;
    }
    if ($method === 'POST' && !csrf_valid()) {
        fail_request(403, 'Actualiza el formulario', 'No se pudo verificar la solicitud. Abre nuevamente el formulario.'); exit;
    }
    $handler = match ($controller) {
        'auth' => new AuthController($usuarios),
        'catalogo' => new CatalogoController(new Catalogo($db)),
        'prestamos' => new PrestamoController(new Prestamo($db), new Catalogo($db)),
    };
    $handler->$action();
} catch (Throwable $e) {
    error_log((string)$e);
    fail_request(500, 'No pudimos completar la operación',
        'Inténtalo de nuevo. Si el problema continúa, comprueba que el servidor de base de datos esté iniciado y que la instalación esté completa.');
}
