<?php
declare(strict_types=1);

final class Usuario
{
    public function __construct(private readonly PDO $db) {}

    public function encontrarActivo(int $id): ?array
    {
        $q = $this->db->prepare('SELECT id,usuario,nombre,rol FROM usuarios WHERE id=? AND activo=1');
        $q->execute([$id]);
        $user = $q->fetch();
        return $user && in_array($user['rol'], ['bibliotecario', 'auxiliar'], true) ? $user : null;
    }

    public function autenticar(string $username, string $password): ?array
    {
        if ($username === '' || mb_strlen($username) > 50 || $password === '' || strlen($password) > 1024) {
            return null;
        }
        $q = $this->db->prepare('SELECT * FROM usuarios WHERE usuario=?');
        $q->execute([$username]);
        $user = $q->fetch();
        // Verificar también un hash si el usuario no existe; el mensaje siempre es el mismo.
        $hash = $user['password_hash'] ?? '$2y$10$xt4gC7F8zFUheJ0OF1hzN.w87dmAQIV/ivl26WBKeT.fZE.l4dmGq';
        $valid = password_verify($password, $hash);
        if (!$valid || !$user || !(bool)$user['activo']
            || !in_array($user['rol'], ['bibliotecario', 'auxiliar'], true)) { return null; }
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $q = $this->db->prepare('UPDATE usuarios SET password_hash=? WHERE id=? AND password_hash=?');
            $q->execute([password_hash($password, PASSWORD_DEFAULT), $user['id'], $hash]);
        }
        unset($user['password_hash'], $user['activo']);
        return $user;
    }
}
