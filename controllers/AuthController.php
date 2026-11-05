<?php
declare(strict_types=1);

final class AuthController
{
    public function __construct(private readonly Usuario $usuarios) {}

    public function login(): void
    {
        if (usuario_actual()) { header('Location: '.url(), true, 303); return; }
        render('auth/login', ['title'=>'Iniciar sesión', 'error'=>null, 'username'=>'']);
    }

    public function ingresar(): void
    {
        $username = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $user = $this->usuarios->autenticar($username, $password);
        if (!$user) {
            http_response_code(422);
            render('auth/login', ['title'=>'Iniciar sesión', 'username'=>mb_substr($username, 0, 50),
                'error'=>'No fue posible iniciar sesión. Revisa tu usuario y contraseña.']); return;
        }
        Autenticacion::iniciar($user);
        header('Location: '.url(), true, 303);
    }

    public function salir(): void
    {
        Autenticacion::cerrar();
        header('Location: '.ruta('auth', 'login'), true, 303);
    }
}
