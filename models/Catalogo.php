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
    public function buscar(string $busqueda): array
    {
        $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busqueda).'%';
        $q = $this->db->prepare("SELECT l.id,l.isbn,l.titulo,l.autor,l.categoria,l.anio,l.ejemplares_totales,
            l.ejemplares_totales - (SELECT COUNT(*) FROM prestamos p
                WHERE p.libro_id=l.id AND p.fecha_devolucion IS NULL) AS disponibles
            FROM libros l WHERE l.titulo LIKE ? ESCAPE '!' OR l.autor LIKE ? ESCAPE '!'
                OR l.isbn LIKE ? ESCAPE '!' ORDER BY l.titulo LIMIT 30");
        $q->execute([$term, $term, $term]);
        return $q->fetchAll();
    }

    public function encontrar(int $id): ?array
    {
        $q = $this->db->prepare('SELECT * FROM libros WHERE id=?');
        $q->execute([$id]); return $q->fetch() ?: null;
    }

    public function actualizar(int $id, array $data): void
    {
        $errors = [];
        foreach (['isbn'=>20, 'titulo'=>150, 'autor'=>150, 'categoria'=>80] as $field=>$max) {
            if ($data[$field] === '' || mb_strlen($data[$field]) > $max) {
                $errors[$field] = "El campo $field es obligatorio y admite hasta $max caracteres.";
            }
        }
        foreach (['anio'=>[1,9999], 'ejemplares_totales'=>[0,1000000]] as $field=>$range) {
            if (filter_var($data[$field], FILTER_VALIDATE_INT,
                ['options'=>['min_range'=>$range[0], 'max_range'=>$range[1]]]) === false) {
                $errors[$field] = "El campo $field debe ser un entero entre $range[0] y $range[1].";
            }
        }
        if ($errors) { throw new ValidationException($errors); }
        $this->db->beginTransaction();
        try {
            // Mismo bloqueo de libro que usa el alta de préstamos.
            $q = $this->db->prepare('SELECT id FROM libros WHERE id=? FOR UPDATE');
            $q->execute([$id]);
            if (!$q->fetch()) { throw new ValidationException(['general'=>'El libro ya no existe.']); }
            $q = $this->db->prepare('SELECT COUNT(*) FROM prestamos WHERE libro_id=? AND fecha_devolucion IS NULL');
            $q->execute([$id]);
            if ((int)$data['ejemplares_totales'] < (int)$q->fetchColumn()) {
                throw new ValidationException(['ejemplares_totales'=>'No puedes reducir el total por debajo de los ejemplares sin devolver.']);
            }
            $q = $this->db->prepare('UPDATE libros SET isbn=?,titulo=?,autor=?,categoria=?,anio=?,ejemplares_totales=? WHERE id=?');
            $q->execute([$data['isbn'], $data['titulo'], $data['autor'], $data['categoria'],
                $data['anio'], $data['ejemplares_totales'], $id]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            if ($e instanceof PDOException && $e->getCode() === '23000') {
                throw new ValidationException(['isbn'=>'Ya existe otro libro con ese ISBN.']);
            }
            throw $e;
        }
    }
}
