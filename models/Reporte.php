<?php
declare(strict_types=1);
final class Reporte
{
    public function __construct(private readonly PDO $db) {}
    public function vencidos(string $hasta): array
    {
        $fecha=DateTimeImmutable::createFromFormat('!Y-m-d',$hasta);
        if (!$fecha||$fecha->format('Y-m-d')!==$hasta||$hasta<'2000-01-01'||$hasta>date('Y-m-d')) {
            throw new ValidationException(['hasta'=>'Ingresa una fecha válida hasta hoy.']);
        }
        $q=$this->db->prepare('SELECT p.id,p.fecha_prestamo,p.fecha_limite,l.titulo,l.isbn,
            r.nombre AS lector,r.carne,DATEDIFF(?,p.fecha_limite) AS dias_atraso,
            DATEDIFF(?,p.fecha_limite)*2 AS multa_estimada
            FROM prestamos p JOIN libros l ON l.id=p.libro_id
            JOIN lectores r ON r.id=p.lector_id
            WHERE p.fecha_limite<? AND (p.fecha_devolucion IS NULL OR p.fecha_devolucion>?)
            ORDER BY p.fecha_limite ASC,p.id ASC');
        $q->execute([$hasta,$hasta,$hasta,$hasta]);
        return $q->fetchAll();
    }
    public function populares(string $mes): array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',$mes)) {
            throw new ValidationException(['mes'=>'El mes debe tener formato AAAA-MM.']);
        }
        $inicio=$mes.'-01';
        $fin=(new DateTimeImmutable($inicio))->modify('+1 month')->format('Y-m-d');
        $q=$this->db->prepare('SELECT l.id,l.isbn,l.titulo,l.autor,l.categoria,
            COUNT(p.id) AS prestamos_mes,COUNT(DISTINCT p.lector_id) AS lectores_distintos
            FROM libros l JOIN prestamos p ON p.libro_id=l.id
            WHERE p.fecha_prestamo>=? AND p.fecha_prestamo<?
            GROUP BY l.id,l.isbn,l.titulo,l.autor,l.categoria
            ORDER BY prestamos_mes DESC,l.titulo ASC LIMIT 10');
        $q->execute([$inicio,$fin]);
        return $q->fetchAll();
    }
}
