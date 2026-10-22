<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('America/Guatemala');
$base=rtrim($argv[1] ?? 'http://localhost/biblioteca-palin','/').'/';
$host=parse_url($base,PHP_URL_HOST);
if (!in_array($host,['localhost','127.0.0.1','::1'],true)) {
    fwrite(STDERR,"Esta prueba solo acepta un servidor local.\n"); exit(1);
}
$curl=curl_init(); $checks=0; $createdId=null; $token=''; $exitCode=0;
curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>'',CURLOPT_TIMEOUT=>15]);
function request(string $path='',?array $data=null): array
{
    global $curl,$base;
    curl_setopt($curl,CURLOPT_URL,$base.$path);
    if ($data===null) { curl_setopt($curl,CURLOPT_HTTPGET,true); }
    else { curl_setopt($curl,CURLOPT_POST,true); curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($data)); }
    $body=curl_exec($curl);
    if ($body===false) { throw new RuntimeException(curl_error($curl)); }
    return [(int)curl_getinfo($curl,CURLINFO_HTTP_CODE),$body,(string)curl_getinfo($curl,CURLINFO_REDIRECT_URL)];
}
function test(bool $ok,string $label): void
{
    global $checks;
    if (!$ok) { throw new RuntimeException('FALLÓ: '.$label); }
    $checks++; echo "OK  $label\n";
}
function readToken(string $html): string
{
    if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/',$html,$m)) {
        throw new RuntimeException('No se encontró token CSRF');
    }
    return $m[1];
}
try {
    [$status,$html]=request(); test($status===200 && str_contains($html,'Registro de préstamos'),'Listado disponible');
    [$status,$form]=request('index.php?c=prestamos&a=crear'); $token=readToken($form);
    test($status===200 && str_contains($form,'name="fecha_prestamo" value="'.date('Y-m-d').'"'),'Formulario con fecha inicial');
    test(request('public/css/app.css')[0]===200 && request('public/js/app.js')[0]===200,'Recursos locales disponibles');
    test(request('index.php?c=prestamos&a=guardar',[])[0]===403,'Rechazar POST sin CSRF');
    test(request('index.php?c=prestamos&a=guardar',['csrf_token'=>'incorrecto'])[0]===403,'Rechazar token incorrecto');
    test(request('index.php?c=prestamos&a=eliminar&id=1')[0]===405,'GET no elimina datos');
    test(request('index.php?c=prestamos&a=noExiste')[0]===404,'Ruta inexistente devuelve 404');
    test(request('index.php?c=prestamos&a=ver&id=999999')[0]===404,'Registro inexistente devuelve 404');
    test(request('config/database.php')[0]===403 && request('database.sql')[0]===403,'Apache protege configuración y script SQL');
    $payload='<img src=x onerror=alert(1)>';
    [$status,$html]=request('index.php?q='.rawurlencode($payload));
    test($status===200 && !str_contains($html,$payload) && str_contains($html,'&lt;img'),'Escapar búsqueda contra XSS');
    [, $html]=request('index.php?q='.rawurlencode("' OR 1=1 --"));
    test(str_contains($html,'0 resultados'),'Búsqueda no ejecuta SQL inyectado');

    $data=['csrf_token'=>$token,'libro_id'=>'10','lector_id'=>'4','fecha_prestamo'=>date('Y-m-d'),'fecha_devolucion'=>''];
    [$status,$html]=request('index.php?c=prestamos&a=guardar',array_replace($data,['libro_id'=>'3']));
    test($status===422 && str_contains($html,'RN-01'),'Validación del servidor visible en el formulario');
    test(str_contains($html,'value="3" selected') && str_contains($html,'value="4" selected'),'Conservar campos después del rechazo');
    [$status,$html,$redirect]=request('index.php?c=prestamos&a=guardar',$data);
    test($status===303 && preg_match('/destacado=(\d+)/',$redirect,$m)===1,'POST crea y redirige sin duplicar al actualizar');
    $createdId=(int)$m[1];
    [$status,$html]=request('index.php?c=prestamos&a=ver&id='.$createdId);
    test($status===200 && str_contains($html,'La Odisea') && str_contains($html,'Lucía Gómez'),'Consultar el nuevo registro');
    [$status,$html]=request('index.php?c=prestamos&a=editar&id='.$createdId);
    test($status===200 && str_contains($html,'value="10" selected'),'Edición carga el libro original');
    $token=readToken($html); $data['csrf_token']=$token;
    $data['libro_id']='6';
    $data['fecha_prestamo']=(new DateTimeImmutable('today'))->modify('-10 days')->format('Y-m-d');
    $data['fecha_devolucion']=date('Y-m-d');
    test(request('index.php?c=prestamos&a=guardar&id='.$createdId,$data)[0]===303,'Actualizar préstamo por POST');
    [, $html]=request('index.php?c=prestamos&a=ver&id='.$createdId);
    test(str_contains($html,'Don Quijote') && str_contains($html,'Q 6.00') && str_contains($html,'Devuelto'),'Edición y multa guardadas');
    [$status,$html]=request('index.php?c=prestamos&a=confirmarEliminar&id='.$createdId);
    test($status===200 && str_contains($html,'¿Eliminar este préstamo?'),'Confirmación antes de eliminar');
    $token=readToken($html);
    test(request('index.php?c=prestamos&a=eliminar&id='.$createdId,['csrf_token'=>$token])[0]===303,'Eliminar el registro creado por la prueba');
    test(request('index.php?c=prestamos&a=ver&id='.$createdId)[0]===404,'Confirmar eliminación persistida');
    $createdId=null;
    echo "\n$checks comprobaciones HTTP correctas.\n";
} catch (Throwable $e) { fwrite(STDERR,$e->getMessage()."\n"); $exitCode=1; }
finally {
    if ($createdId!==null && $token!=='') {
        request('index.php?c=prestamos&a=eliminar&id='.$createdId,['csrf_token'=>$token]);
    }
    curl_close($curl);
}
exit($exitCode);
