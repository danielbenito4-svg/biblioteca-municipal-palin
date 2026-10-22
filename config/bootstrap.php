<?php
declare(strict_types=1);
define('APP_ROOT', dirname(__DIR__));
date_default_timezone_set('America/Guatemala');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/php-error.log');
spl_autoload_register(function (string $class): void {
    foreach (['models', 'controllers', 'config'] as $directory) {
        $path = APP_ROOT . '/' . $directory . '/' . $class . '.php';
        if (is_file($path)) { require $path; return; }
    }
});
if (PHP_SAPI !== 'cli') {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'use_strict_mode' => true]);
}
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function url(string $action = 'index', array $params = []): string
{
    return 'index.php?' . http_build_query(['c' => 'prestamos', 'a' => $action] + $params);
}
function render(string $view, array $viewData = []): void
{
    extract($viewData, EXTR_SKIP);
    $action = is_string($_GET['a'] ?? null) ? $_GET['a'] : 'index';
    require APP_ROOT . '/views/layout/header.php';
    require APP_ROOT . '/views/' . $view . '.php';
    require APP_ROOT . '/views/layout/footer.php';
}
function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf_token'];
}
function csrf_valid(): bool
{
    $token = $_POST['csrf_token'] ?? null;
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
function flash(string $message): void { $_SESSION['flash'] = $message; }
function take_flash(): ?string
{
    $message = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $message;
}
function fecha(?string $date): string
{
    return $date ? (new DateTimeImmutable($date))->format('d/m/Y') : 'Pendiente';
}
function estado(array $prestamo): string
{
    if ($prestamo['fecha_devolucion'] !== null) { return 'devuelto'; }
    return $prestamo['fecha_limite'] < date('Y-m-d') ? 'vencido' : 'activo';
}
function estado_texto(string $status): string
{
    return ['activo' => 'En curso', 'vencido' => 'Vencido', 'devuelto' => 'Devuelto'][$status];
}
function multa_visible(array $prestamo): float
{
    return $prestamo['fecha_devolucion'] !== null ? (float) $prestamo['multa']
        : Prestamo::calcularMulta($prestamo['fecha_limite'], date('Y-m-d'));
}
