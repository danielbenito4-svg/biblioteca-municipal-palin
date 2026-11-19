<?php
declare(strict_types=1);
final class UsuariosController
{
    public function __construct(private readonly AdministracionUsuarios $usuarios) {}
    private function id(): int
    {
        return filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
    }
    public function index(): void
    {
        $buscar=is_string($_GET['q']??null)?trim($_GET['q']):'';
        render('usuarios/index',['title'=>'Usuarios','usuarios'=>$this->usuarios->listar($buscar),
            'buscar'=>$buscar,'flash'=>take_flash()]);
    }
    public function crear(): void
    {
        render('usuarios/formulario',['title'=>'Nuevo usuario','registro'=>[
            'id'=>0,'usuario'=>'','nombre'=>'','rol'=>'auxiliar','activo'=>1
        ],'errors'=>[]]);
    }
    public function editar(): void
    {
        $registro=$this->usuarios->encontrar($this->id());
        if (!$registro) {http_response_code(404);render('error',['title'=>'Usuario inexistente','message'=>'El usuario no existe.']);return;}
        render('usuarios/formulario',['title'=>'Editar usuario','registro'=>$registro,'errors'=>[]]);
    }
    public function guardar(): void
    {
        $id=$this->id();
        if ($id && !$this->usuarios->encontrar($id)) {
            http_response_code(404);render('error',['title'=>'Usuario inexistente','message'=>'El usuario no existe.']);return;
        }
        $datos=[];
        foreach (['usuario','nombre','rol','activo','password'] as $key) {
            $datos[$key]=is_string($_POST[$key]??null)?trim($_POST[$key]):'';
        }
        try {
            $this->usuarios->guardar($datos,$id?:null,(int)usuario_actual()['id']);
            flash($id?'Usuario actualizado.':'Usuario creado.');
            header('Location: '.ruta('usuarios'),true,303);
        } catch (ValidationException $e) {
            http_response_code(422);
            $datos['id']=$id;unset($datos['password']);
            render('usuarios/formulario',['title'=>$id?'Editar usuario':'Nuevo usuario',
                'registro'=>$datos,'errors'=>$e->errors]);
        }
    }
    public function confirmarDesactivar(): void
    {
        $registro=$this->usuarios->encontrar($this->id());
        if (!$registro) {http_response_code(404);render('error',['title'=>'Usuario inexistente','message'=>'El usuario no existe.']);return;}
        render('usuarios/desactivar',['title'=>'Desactivar usuario','registro'=>$registro,'errors'=>[]]);
    }
    public function desactivar(): void
    {
        try {
            $this->usuarios->desactivar($this->id(),(int)usuario_actual()['id']);
            flash('Usuario desactivado. Su sesión ya no será válida.');
            header('Location: '.ruta('usuarios'),true,303);
        } catch (ValidationException $e) {
            $registro=$this->usuarios->encontrar($this->id());
            if (!$registro) {
                http_response_code(404);
                render('error',['title'=>'Usuario inexistente','message'=>'El usuario no existe.']);
                return;
            }
            http_response_code(422);
            render('usuarios/desactivar',['title'=>'Desactivar usuario',
                'registro'=>$registro,'errors'=>$e->errors]);
        }
    }
}
