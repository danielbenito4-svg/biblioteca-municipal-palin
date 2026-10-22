<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/config/bootstrap.php';

// Toda la prueba utiliza una base temporal independiente, nunca la base de la demostración.
$config=require APP_ROOT.'/config/database.php';
$temporary='biblioteca_palin_test_'.bin2hex(random_bytes(4));
$db=new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4",
    $config['username'],$config['password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
$checks=0; $exitCode=0;
function check(bool $condition,string $label): void
{
    global $checks;
    if (!$condition) { throw new RuntimeException('FALLÓ: '.$label); }
    $checks++; echo "OK  $label\n";
}
function rechazo(Prestamo $model,array $data,string $expected): void
{
    try { $model->guardar($data,1); }
    catch (ValidationException $e) {
        check(str_contains(implode(' ',$e->errors),$expected),'Rechazo: '.$expected); return;
    }
    throw new RuntimeException('La operación inválida fue aceptada: '.$expected);
}
try {
    $sql=str_replace('biblioteca_palin',$temporary,file_get_contents(APP_ROOT.'/database.sql'));
    $db->exec($sql);
    $model=new Prestamo($db); $catalogo=new Catalogo($db);
    $today=date('Y-m-d');
    $base=['libro_id'=>'10','lector_id'=>'4','fecha_prestamo'=>$today,'fecha_devolucion'=>''];
    check((int)$model->resumen()['total']===12,'Importación de 12 préstamos');
    check(count($catalogo->lectores())===10 && count($catalogo->libros())===10,'Catálogo y lectores relacionados');
    check(password_verify('Biblioteca2026!',$db->query('SELECT password_hash FROM usuarios WHERE id=1')->fetchColumn()),'Contraseña de prueba almacenada con hash');

    $id=$model->guardar($base+['multa'=>'999','fecha_limite'=>'1900-01-01','usuario_id'=>'2'],1);
    $created=$model->encontrar($id);
    check($created!==null && (int)$created['libro_id']===10,'Crear y consultar préstamo');
    check($created['fecha_limite']===(new DateTimeImmutable($today))->modify('+7 days')->format('Y-m-d'),'Plazo de siete días calculado en servidor');
    check((float)$created['multa']===0.0 && (int)$created['usuario_id']===1,'Ignorar multa y operador manipulados');
    $changed=array_replace($base,['libro_id'=>'6','lector_id'=>'10',
        'fecha_prestamo'=>(new DateTimeImmutable($today))->modify('-10 days')->format('Y-m-d'),
        'fecha_devolucion'=>$today]);
    $saved=$model->guardar($changed,2,$id);
    $updated=$model->encontrar($id);
    check($saved===$id && (int)$updated['lector_id']===10 && (int)$updated['libro_id']===6,'Editar el mismo préstamo');
    check((float)$updated['multa']===6.0 && (int)$updated['usuario_id']===1,'Devolución con tres días de atraso y operador original');
    check(count($model->listar('L-010','devuelto'))===1,'Búsqueda por carné y filtro de estado');
    check($model->eliminar($id) && $model->encontrar($id)===null,'Eliminar y comprobar ausencia');
    check(!$model->eliminar($id),'Eliminar registro inexistente no inventa éxito');

    rechazo($model,array_replace($base,['libro_id'=>'3']),'RN-01');
    rechazo($model,array_replace($base,['libro_id'=>'9']),'RN-01');
    rechazo($model,array_replace($base,['lector_id'=>'3']),'RN-02');
    rechazo($model,array_replace($base,['lector_id'=>'2']),'RN-04');
    rechazo($model,array_replace($base,['lector_id'=>'9']),'inactivo');
    rechazo($model,array_replace($base,['libro_id'=>'99999']),'no existe');
    rechazo($model,array_replace($base,['lector_id'=>'99999']),'no existe');
    rechazo($model,array_replace($base,['fecha_prestamo'=>'2026-02-30']),'fecha válida');
    rechazo($model,array_replace($base,['fecha_prestamo'=>"2026-10-22\0"]),'fecha válida');
    rechazo($model,array_replace($base,['fecha_prestamo'=>(new DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d')]),'posterior a hoy');
    rechazo($model,array_replace($base,['fecha_devolucion'=>(new DateTimeImmutable($today))->modify('-1 day')->format('Y-m-d')]),'anterior al préstamo');
    rechazo($model,array_replace($base,['libro_id'=>"1 OR 1=1"]),'libro válido');
    check((int)$model->resumen()['total']===12,'Los rechazos no insertaron ni dejaron cambios parciales');

    $ids=[];
    foreach ([1,1,4] as $book) {
        $ids[]=$model->guardar(array_replace($base,['libro_id'=>(string)$book,'lector_id'=>'10']),1);
    }
    check(count($model->listar('L-010','activo'))===3,'Permitir exactamente tres préstamos activos');
    rechazo($model,array_replace($base,['libro_id'=>'7','lector_id'=>'10']),'RN-02');
    foreach ($ids as $loanId) { $model->eliminar($loanId); }
    $existing=$model->encontrar(3);
    $model->guardar($existing,1,3);
    check((int)$model->resumen()['total']===12,'Editar un préstamo propio no cuenta dos veces su ejemplar ni su lector');
    check($model->listar("' OR 1=1 --")===[] && $model->listar('%')===[],'Búsqueda parametrizada y comodines literales');
    check(Prestamo::calcularMulta('2024-02-28','2024-03-01')===4.0,'Multa correcta en cambio de mes y año bisiesto');
    check(Prestamo::calcularMulta('2026-10-22','2026-10-20')===0.0,'Devolver antes de la fecha límite no genera multa');
    try { $db->exec('DELETE FROM libros WHERE id=1'); throw new RuntimeException('Se rompió la integridad referencial'); }
    catch (PDOException $e) { check($e->getCode()==='23000','Llave foránea impide borrar un libro con préstamos'); }
    try { $db->exec("INSERT INTO lectores (carne,nombre,tipo,estado) VALUES ('L-001','Duplicado','vecino','activo')"); throw new RuntimeException('Se aceptó carné duplicado'); }
    catch (PDOException $e) { check($e->getCode()==='23000','Restricción UNIQUE del carné'); }
    echo "\n$checks comprobaciones correctas.\n";
} catch (Throwable $e) {
    fwrite(STDERR,$e->getMessage()."\n"); $exitCode=1;
} finally {
    if (preg_match('/^biblioteca_palin_test_[a-f0-9]{8}$/D',$temporary)) {
        $db->exec('DROP DATABASE IF EXISTS `'.$temporary.'`');
    }
}
exit($exitCode);
