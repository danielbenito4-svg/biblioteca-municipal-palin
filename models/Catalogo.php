<?php
declare(strict_types=1);
final class Catalogo
{
    public function __construct(private readonly PDO $db) {}
    public function libros(int $excluirPrestamo = 0): array
    {
        $q = $this->db->prepare('SELECT l.*, l.ejemplares_totales -
            (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id=l.id
             AND p.fecha_devolucion IS NULL AND p.id<>?) AS disponibles
            FROM libros l ORDER BY l.titulo');
        $q->execute([$excluirPrestamo]); return $q->fetchAll();
    }
    public function lectores(): array
    {
        return $this->db->query('SELECT id,carne,nombre,tipo,estado FROM lectores ORDER BY nombre')->fetchAll();
    }
    public function usuarioRegistro(): array
    {
        // Checkpoint 1: operador local fijo. En el checkpoint 2 se usará la sesión autenticada.
        $q = $this->db->prepare('SELECT id,nombre FROM usuarios WHERE id=? AND activo=1');
        $q->execute([1]); $usuario = $q->fetch();
        if (!$usuario) { throw new RuntimeException('Falta el operador de demostración.'); }
        return $usuario;
    }
}
