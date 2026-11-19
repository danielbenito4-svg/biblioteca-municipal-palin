<?php
declare(strict_types=1);
final class ReporteController
{
    public function __construct(private readonly Reporte $reporte) {}
    public function index(): void
    {
        $tipo=is_string($_GET['tipo']??null)?$_GET['tipo']:'vencidos';
        if (!in_array($tipo,['vencidos','populares'],true)) { $tipo='vencidos'; }
        $hasta=is_string($_GET['hasta']??null)?$_GET['hasta']:date('Y-m-d');
        $mes=is_string($_GET['mes']??null)?$_GET['mes']:date('Y-m');
        $datos=[];$errors=[];
        try {
            $datos=$tipo==='vencidos'?$this->reporte->vencidos($hasta):$this->reporte->populares($mes);
        } catch (ValidationException $e) {
            $errors=$e->errors;http_response_code(422);
        }
        render('reportes/index',['title'=>'Reportes','tipo'=>$tipo,'hasta'=>$hasta,'mes'=>$mes,
            'datos'=>$datos,'errors'=>$errors]);
    }
}
