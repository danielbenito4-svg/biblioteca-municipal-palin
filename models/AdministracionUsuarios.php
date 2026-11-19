<?php
declare(strict_types=1);
final class AdministracionUsuarios
{
    public function __construct(private readonly PDO $db) {}
    public function listar(string $buscar=''): array
    {
        $sql='SELECT id,usuario,nombre,rol,activo FROM usuarios';
        $args=[];
        if (trim($buscar)!=='') {
            $term='%'.str_replace(['!','%','_'],['!!','!%','!_'],mb_substr(trim($buscar),0,100)).'%';
            $sql.=" WHERE usuario LIKE ? ESCAPE '!' OR nombre LIKE ? ESCAPE '!'";
            $args=[$term,$term];
        }
        $q=$this->db->prepare($sql.' ORDER BY nombre');
        $q->execute($args);
        return $q->fetchAll();
    }
    public function encontrar(int $id): ?array
    {
        $q=$this->db->prepare('SELECT id,usuario,nombre,rol,activo FROM usuarios WHERE id=?');
        $q->execute([$id]); return $q->fetch() ?: null;
    }
    public function guardar(array $d,?int $id,int $operador): int
    {
        $e=[];
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/D',(string)($d['usuario']??''))) {
            $e['usuario']='Usuario de 3 a 50 caracteres alfanuméricos o ._-';
        }
        if (!is_string($d['nombre']??null)||trim($d['nombre'])===''||mb_strlen($d['nombre'])>120) {
            $e['nombre']='Nombre obligatorio, máximo 120 caracteres.';
        }
        if (!in_array($d['rol']??null,['bibliotecario','auxiliar'],true)) { $e['rol']='Rol inválido.'; }
        if (!in_array((string)($d['activo']??''),['0','1'],true)) { $e['activo']='Estado inválido.'; }
        if (!is_string($d['password']??null)||($id===null && $d['password']==='')||
            ($d['password']!=='' && (strlen($d['password'])<10 || strlen($d['password'])>128))) {
            $e['password']='La contraseña debe tener entre 10 y 128 caracteres; obligatoria al crear.';
        }
        if ($e) { throw new ValidationException($e); }
        if ($id===$operador && ((int)$d['activo']!==1 || $d['rol']!=='bibliotecario')) {
            throw new ValidationException(['general'=>'No puedes desactivar tu cuenta ni quitarte el rol de bibliotecario.']);
        }
        try {
            if ($id===null) {
                $q=$this->db->prepare('INSERT INTO usuarios (usuario,password_hash,nombre,rol,activo) VALUES (?,?,?,?,?)');
                $q->execute([$d['usuario'],password_hash($d['password'],PASSWORD_DEFAULT),
                    $d['nombre'],$d['rol'],(int)$d['activo']]);
                return (int)$this->db->lastInsertId();
            }
            if (!$this->encontrar($id)) { throw new ValidationException(['general'=>'Usuario inexistente.']); }
            if ($d['password']!=='') {
                $q=$this->db->prepare('UPDATE usuarios SET usuario=?,nombre=?,rol=?,activo=?,password_hash=? WHERE id=?');
                $q->execute([$d['usuario'],$d['nombre'],$d['rol'],(int)$d['activo'],
                    password_hash($d['password'],PASSWORD_DEFAULT),$id]);
            } else {
                $q=$this->db->prepare('UPDATE usuarios SET usuario=?,nombre=?,rol=?,activo=? WHERE id=?');
                $q->execute([$d['usuario'],$d['nombre'],$d['rol'],(int)$d['activo'],$id]);
            }
            return $id;
        } catch (PDOException $ex) {
            if ($ex->getCode()==='23000') { throw new ValidationException(['usuario'=>'El nombre de usuario ya existe.']); }
            throw $ex;
        }
    }
    public function desactivar(int $id,int $operador): void
    {
        if ($id===$operador) { throw new ValidationException(['general'=>'No puedes desactivar tu cuenta.']); }
        if (!$this->encontrar($id)) { throw new ValidationException(['general'=>'El usuario no existe.']); }
        $this->db->prepare('UPDATE usuarios SET activo=0 WHERE id=?')->execute([$id]);
    }
}
