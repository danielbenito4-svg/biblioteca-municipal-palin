<?php
declare(strict_types=1);
final class Lector
{
    public function __construct(private readonly PDO $db) {}
    public function listar(string $buscar='',string $estado=''): array
    {
        $sql='SELECT l.*, (SELECT COUNT(*) FROM prestamos p WHERE p.lector_id=l.id) AS total_prestamos FROM lectores l WHERE 1=1';
        $args=[];
        if (trim($buscar)!=='') {
            $term='%'.str_replace(['!','%','_'],['!!','!%','!_'],mb_substr(trim($buscar),0,100)).'%';
            $sql.=" AND (l.nombre LIKE ? ESCAPE '!' OR l.carne LIKE ? ESCAPE '!')";
            $args=[$term,$term];
        }
        if (in_array($estado,['activo','inactivo'],true)) {
            $sql.=' AND l.estado=?'; $args[]=$estado;
        }
        $q=$this->db->prepare($sql.' ORDER BY l.nombre');
        $q->execute($args);
        return $q->fetchAll();
    }
    public function encontrar(int $id): ?array
    {
        $q=$this->db->prepare('SELECT * FROM lectores WHERE id=?');
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }
    public function guardar(array $d,?int $id=null): int
    {
        $e=[];
        if (!preg_match('/^[A-Za-z0-9-]{2,20}$/D',(string)($d['carne']??''))) {
            $e['carne']='El carné requiere de 2 a 20 letras, números o guiones.';
        }
        if (!is_string($d['nombre']??null)||trim($d['nombre'])===''||mb_strlen($d['nombre'])>120) {
            $e['nombre']='Nombre obligatorio, máximo 120 caracteres.';
        }
        if (!is_string($d['telefono']??null) || ($d['telefono']!=='' && !preg_match('/^\d{4}-?\d{4}$/D',$d['telefono']))) {
            $e['telefono']='El teléfono debe tener ocho dígitos.';
        }
        if (!in_array($d['tipo']??null,['estudiante','vecino'],true)) {
            $e['tipo']='Tipo de lector inválido.';
        }
        if (!in_array($d['estado']??null,['activo','inactivo'],true)) {
            $e['estado']='Estado de lector inválido.';
        }
        if ($e) { throw new ValidationException($e); }
        try {
            if ($id===null) {
                $q=$this->db->prepare('INSERT INTO lectores (carne,nombre,telefono,tipo,estado) VALUES (?,?,?,?,?)');
                $q->execute([$d['carne'],$d['nombre'],$d['telefono']?:null,$d['tipo'],$d['estado']]);
                return (int)$this->db->lastInsertId();
            }
            if (!$this->encontrar($id)) { throw new ValidationException(['general'=>'El lector ya no existe.']); }
            $q=$this->db->prepare('UPDATE lectores SET carne=?,nombre=?,telefono=?,tipo=?,estado=? WHERE id=?');
            $q->execute([$d['carne'],$d['nombre'],$d['telefono']?:null,$d['tipo'],$d['estado'],$id]);
            return $id;
        } catch (PDOException $ex) {
            if ($ex->getCode()==='23000') { throw new ValidationException(['carne'=>'Ya existe un lector con ese carné.']); }
            throw $ex;
        }
    }
    public function eliminar(int $id): string
    {
        if (!$this->encontrar($id)) { throw new ValidationException(['general'=>'El lector no existe.']); }
        $q=$this->db->prepare('SELECT COUNT(*) FROM prestamos WHERE lector_id=?');
        $q->execute([$id]);
        if ((int)$q->fetchColumn()>0) {
            $this->db->prepare("UPDATE lectores SET estado='inactivo' WHERE id=?")->execute([$id]);
            return 'El lector tiene historial de préstamos, por lo que fue desactivado.';
        }
        $this->db->prepare('DELETE FROM lectores WHERE id=?')->execute([$id]);
        return 'Lector eliminado correctamente.';
    }
}
