<?php
declare(strict_types=1);
final class PrestamoController
{
    public function __construct(private readonly Prestamo $prestamos, private readonly Catalogo $catalogo) {}
    private function id(): int
    {
        return filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
    }
    private function registro(): ?array
    {
        $registro=$this->prestamos->encontrar($this->id());
        if (!$registro) {
            http_response_code(404);
            render('error',['title'=>'Préstamo no encontrado','message'=>'Este préstamo no existe o ya fue eliminado.']);
        }
        return $registro;
    }
    public function index(): void
    {
        $busqueda=mb_substr(trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : ''),0,100);
        $estado=is_string($_GET['estado'] ?? null) ? $_GET['estado'] : '';
        if (!in_array($estado,['','activo','vencido','devuelto'],true)) { $estado=''; }
        render('prestamos/listar',['title'=>'Préstamos','prestamos'=>$this->prestamos->listar($busqueda,$estado),
            'resumen'=>$this->prestamos->resumen(),'busqueda'=>$busqueda,'filtroEstado'=>$estado,'flash'=>take_flash()]);
    }
    private function formulario(array $data,array $errors=[],?int $id=null): void
    {
        render('prestamos/formulario',['title'=>$id ? 'Editar préstamo' : 'Nuevo préstamo',
            'data'=>$data,'errors'=>$errors,'id'=>$id,'libros'=>$this->catalogo->libros($id ?? 0),
            'lectores'=>$this->catalogo->lectores(),'operador'=>$this->catalogo->usuarioRegistro()]);
    }
    public function crear(): void
    {
        $this->formulario(['libro_id'=>'','lector_id'=>'','fecha_prestamo'=>date('Y-m-d'),'fecha_devolucion'=>'']);
    }
    public function editar(): void
    {
        $r=$this->registro(); if ($r) { $this->formulario($r,[],(int)$r['id']); }
    }
    public function ver(): void
    {
        $r=$this->registro(); if ($r) { render('prestamos/ver',['title'=>'Detalle del préstamo','prestamo'=>$r]); }
    }
    public function guardar(): void
    {
        $id=$this->id() ?: null; $data=[];
        foreach (['libro_id','lector_id','fecha_prestamo','fecha_devolucion'] as $key) {
            $data[$key]=is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
        }
        try {
            $operador=$this->catalogo->usuarioRegistro();
            $saved=$this->prestamos->guardar($data,(int)$operador['id'],$id);
            flash($id ? 'Los cambios del préstamo se guardaron correctamente.' : 'El préstamo se registró correctamente.');
            header('Location: '.url('index',['destacado'=>$saved]),true,303);
        } catch (ValidationException $e) {
            http_response_code(422); $this->formulario($data,$e->errors,$id);
        }
    }
    public function confirmarEliminar(): void
    {
        $r=$this->registro(); if ($r) { render('prestamos/eliminar',['title'=>'Eliminar préstamo','prestamo'=>$r]); }
    }
    public function eliminar(): void
    {
        if (!$this->prestamos->eliminar($this->id())) {
            http_response_code(404);
            render('error',['title'=>'Préstamo no encontrado','message'=>'El préstamo ya no existe.']); return;
        }
        flash('El préstamo se eliminó correctamente. La disponibilidad se actualizó.');
        header('Location: '.url(),true,303);
    }
}
