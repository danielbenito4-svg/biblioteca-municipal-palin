<?php
declare(strict_types=1);

final class CatalogoController
{
    public function __construct(private readonly Catalogo $catalogo) {}
    public function index(): void
    {
        render('catalogo/index', ['title'=>'Catálogo', 'flash'=>take_flash(), 'libros'=>$this->catalogo->listar(is_string($_GET['q']??null)?$_GET['q']:'')]);
    }
    public function buscar(): void
    {
        $q = $_GET['q'] ?? '';
        if (!is_string($q) || mb_strlen(trim($q)) < 3 || mb_strlen(trim($q)) > 100) {
            json_response(['error'=>'Escribe entre 3 y 100 caracteres.', 'libros'=>[]], 422); return;
        }
        json_response(['libros'=>$this->catalogo->buscar(trim($q)), 'limite'=>30]);
    }
    private function id(): int
    {
        return filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
    }
    public function crear(): void
    {
        render('catalogo/formulario',['title'=>'Nuevo libro','libro'=>[
            'id'=>0,'isbn'=>'','titulo'=>'','autor'=>'','categoria'=>'','anio'=>'2026','ejemplares_totales'=>'1'
        ],'errors'=>[]]);
    }
    public function confirmarEliminar(): void
    {
        $libro=$this->catalogo->encontrar($this->id());
        if (!$libro) { http_response_code(404); render('error',['title'=>'Libro no encontrado','message'=>'El libro no existe.']); return; }
        render('catalogo/eliminar',['title'=>'Eliminar libro','libro'=>$libro,'errors'=>[]]);
    }
    public function eliminar(): void
    {
        $libro=$this->catalogo->encontrar($this->id());
        if (!$libro) { http_response_code(404); render('error',['title'=>'Libro no encontrado','message'=>'El libro no existe.']); return; }
        try {
            $this->catalogo->eliminar((int)$libro['id']);
            flash('Libro eliminado correctamente.');
            header('Location: '.ruta('catalogo'),true,303);
        } catch (ValidationException $e) {
            http_response_code(422);
            render('catalogo/eliminar',['title'=>'Eliminar libro','libro'=>$libro,'errors'=>$e->errors]);
        }
    }
    public function editar(): void
    {
        $libro = $this->catalogo->encontrar($this->id());
        if (!$libro) {
            http_response_code(404);
            render('error', ['title'=>'Libro no encontrado', 'message'=>'Este libro no existe.']); return;
        }
        render('catalogo/formulario', ['title'=>'Editar libro', 'libro'=>$libro, 'errors'=>[]]);
    }
    public function guardar(): void
    {
        $id = $this->id();
        if ($id && !$this->catalogo->encontrar($id)) {
            http_response_code(404);
            render('error', ['title'=>'Libro no encontrado', 'message'=>'Este libro no existe.']); return;
        }
        $data = [];
        foreach (['isbn','titulo','autor','categoria','anio','ejemplares_totales'] as $field) {
            $data[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
        }
        try {
            if ($id) { $this->catalogo->actualizar($id, $data); }
            else { $this->catalogo->crear($data); }
            flash($id ? 'Libro actualizado correctamente.' : 'Libro agregado correctamente.');
            header('Location: '.ruta('catalogo'), true, 303);
        } catch (ValidationException $e) {
            http_response_code(422);
            render('catalogo/formulario', ['title'=>'Editar libro', 'libro'=>$data+['id'=>$id], 'errors'=>$e->errors]);
        }
    }
}
