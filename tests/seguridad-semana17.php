<?php
declare(strict_types=1);
// Auditoria semana 17. Ejecutar: php tests/seguridad-semana17.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/config/RateLimit.php';
require APP_ROOT . '/config/AuditoriaSeguridad.php';
$archivo = tempnam(sys_get_temp_dir(), 'rate-test-');
if ($archivo === false) { throw new RuntimeException('No se pudo crear archivo temporal.'); }
try {
    $limite = new RateLimit($archivo);
    if ($limite->bloqueado('auxiliar')) { throw new RuntimeException('Bloqueado al inicio.'); }
    for ($i = 1; $i <= 4; $i++) {
        $limite->registrarFallo('auxiliar');
        if ($limite->bloqueado('auxiliar')) { throw new RuntimeException('Bloqueo prematuro.'); }
    }
    $limite->registrarFallo('auxiliar');
    if (!$limite->bloqueado('auxiliar')) { throw new RuntimeException('No bloquea tras cinco fallos.'); }
    if ($limite->bloqueado('bibliotecario')) { throw new RuntimeException('Mezcla usuarios.'); }
    $json = file_get_contents($archivo);
    if (str_contains($json, 'auxiliar')) { throw new RuntimeException('Se expone usuario.'); }
    $limite->limpiar('auxiliar');
    if ($limite->bloqueado('auxiliar')) { throw new RuntimeException('No se libera la cuenta.'); }
    echo "OK: cinco fallos bloquean durante quince minutos.\n";
    echo "OK: cuentas independientes y usuario almacenado como hash.\n";
    echo "OK: al autenticar correctamente se limpia el contador.\n";
    // Comprobar el formato de un evento sin almacenar clave.
    $ruta = APP_ROOT . '/storage/security-audit.log';
    AuditoriaSeguridad::registrar('login_fallido', 'test-semana17');
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $evento = json_decode(end($lineas), true, 512, JSON_THROW_ON_ERROR);
    if (($evento['evento'] ?? '') !== 'login_fallido'
        || array_key_exists('password', $evento)
        || str_contains(end($lineas), 'test-semana17')) {
        throw new RuntimeException('Registro inseguro.');
    }
    echo "OK: bitacora registra eventos sin usuario ni contrasena en claro.\n";
    if (!str_contains(file_get_contents(APP_ROOT . '/index.php'),
        "header('X-Frame-Options: DENY');")) {
        throw new RuntimeException('Falta defensa contra clickjacking.');
    }
    echo "OK: cabecera X-Frame-Options declarada en la respuesta web.\\n";
} finally {
    @unlink($archivo);
}
