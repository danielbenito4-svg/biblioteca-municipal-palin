# Checkpoint 2 - Auditoría de seguridad del proyecto final
**Proyecto:** Biblioteca Municipal de Palín · **Estudiante:** Gerson Daniel Benito Chávez · **Carné:** 2453090
**Fuente de controles:** Semana17_Actividad_ProgWeb.pdf, página 29 · **Revisión:** 14 de noviembre de 2026 · **Rama:** main

Evaluación de código de la rama main. La columna `¿Cumple?` describe lo implementado y observado en los archivos, **no sustituye una prueba de navegador o de configuración de MySQL**. Los 15 controles y las clasificaciones OWASP se conservan de la guía oficial.

| # | Control oficial | OWASP | ¿Cumple? | Evidencia (archivo:línea) o pendiente |
|---|---|---|---|---|
| 1 | Consultas parametrizadas en todo el CRUD | A05 | **Sí** | `config/Conexion.php:8-12` desactiva emulación; `models/Prestamo.php:27-28,41-45,134-143,154-155`; `models/Catalogo.php:21-26,62-63`. Las consultas fijas sin entrada del usuario usan `query()`. |
| 2 | Toda salida escapada (e(), <c:out>, Razor @) | A05 | **Sí** | `config/bootstrap.php:19-22` define `e()`; `views/layout/header.php:6,15,28` escapa textos; `public/js/app.js:35` usa `textContent` para datos externos. |
| 3 | Validación de entradas en el servidor | A05/A06 | **Sí** | `models/Prestamo.php:54-79` valida IDs y fechas; `models/Catalogo.php:38-50` valida campos y cantidades; `controllers/CatalogoController.php:12-17` limita búsqueda. |
| 4 | Contraseñas con hash lento y sal | A04 | **Sí** | `models/Usuario.php:25-31` usa `password_verify()`, `password_hash(PASSWORD_DEFAULT)` y rehash; confirmar hashes en BD en prueba local. |
| 5 | Token CSRF en todos los formularios POST | A01 | **Sí** | `config/bootstrap.php:55-63` token aleatorio y `hash_equals`; `index.php:55-57` rechaza POST inválidos; `views/auth/login.php:14-15` y `views/prestamos/formulario.php:4-5` lo incluyen. |
| 6 | Cookie de sesión HttpOnly + SameSite | A07 | **Sí** | `config/bootstrap.php:14-17`: `cookie_httponly=true`, `cookie_samesite=Lax`; `Secure` activado solo bajo HTTPS. Verificar atributos con F12. |
| 7 | Regenerar el ID de sesión al iniciar sesión | A07 | **Sí** | `config/Autenticacion.php:21-25` invoca `session_regenerate_id(true)` y cambia los datos de sesión. |
| 8 | Límite de intentos de inicio de sesión | A07 | **Sí** | **Mitigación 1.** `controllers/AuthController.php:18-24,28-30,35`, `config/RateLimit.php:45-65`: cinco fallos en 15 minutos, respuesta 429 y contador privado con bloqueo de archivo. |
| 9 | Páginas privadas verifican sesión y rol | A01 | **Sí** | `index.php:13-23,39-53`: lista de rutas/métodos y roles; devuelve 401, redirección al login o 403 según corresponda. |
| 10 | Verificación de dueño en cada registro (sin IDOR) | A01 | **Parcial** | `index.php:13-23,39-53` controla roles, pero `models/Prestamo.php:41-45` consulta por ID sin restricción individual de propietario. **Pendiente:** documentar autorización compartida para el personal o introducir permisos por registro según la política de negocio. |
| 11 | Errores genéricos y bitácora | A09/A10 | **Sí** | **Mitigación 2.** `config/bootstrap.php:5-7` y `index.php:63-67` ocultan detalles y registran errores; `config/AuditoriaSeguridad.php:7-25` registra eventos de seguridad; `controllers/AuthController.php:20-30,35-36` invoca bitácora. |
| 12 | Cabeceras de seguridad y CSP | A02 | **Sí** | **Mitigación 3.** `index.php:4-10`: `Cache-Control`, `X-Frame-Options: DENY`, `X-Content-Type-Options`, `Referrer-Policy` y CSP con `frame-ancestors 'none'`. Confirmar en F12 → Network → Headers. |
| 13 | Credenciales fuera del código público / .gitignore | A02 | **Parcial** | `config/.htaccess:1` bloquea acceso web; `.gitignore:1` excluye `config/database.local.php`; `config/database.php:4-11` acepta variables de entorno. **Pendiente:** quitar el usuario `root`/clave vacía como fallback del archivo versionado. |
| 14 | Usuario de BD con mínimo privilegio | A02 | **No** | `config/database.php:7-8` y `config/database.local.example.php:4` usan `root` por defecto. **Pendiente:** crear usuario MySQL dedicado con solo `SELECT, INSERT, UPDATE, DELETE` en `biblioteca_palin` y configurar `database.local.php`. |
| 15 | Dependencias revisadas y actualizadas | A03 | **Parcial** | El repositorio no contiene `composer.json`, `composer.lock` ni dependencias externas PHP; `README.md` describe requisitos. **Pendiente:** registrar versión de PHP/MariaDB, revisión de parches y dependencias del entorno con fecha y evidencia. |

**Resumen del estado actual del código:** 11 Sí, 3 Parcial y 1 No.

## Tres mitigaciones implementadas (antes → después)

Las tres mejoras se agregaron a `main` en la Tarea 9. Comparación con la revisión previa **348ac3f5289a**.

### Mitigación 1: control 8 (antes: No → ahora: Sí)
**Antes** (`controllers/AuthController.php`): autenticación sin contador.
```php
$user = $this->usuarios->autenticar($username, $password);
if (!$user) { http_response_code(422); /* ... */ }
```
**Después** (`controllers/AuthController.php:18-24`, `config/RateLimit.php`):
```php
$limite = new RateLimit(APP_ROOT . '/storage/limite-login.json');
if ($limite->bloqueado($username)) {
    AuditoriaSeguridad::registrar('login_bloqueado', $username);
    http_response_code(429);
    // Formulario con mensaje y retorno...
}
```
Además registra fallos y limpia el contador después de login correcto. Prueba: `php tests/seguridad-semana17.php` y cinco fallos consecutivos en localhost, dejando constancia del HTTP 429.

### Mitigación 2: control 11 (antes: Parcial → ahora: Sí)
**Antes**: `index.php` registraba errores de aplicación, pero el login no dejaba eventos de seguridad.
**Después** (`controllers/AuthController.php:28-36`, `config/AuditoriaSeguridad.php:7-25`):
```php
$limite->registrarFallo($username);
AuditoriaSeguridad::registrar('login_fallido', $username);
// ...
AuditoriaSeguridad::registrar('login_correcto', $username);
```
El registro guarda fecha, tipo, hash de usuario e IP; no guarda contraseñas en texto. Prueba: verificar `storage/security-audit.log` después de un intento fallido y uno correcto.

### Mitigación 3: control 12 (antes: Parcial → ahora: Sí)
**Antes** (`index.php:6` en commit anterior): CSP con `frame-ancestors 'none'`, pero no había cabecera adicional `X-Frame-Options`.
**Después** (`index.php:6-10`):
```php
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
```
Prueba: F12 → Network → `index.php` → Headers y comprobar `X-Frame-Options: DENY`.

## Evidencia pendiente
Las tres **capturas de pruebas reales en el entorno local del estudiante** deben adjuntarse al informe de la Tarea 9. Los fragmentos de código no sustituyen esas capturas. No se deben inventar resultados de pruebas que no se han ejecutado.

## Próximos controles que requieren trabajo real
- **13:** eliminar credenciales por defecto del repositorio y exigir configuración local/env.
- **14:** crear usuario de aplicación con permisos mínimos; **requiere acceso a MySQL local** y no se afirma implementado.
- **15:** levantar inventario de versiones y registrar auditoría de actualizaciones.
- **10:** decidir por escrito el alcance de lectura/edición compartida del personal y reforzar autorizaciones según la decisión.
