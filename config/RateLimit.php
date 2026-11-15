<?php
declare(strict_types=1);

/** Tarea 9, mitigacion 1: maximo cinco fallos por usuario en 15 minutos. */
final class RateLimit
{
    public function __construct(private readonly string $archivo) {}

    private function llave(string $usuario): string
    {
        // Solo hashes en disco; nunca nombres de usuario ni contrasenas.
        return hash('sha256', strtolower(trim($usuario)));
    }

    private function transaccion(callable $operacion): mixed
    {
        $manejador = fopen($this->archivo, 'c+');
        if ($manejador === false) { throw new RuntimeException('No se puede abrir el control de intentos.'); }
        try {
            if (!flock($manejador, LOCK_EX)) { throw new RuntimeException('No se puede bloquear el archivo.'); }
            rewind($manejador);
            $contenido = stream_get_contents($manejador);
            $datos = json_decode($contenido ?: '{}', true);
            if (!is_array($datos)) { $datos = []; }
            $umbral = time() - 900;
            foreach ($datos as $llave => $momentos) {
                if (!is_array($momentos)) { unset($datos[$llave]); continue; }
                $datos[$llave] = array_values(array_filter($momentos,
                    static fn($t): bool => is_int($t) && $t > $umbral));
                if (!$datos[$llave]) { unset($datos[$llave]); }
            }
            $resultado = $operacion($datos);
            rewind($manejador);
            ftruncate($manejador, 0);
            fwrite($manejador, json_encode($datos, JSON_THROW_ON_ERROR));
            fflush($manejador);
            return $resultado;
        } finally {
            flock($manejador, LOCK_UN);
            fclose($manejador);
            @chmod($this->archivo, 0600);
        }
    }

    public function bloqueado(string $usuario): bool
    {
        $llave = $this->llave($usuario);
        return $this->transaccion(static fn(array &$datos): bool => count($datos[$llave] ?? []) >= 5);
    }

    public function registrarFallo(string $usuario): void
    {
        $llave = $this->llave($usuario);
        $this->transaccion(static function (array &$datos) use ($llave): void {
            $datos[$llave][] = time();
        });
    }

    public function limpiar(string $usuario): void
    {
        $llave = $this->llave($usuario);
        $this->transaccion(static function (array &$datos) use ($llave): void {
            unset($datos[$llave]);
        });
    }
}
