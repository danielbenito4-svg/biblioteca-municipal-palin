<?php
declare(strict_types=1);
final class LectorController
{
    public function __construct(private readonly Lector $lectores) {}
    private function id(): int
    {
        return filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
    }
    public function index(): void
    {
        $buscar=is_string($_GET['q']??null)?trim($_GET['q']):'';
        $estado=is_string($_GET['estado']??null)?$_GET['estado']:'';
        render('lectores/index',['title'=>'Lectores','lectores'=>$this->lectores->listar($buscar,$estado),
            'buscar'=>$buscar,'estado'=>$estado,'flash'=>take_flash()]);
    }
    public function crear(): void
    {
        render('lectores/formulario',['title'=>'Nuevo lector','lector'=>[
            'id'=>0,'carne'=>'','nombre'=>'','telefono'=>'','tipo'=>'estudiante','estado'=>'activo'
        ],'errors'=>[]]);
    }
    public function editar(): void
    {
        $lector=$this->lectores->encontrar($this->id());
        if (!$lector) { http_response_code(404);render('error',['title'=>'Lector no encontrado','message'=>'Este lector no existe.']);return; }
        render('lectores/formulario',['title'=>'Editar lector','lector'=>$lector,'errors'=>[]]);
    }
    public function guardar(): void
    {
        $id=$this->id();
        if ($id && !$this->lectores->encontrar($id)) {
            http_response_code(404);render('error',['title'=>'Lector no encontrado','message'=>'Este lector no existe.']);return;
        }
        $datos=[];
        foreach (['carne','nombre','telefono','tipo','estado'] as $key) {
            $datos[$key]=is_string($_POST[$key]??null)?trim($_POST[$key]):'';
        }
        try {
            $this->lectores->guardar($datos,$id?:null);
            flash($id?'Lector actualizado.':'Lector registrado.');
            header('Location: '.ruta('lectores'),true,303);
        } catch (ValidationException $e) {
            http_response_code(422);
            render('lectores/formulario',['title'=>$id?'Editar lector':'Nuevo lector',
                'lector'=>$datos+['id'=>$id],'errors'=>$e->errors]);
        }
    }
    public function confirmarEliminar(): void
    {
        $lector=$this->lectores->encontrar($this->id());
        if (!$lector) {http_response_code(404);render('error',['title'=>'Lector no encontrado','message'=>'Este lector no existe.']);return;}
        render('lectores/eliminar',['title'=>'Eliminar o desactivar lector','lector'=>$lector,'errors'=>[]]);
    }
    public function eliminar(): void
    {
        try {
            flash($this->lectores->eliminar($this->id()));
            header('Location: '.ruta('lectores'),true,303);
        } catch (ValidationException $e) {
            http_response_code(422);
            $lector=$this->lectores->encontrar($this->id());
            render('lectores/eliminar',['title'=>'Eliminar o desactivar lector','lector'=>$lector,'errors'=>$e->errors]);
        }
    }
}
