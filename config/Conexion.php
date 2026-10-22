<?php
declare(strict_types=1);
final class Conexion
{
    public static function conectar(): PDO
    {
        $c = require __DIR__ . '/database.php';
        $pdo = new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4",
            $c['username'], $c['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
        );
        $pdo->exec("SET time_zone = '-06:00'");
        return $pdo;
    }
}
