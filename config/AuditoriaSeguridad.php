<?php
declare(strict_types=1);

/** Tarea 9, mitigacion 2: bitacora sin claves ni nombres en texto claro. */
final class AuditoriaSeguridad
{
    public static function registrar(string $evento, string $usuario = ''): void
    {
        $permitidos = ['login_fallido', 'login_bloqueado', 'login_correcto', 'acceso_denegado'];
        if (!in_array($evento, $permitidos, true)) {
            throw new InvalidArgumentException('Evento de auditoria no permitido.');
        }
        $linea = json_encode([
            'fecha' => gmdate('c'),
            'evento' => $evento,
            'usuario_hash' => hash('sha256', strtolower(trim($usuario))),
            'ip_hash' => hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'local')),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $ruta = APP_ROOT . '/storage/security-audit.log';
        if (file_put_contents($ruta, $linea . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('No se pudo registrar el evento de seguridad.');
        }
        @chmod($ruta, 0600);
    }
}
