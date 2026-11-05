<?php
declare(strict_types=1);

final class Autenticacion
{
    public const INACTIVIDAD = 1800;

    public static function actual(Usuario $usuarios): ?array
    {
        if (!isset($_SESSION['usuario_id'])) { return null; }
        if (time() - (int)($_SESSION['ultima_actividad'] ?? 0) >= self::INACTIVIDAD) {
            self::limpiar(); return null;
        }
        // Releer rol y estado en cada petición aplica inmediatamente los cambios de la BD.
        $user = $usuarios->encontrarActivo((int)$_SESSION['usuario_id']);
        if (!$user) { self::limpiar(); return null; }
        $_SESSION['ultima_actividad'] = time();
        return $user;
    }

    public static function iniciar(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION = ['usuario_id' => (int)$user['id'], 'ultima_actividad' => time()];
    }

    private static function limpiar(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $cookie['path'],
            'domain' => $cookie['domain'], 'secure' => $cookie['secure'],
            'httponly' => true, 'samesite' => 'Lax']);
        session_destroy();
    }
}
