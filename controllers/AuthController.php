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
        // MITIGACION 1 (Tarea 9): como maximo cinco fallos por usuario en quince minutos.
        $limite = new RateLimit(APP_ROOT . '/storage/limite-login.json');
        if ($limite->bloqueado($username)) {
            AuditoriaSeguridad::registrar('login_bloqueado', $username);
            http_response_code(429);
            render('auth/login', ['title'=>'Iniciar sesión', 'username'=>mb_substr($username, 0, 50),
                'error'=>'Demasiados intentos. Intenta de nuevo en 15 minutos.']); return;
        }
        $user = $this->usuarios->autenticar($username, $password);
        if (!$user) {
            // MITIGACION 2 (Tarea 9): bitacora de eventos sin contrasenas ni usuarios en claro.
            $limite->registrarFallo($username);
            AuditoriaSeguridad::registrar('login_fallido', $username);
            http_response_code(422);
            render('auth/login', ['title'=>'Iniciar sesión', 'username'=>mb_substr($username, 0, 50),
                'error'=>'No fue posible iniciar sesión. Revisa tu usuario y contraseña.']); return;
        }
        $limite->limpiar($username);
        AuditoriaSeguridad::registrar('login_correcto', $username);
        Autenticacion::iniciar($user);
        header('Location: '.url(), true, 303);
    }

    public function salir(): void
    {
        Autenticacion::cerrar();
        header('Location: '.ruta('auth', 'login'), true, 303);
    }
}
