<?php
declare(strict_types=1);

final class Prestamo
{
    public function __construct(private readonly PDO $db) {}

    public function listar(string $busqueda = '', string $estado = ''): array
    {
        $sql = 'SELECT p.*, l.titulo, l.autor, l.isbn, r.nombre AS lector, r.carne,
                u.nombre AS operador FROM prestamos p
                JOIN libros l ON l.id=p.libro_id JOIN lectores r ON r.id=p.lector_id
                JOIN usuarios u ON u.id=p.usuario_id WHERE 1=1';
        $args = [];
        if ($busqueda !== '') {
            $sql .= " AND (l.titulo LIKE ? ESCAPE '!' OR r.nombre LIKE ? ESCAPE '!'
                OR r.carne LIKE ? ESCAPE '!' OR l.isbn LIKE ? ESCAPE '!')";
            $term = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busqueda) . '%';
            $args = [$term, $term, $term, $term];
        }
        $sql .= match ($estado) {
            'activo' => ' AND p.fecha_devolucion IS NULL',
            'vencido' => ' AND p.fecha_devolucion IS NULL AND p.fecha_limite<CURDATE()',
            'devuelto' => ' AND p.fecha_devolucion IS NOT NULL',
            default => '',
        };
        $q = $this->db->prepare($sql . ' ORDER BY p.id DESC');
        $q->execute($args); return $q->fetchAll();
    }

    public function resumen(): array
    {
        return $this->db->query('SELECT COUNT(*) AS total,
            COALESCE(SUM(fecha_devolucion IS NULL),0) AS activos,
            COALESCE(SUM(fecha_devolucion IS NULL AND fecha_limite<CURDATE()),0) AS vencidos,
            COALESCE(SUM(fecha_devolucion IS NOT NULL),0) AS devueltos FROM prestamos')->fetch();
    }

    public function encontrar(int $id): ?array
    {
        $q = $this->db->prepare('SELECT p.*,l.titulo,l.autor,l.isbn,r.nombre AS lector,
            r.carne,u.nombre AS operador FROM prestamos p
            JOIN libros l ON l.id=p.libro_id JOIN lectores r ON r.id=p.lector_id
            JOIN usuarios u ON u.id=p.usuario_id WHERE p.id=?');
        $q->execute([$id]); return $q->fetch() ?: null;
    }

    public static function calcularMulta(string $limite, string $devolucion): float
    {
        $dias = (int) (new DateTimeImmutable($limite))->diff(new DateTimeImmutable($devolucion))->format('%r%a');
        return max(0, $dias) * 2.0;
    }

    private function validar(array $data): array
    {
        $errors = [];
        foreach (['libro_id'=>'Selecciona un libro válido.', 'lector_id'=>'Selecciona un lector válido.'] as $key=>$msg) {
            if (!filter_var($data[$key] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]])) {
                $errors[$key] = $msg;
            }
        }
        foreach (['fecha_prestamo','fecha_devolucion'] as $key) {
            $value = is_string($data[$key] ?? null) ? $data[$key] : '';
            $data[$key] = $value;
            if ($key === 'fecha_devolucion' && $value === '') { continue; }
            $date = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)
                ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
            if (!$date || $date->format('Y-m-d') !== $value || $value<'1900-01-01') {
                $errors[$key] = 'Ingresa una fecha válida a partir de 1900.';
            } elseif ($value>date('Y-m-d')) {
                $errors[$key] = 'La fecha no puede ser posterior a hoy.';
            }
        }
        if (!isset($errors['fecha_prestamo']) && !isset($errors['fecha_devolucion'])
            && $data['fecha_devolucion'] !== '' && $data['fecha_devolucion']<$data['fecha_prestamo']) {
            $errors['fecha_devolucion'] = 'La devolución no puede ser anterior al préstamo.';
        }
        if ($errors) { throw new ValidationException($errors); }
        return ['libro_id'=>(int)$data['libro_id'], 'lector_id'=>(int)$data['lector_id'],
            'fecha_prestamo'=>$data['fecha_prestamo'],
            'fecha_limite'=>(new DateTimeImmutable($data['fecha_prestamo']))->modify('+7 days')->format('Y-m-d'),
            'fecha_devolucion'=>$data['fecha_devolucion'] ?: null];
    }

    public function guardar(array $data, int $usuarioId, ?int $id = null): int
    {
        $v = $this->validar($data);
        $this->db->beginTransaction();
        try {
            $original = null;
            if ($id !== null) {
                $q = $this->db->prepare('SELECT * FROM prestamos WHERE id=? FOR UPDATE');
                $q->execute([$id]); $original = $q->fetch();
                if (!$original) {
                    throw new ValidationException(['general'=>'El préstamo ya no existe. Actualiza el listado.']);
                }
            }
            // El bloqueo del lector y del libro serializa altas concurrentes sobre los mismos recursos.
            $q = $this->db->prepare('SELECT * FROM lectores WHERE id=? FOR UPDATE');
            $q->execute([$v['lector_id']]); $lector = $q->fetch();
            $q = $this->db->prepare('SELECT * FROM libros WHERE id=? FOR UPDATE');
            $q->execute([$v['libro_id']]); $libro = $q->fetch();
            $errors = [];
            if (!$lector) { $errors['lector_id']='El lector seleccionado no existe.'; }
            if (!$libro) { $errors['libro_id']='El libro seleccionado no existe.'; }
            if ($errors) { throw new ValidationException($errors); }
            if ($v['fecha_devolucion'] === null) {
                $excluir = $id ?? 0;
                if ($lector['estado'] !== 'activo') {
                    $errors['lector_id']='Este lector está inactivo. Selecciona un lector activo.';
                }
                $q = $this->db->prepare('SELECT COUNT(*) FROM prestamos WHERE libro_id=? AND fecha_devolucion IS NULL AND id<>?');
                $q->execute([$v['libro_id'],$excluir]);
                if ((int)$q->fetchColumn()>=(int)$libro['ejemplares_totales']) {
                    $errors['libro_id']='RN-01: este libro no tiene ejemplares disponibles.';
                }
                $q = $this->db->prepare('SELECT COUNT(*) FROM prestamos WHERE lector_id=? AND fecha_devolucion IS NULL AND id<>?');
                $q->execute([$v['lector_id'],$excluir]);
                if ((int)$q->fetchColumn()>=3) {
                    $errors['lector_id']='RN-02: el lector ya tiene el máximo de 3 préstamos activos.';
                }
                $q = $this->db->prepare('SELECT COUNT(*) FROM prestamos WHERE lector_id=? AND fecha_devolucion IS NULL AND fecha_limite<CURDATE() AND id<>?');
                $q->execute([$v['lector_id'],$excluir]);
                if ((int)$q->fetchColumn()>0) {
                    $errors['lector_id']='RN-04: el lector tiene préstamos vencidos y debe devolverlos antes de recibir otro libro.';
                }
                if ($errors) { throw new ValidationException($errors); }
            }
            $v['multa'] = $v['fecha_devolucion'] !== null
                ? self::calcularMulta($v['fecha_limite'],$v['fecha_devolucion']) : 0;
            // El navegador no decide el operador, la multa ni la fecha límite.
            $v['usuario_id'] = $original ? (int)$original['usuario_id'] : $usuarioId;
            if ($id === null) {
                $q = $this->db->prepare('INSERT INTO prestamos
                    (libro_id,lector_id,usuario_id,fecha_prestamo,fecha_limite,fecha_devolucion,multa)
                    VALUES (:libro_id,:lector_id,:usuario_id,:fecha_prestamo,:fecha_limite,:fecha_devolucion,:multa)');
                $q->execute($v); $id = (int)$this->db->lastInsertId();
            } else {
                $v['id']=$id;
                $q = $this->db->prepare('UPDATE prestamos SET libro_id=:libro_id,lector_id=:lector_id,
                    usuario_id=:usuario_id,fecha_prestamo=:fecha_prestamo,fecha_limite=:fecha_limite,
                    fecha_devolucion=:fecha_devolucion,multa=:multa WHERE id=:id');
                $q->execute($v);
            }
            $this->db->commit(); return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
    }

    public function eliminar(int $id): bool
    {
        $q=$this->db->prepare('DELETE FROM prestamos WHERE id=?');
        $q->execute([$id]); return $q->rowCount()===1;
    }
}
