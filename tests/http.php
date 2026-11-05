<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('America/Guatemala');
$base=rtrim($argv[1] ?? 'http://localhost/biblioteca-palin','/').'/';
$host=parse_url($base,PHP_URL_HOST);
if (!in_array($host,['localhost','127.0.0.1','::1'],true)) {
    fwrite(STDERR,"Esta prueba solo acepta un servidor local.\n"); exit(1);
}
$curl=curl_init(); $checks=0; $createdId=null; $token=''; $exitCode=0; $responseHeaders=[];
curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>'',CURLOPT_TIMEOUT=>15]);
curl_setopt($curl,CURLOPT_HEADERFUNCTION,function($handle,string $line): int {
    global $responseHeaders;
    $responseHeaders[]=trim($line); return strlen($line);
});
function request(string $path='',?array $data=null): array
{
    global $curl,$base,$responseHeaders;
    $responseHeaders=[];
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
function login(string $username, string $password): array
{
    [, $form] = request('index.php?c=auth&a=login');
    return request('index.php?c=auth&a=ingresar', ['csrf_token'=>readToken($form),
        'usuario'=>$username, 'password'=>$password]);
}
function logout(): void
{
    [, $html] = request();
    request('index.php?c=auth&a=salir', ['csrf_token'=>readToken($html)]);
}
try {
    [$status,, $redirect] = request();
    test($status===303 && str_contains($redirect,'c=auth'),'Páginas internas requieren sesión');
    test(str_contains(strtolower(implode(' ',$responseHeaders)),'samesite=lax'),'Cookie limita envío entre sitios con SameSite');
    test(request('index.php?c=prestamos&a=ver&id=1')[0]===303,'Detalle protegido por URL directa');
    test(request('index.php?c=catalogo&a=buscar&q=Asturias')[0]===401,'Endpoint sin sesión responde 401');
    [$status,$form] = request('index.php?c=auth&a=login');
    $loginToken=readToken($form);
    test($status===200 && str_contains($form,'autocomplete="current-password"'),'Login público con campo de contraseña');
    $beforeLoginCookies=curl_getinfo($curl,CURLINFO_COOKIELIST);
    test(request('index.php?c=auth&a=ingresar',['usuario'=>'bibliotecario','password'=>'Biblioteca2026!'])[0]===403,'Login requiere CSRF');
    [$status,$html]=login('bibliotecario','incorrecta');
    test($status===422 && str_contains($html,'No fue posible iniciar sesión'),'Rechazar contraseña incorrecta sin revelar cuenta');
    test(login("' OR 1=1 --",'Biblioteca2026!')[0]===422,'Login resiste inyección SQL');
    test(login('bibliotecario','Biblioteca2026!')[0]===303,'Login válido del bibliotecario');
    test(curl_getinfo($curl,CURLINFO_COOKIELIST)!==$beforeLoginCookies,'Cambiar identificador de sesión al iniciar sesión');
    test(request('index.php?c=prestamos&a=guardar',['csrf_token'=>$loginToken])[0]===403,'Rotar token CSRF después de autenticar');
    [$status,$html]=request(); test($status===200 && str_contains($html,'Registro de préstamos'),'Listado disponible');
    $headers=strtolower(implode(' ',$responseHeaders));
    test(str_contains($headers,'cache-control: no-store') && str_contains($headers,"frame-ancestors 'none'")
        && str_contains($headers,'x-content-type-options: nosniff'),'Respuestas privadas sin caché y con cabeceras de seguridad');
    test(str_contains($html,'Eliminar préstamo 1'),'Administrador puede eliminar préstamos');
    $cookies=curl_getinfo($curl,CURLINFO_COOKIELIST);
    test((bool)array_filter($cookies,fn($c)=>str_starts_with($c,'#HttpOnly_')),'Cookie de sesión HttpOnly');
    [$status,$form]=request('index.php?c=prestamos&a=crear'); $token=readToken($form);
    test($status===200 && str_contains($form,'name="fecha_prestamo" value="'.date('Y-m-d').'"'),'Formulario con fecha inicial');
    test(request('public/css/app.css')[0]===200 && request('public/js/app.js')[0]===200,'Recursos locales disponibles');
    test(request('index.php?c=prestamos&a=guardar',[])[0]===403,'Rechazar POST sin CSRF');
    test(request('index.php?c=prestamos&a=guardar',['csrf_token'=>'incorrecto'])[0]===403,'Rechazar token incorrecto');
    test(request('index.php?c=prestamos&a=eliminar&id=1')[0]===405,'GET no elimina datos');
    test(request('index.php?c=prestamos&a=noExiste')[0]===404,'Ruta inexistente devuelve 404');
    test(request('index.php?c=prestamos&a=ver&id=999999')[0]===404,'Registro inexistente devuelve 404');
    test(request('config/database.php')[0]===403 && request('database.sql')[0]===403,'Apache protege configuración y script SQL');
    test(request('models/Usuario.php')[0]===403 && request('tests/http.php')[0]===403,'Modelos y pruebas no son accesibles por HTTP');
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

    [$status,$html]=request('index.php?c=catalogo');
    test($status===200 && str_contains($html,'data-catalogo'),'Página de búsqueda en vivo disponible');
    foreach (['Quijote'=>'Don Quijote','Asturias'=>'Hombres de maíz','DEMO-010'=>'La Odisea'] as $query=>$expected) {
        [$status,$body]=request('index.php?c=catalogo&a=buscar&q='.rawurlencode($query));
        $json=json_decode($body,true,512,JSON_THROW_ON_ERROR);
        test($status===200 && str_contains($body,$expected) && isset($json['libros'][0]['disponibles']), 'Buscar título, autor o ISBN: '.$query);
        test(str_starts_with(curl_getinfo($curl,CURLINFO_CONTENT_TYPE),'application/json') && !str_contains($body,'password_hash'),'Endpoint entrega JSON sin contraseñas');
    }
    [$status,$body]=request('index.php?c=catalogo&a=buscar&q=DEMO-003');
    $json=json_decode($body,true,512,JSON_THROW_ON_ERROR);
    test($status===200 && (int)$json['libros'][0]['disponibles']===0,'AJAX calcula disponibilidad desde préstamos sin devolver');
    foreach (['ab',str_repeat('a',101)] as $query) {
        test(request('index.php?c=catalogo&a=buscar&q='.$query)[0]===422,'Servidor limita longitud de búsqueda');
    }
    test(request('index.php?c=catalogo&a=buscar&q[]=Asturias')[0]===422,'Rechazar parámetros de búsqueda con tipo incorrecto');
    [, $body]=request('index.php?c=catalogo&a=buscar&q='.rawurlencode("' OR 1=1 --"));
    test(json_decode($body,true,512,JSON_THROW_ON_ERROR)['libros']===[],'SQL inyectado no devuelve catálogo completo');
    test(request('index.php?c=catalogo&a=buscar&q=Asturias',[])[0]===405,'Endpoint JSON rechaza escritura POST');
    [$status,$html]=request('index.php?c=catalogo&a=editar&id=1');
    test($status===200 && str_contains($html,'Guardar libro'),'Bibliotecario tiene acceso a edición del catálogo');
    $catalogToken=readToken($html);
    test(request('index.php?c=catalogo&a=guardar&id=1',['csrf_token'=>$catalogToken,'ejemplares_totales'=>'-1'])[0]===422,'Validación del catálogo en servidor');
    test(request('index.php?c=auth&a=salir')[0]===405,'GET no cierra la sesión');
    test(request('index.php?c=auth&a=salir',[])[0]===403,'Cerrar sesión requiere CSRF');
    logout();
    test(request()[0]===303,'Cerrar sesión impide volver a páginas internas');
    test(request('index.php?c=catalogo&a=buscar&q=Asturias')[0]===401,'Cerrar sesión bloquea también AJAX');
    test(login('auxiliar','Auxiliar2026!')[0]===303,'Login válido del auxiliar');
    [$status,$html]=request(); $token=readToken($html);
    test($status===200 && !str_contains($html,'Eliminar préstamo'),'Auxiliar consulta listado sin controles de eliminación');
    test(request('index.php?c=prestamos&a=confirmarEliminar&id=1')[0]===403,'Auxiliar no abre eliminación por URL directa');
    test(request('index.php?c=prestamos&a=eliminar&id=1',['csrf_token'=>$token,'rol'=>'bibliotecario'])[0]===403,'Auxiliar no elimina por POST ni suplanta rol');
    test(request('index.php?c=catalogo&a=editar&id=1')[0]===403,'Auxiliar no edita catálogo por URL directa');
    test(request('index.php?c=catalogo&a=guardar&id=1',['csrf_token'=>$token,'titulo'=>'Manipulado','rol'=>'bibliotecario'])[0]===403,'Auxiliar no modifica catálogo con POST manual');
    test(request('index.php?c=catalogo&a=buscar&q=Asturias')[0]===200,'Auxiliar puede consultar JSON de catálogo');
    test(request('index.php?c=prestamos&a=editar&id=1')[0]===200,'Auxiliar accede al registro de devoluciones');
    $auxData=['csrf_token'=>$token,'libro_id'=>'10','lector_id'=>'4','fecha_prestamo'=>date('Y-m-d'),
        'fecha_devolucion'=>'','usuario_id'=>'1','rol'=>'bibliotecario'];
    [$status,,$redirect]=request('index.php?c=prestamos&a=guardar',$auxData);
    test($status===303 && preg_match('/destacado=(\d+)/',$redirect,$m)===1,'Auxiliar registra préstamos');
    $createdId=(int)$m[1];
    [, $html]=request('index.php?c=prestamos&a=ver&id='.$createdId);
    test(str_contains($html,'Auxiliar de prueba'),'Operador del préstamo procede de la sesión, no del POST');
    $auxData['fecha_devolucion']=date('Y-m-d');
    test(request('index.php?c=prestamos&a=guardar&id='.$createdId,$auxData)[0]===303,'Auxiliar registra devolución');
    logout(); login('bibliotecario','Biblioteca2026!');
    [, $html]=request(); $token=readToken($html);
    test(request('index.php?c=prestamos&a=eliminar&id='.$createdId,['csrf_token'=>$token])[0]===303,'Administrador limpia préstamo de prueba del auxiliar');
    $createdId=null;
    logout();
    echo "\n$checks comprobaciones HTTP correctas.\n";
} catch (Throwable $e) { fwrite(STDERR,$e->getMessage()."\n"); $exitCode=1; }
finally {
    if ($createdId!==null && $token!=='') {
        logout(); login('bibliotecario','Biblioteca2026!');
        [, $html]=request(); $token=readToken($html);
        request('index.php?c=prestamos&a=eliminar&id='.$createdId,['csrf_token'=>$token]);
    }
    curl_close($curl);
}
exit($exitCode);
