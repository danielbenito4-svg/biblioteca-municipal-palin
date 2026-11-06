# Biblioteca Municipal de Palín

Proyecto final individual de Programación Web, segundo semestre de 2026.
Sistema web para administrar el catálogo, los lectores, los préstamos y las devoluciones de la biblioteca.

## Estado del proyecto

Checkpoint 2 implementado: inicio/cierre de sesión, contraseñas con hash, autorización por roles en el servidor, búsqueda AJAX del catálogo con disponibilidad y checklist de seguridad aplicado. Se conserva el MVC y el CRUD de `prestamos` del checkpoint 1. La instalación inicial incluye 12 préstamos, 10 libros, 10 lectores y 2 usuarios de prueba.

La etiqueta de esta entrega es [`checkpoint-2`](https://github.com/danielbenito4-svg/biblioteca-municipal-palin/tree/checkpoint-2). Ver el [checklist de seguridad](docs/seguridad-checkpoint-2.md). La versión anterior permanece en [`checkpoint-1`](https://github.com/danielbenito4-svg/biblioteca-municipal-palin/tree/checkpoint-1).

![Inicio de sesión](docs/capturas/login.png)

![Búsqueda AJAX del catálogo](docs/capturas/catalogo-ajax.png)

Las páginas internas requieren sesión. El operador de un nuevo préstamo se toma del usuario autenticado; al editar se conserva el operador original. El bibliotecario puede eliminar préstamos y editar libros; el auxiliar puede registrar préstamos y devoluciones y consultar el catálogo. Las acciones restringidas devuelven HTTP 403 incluso mediante URL directa o POST manual.

Para la entrega final quedan las altas/bajas del catálogo, la administración de lectores y usuarios y los dos reportes de la ficha. Este avance incluye consulta y edición de libros, además del CRUD de préstamos completo.

## Alcance previsto

- Administrar libros, lectores y usuarios.
- Registrar, consultar, editar y eliminar préstamos según permisos y validaciones.
- Registrar devoluciones y calcular multas por atraso.
- Consultar la disponibilidad de ejemplares.
- Buscar libros por título, autor o ISBN desde tres caracteres, mediante fetch() y JSON.
- Consultar préstamos vencidos a una fecha y los diez libros más prestados de un mes.
- Proteger las páginas internas mediante sesión y permisos por rol.

No se incluyen pagos en línea, reservas, notificaciones por correo ni acceso público de lectores.

## Roles

| Rol | Alcance completo previsto |
| --- | --- |
| Bibliotecario (administrador) | Gestionar catálogo, lectores, préstamos, devoluciones y usuarios; consultar reportes. |
| Auxiliar | Consultar catálogo y lectores; registrar préstamos y devoluciones. No modifica catálogo, lectores ni usuarios y no elimina préstamos. |

Los permisos de las funciones disponibles ya se comprueban en el servidor. La matriz implementada está en el [checklist](docs/seguridad-checkpoint-2.md).

## Stack seleccionado

- PHP 8.x con arquitectura MVC propia.
- Apache y MariaDB mediante XAMPP.
- PDO para acceso a datos con consultas preparadas.
- HTML5, CSS3 y JavaScript con fetch().
- Git y GitHub; editor Visual Studio Code.
- Recursos CSS/JS locales para funcionar sin conexión a internet.

## Modelo de datos propuesto

La tabla principal es `prestamos`. Cada préstamo representa un ejemplar de un libro.
Se añade `usuario_id` a la ficha base para identificar al usuario que registra el préstamo.

```mermaid
erDiagram
    libros ||--o{ prestamos : registra
    lectores ||--o{ prestamos : solicita
    usuarios ||--o{ prestamos : registra
    libros {
        int id PK
        varchar isbn UK
        varchar titulo
        varchar autor
        varchar categoria
        smallint anio
        int ejemplares_totales
    }
    lectores {
        int id PK
        varchar carne UK
        varchar nombre
        varchar telefono
        enum tipo
        enum estado
    }
    usuarios {
        int id PK
        varchar usuario UK
        varchar password_hash
        varchar nombre
        enum rol
        boolean activo
    }
    prestamos {
        int id PK
        int libro_id FK
        int lector_id FK
        int usuario_id FK
        date fecha_prestamo
        date fecha_limite
        date fecha_devolucion
        decimal multa
    }
```

Cada préstamo pertenece a exactamente un libro, un lector y un usuario. Cada libro, lector o usuario puede tener cero o muchos préstamos asociados.

## Reglas de negocio

1. Disponibles = ejemplares totales menos préstamos sin devolver de ese libro; no se presta sin disponibilidad.
2. Un lector puede tener como máximo tres préstamos activos.
3. El plazo es de siete días. Al devolver, la multa es Q 2.00 por día de atraso, con mínimo Q 0.00.
4. Un lector con préstamos vencidos no puede recibir nuevos préstamos.
5. Las reglas se validan en el servidor. Se impedirán eliminaciones que rompan relaciones existentes; los usuarios pueden desactivarse.

## Seguridad del checkpoint 2

Consultas preparadas PDO, escape con `htmlspecialchars`, tokens CSRF en operaciones de escritura, validación en cliente y servidor, rutas permitidas por lista y métodos HTTP comprobados. Los errores técnicos se registran en `storage/php-error.log`, fuera de las vistas y protegido por Apache.

Se usa `password_verify`, con actualización mediante `password_hash` si corresponde. Al autenticar se regenera el identificador de sesión y el token CSRF; al salir se destruyen la sesión y su cookie. Caduca tras 30 minutos de inactividad. El estado y el rol del usuario se releen en la BD en cada petición. Cookies HttpOnly/SameSite=Lax y Secure si se sirve por HTTPS, respuestas privadas sin caché y CSP con recursos locales. El detalle y las evidencias están en el checklist de seguridad.

## Entregas

| Fecha | Entrega |
| --- | --- |
| 18/10/2026 | Propuesta y repositorio con README inicial |
| 25/10/2026 | MVC, base de datos y CRUD de prestamos; etiqueta checkpoint-1 |
| 08/11/2026 | Autenticación, roles y AJAX; etiqueta checkpoint-2 |
| 22/11/2026 | Sistema completo y defensa; etiqueta final |

El historial reflejará el avance real con al menos quince commits distribuidos durante el período.

## Instalación y pruebas

Requisitos: XAMPP con Apache, PHP 8.2 o superior, MariaDB 10.4 o superior y extensiones `pdo_mysql`, `mbstring` y `curl` (esta última para las pruebas HTTP). No requiere Composer, Node, frameworks, librerías de terceros ni conexión a internet durante la demostración.

1. Iniciar **Apache** y **MySQL** desde XAMPP.
2. Clonar o descargar este repositorio dentro de `C:\xampp\htdocs\biblioteca-palin`.
3. Abrir `http://localhost/phpmyadmin`, elegir **Importar** y seleccionar `database.sql`. El script crea la base `biblioteca_palin`, sus cuatro tablas, restricciones y datos de prueba. Importarlo una sola vez en una instalación nueva; no elimina bases ni tablas existentes.
4. La configuración local predeterminada es `127.0.0.1:3306`, usuario `root` y contraseña vacía, habitual en XAMPP de desarrollo. Si el entorno usa otros valores, copiar `config/database.local.example.php` como `config/database.local.php` y ajustar los datos. Este archivo local está excluido de Git. También se aceptan las variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`.
5. Abrir **http://localhost/biblioteca-palin/**.
6. Iniciar sesión con una de las cuentas de demostración de esta página.

**Si ya instalaste el checkpoint 1:** el esquema y las cuentas existentes siguen siendo compatibles. Actualiza los archivos y recarga el navegador; no vuelvas a importar `database.sql` sobre la base existente.

La zona horaria del sistema es `America/Guatemala`. Las fechas de los datos de prueba se calculan al importar, para disponer de casos en curso, vencidos y devueltos. Los nombres, teléfonos e identificadores del catálogo son datos de demostración.

### Organización MVC

```text
index.php                   Enrutador de entrada y controles HTTP/CSRF
config/
  bootstrap.php             Inicio, autoload y funciones compartidas
  database.php              Configuración de conexión
  Conexion.php              Conexión PDO
  Autenticacion.php          Sesión, caducidad y usuario vigente
controllers/
  PrestamoController.php    Recibe solicitudes y elige modelos y vistas
  AuthController.php        Inicio y cierre de sesión
  CatalogoController.php    Consulta JSON y edición administrativa
models/
  Prestamo.php              Consultas, transacciones y reglas de negocio
  Catalogo.php              Libros, lectores, búsqueda y edición de libros
  Usuario.php               Verificación de hash y consulta de usuario activo
  ValidationException.php   Errores de validación del formulario
views/
  layout/                   Estructura compartida de las páginas
  prestamos/                Listado, formulario, detalle y confirmación
  auth/ y catalogo/         Login, búsqueda en vivo y edición de libros
public/css/ y public/js/    Recursos locales de la interfaz
database.sql                Esquema y datos de prueba
tests/                      Pruebas de integración y HTTP
docs/                       Checklist de seguridad y capturas
```

Las vistas no ejecutan SQL y el controlador no construye HTML. `usuario_id` conserva al operador original al editar. La fecha límite y la multa siempre se calculan en PHP; los valores enviados por el navegador no pueden reemplazarlos. La disponibilidad se calcula desde los préstamos sin devolución, en lugar de almacenar otro contador que pueda desajustarse.

### Probar el sistema

Con MariaDB iniciado, ejecutar en una terminal:

```powershell
C:\xampp\php\php.exe tests\integration.php
```

La prueba crea una base temporal con nombre aleatorio, verifica el CRUD, las cuatro reglas, fechas, multas, integridad referencial y búsquedas, y elimina únicamente esa base temporal al terminar. Requiere permisos para crear bases locales.

Con Apache y la aplicación instalados:

```powershell
C:\xampp\php\php.exe tests\http.php
```

La prueba HTTP verifica autenticación, cierre de sesión, permisos con ambos roles, rutas directas, JSON, formularios, persistencia, errores, CSRF, métodos HTTP y escape XSS. Crea sus propios préstamos de prueba y los elimina al terminar. Se puede pasar una URL local diferente como primer argumento. Usa las cuentas de demostración originales; las pruebas de cambios de rol, usuarios inactivos y caducidad se ejecutan únicamente en la base temporal de integración.

Validación del checkpoint 2: **47 comprobaciones de integración y 67 HTTP correctas**, además de la revisión en navegador y móvil. Las evidencias y los controles aplicados están en el [checklist de seguridad](docs/seguridad-checkpoint-2.md).

### Usuarios de demostración

| Usuario | Contraseña de prueba | Rol |
| --- | --- | --- |
| `bibliotecario` | `Biblioteca2026!` | Bibliotecario |
| `auxiliar` | `Auxiliar2026!` | Auxiliar |

Son credenciales públicas para la demostración local, con contraseñas almacenadas como hash en la base.

## Uso de inteligencia artificial

Se utilizó ChatGPT/Codex como apoyo para organizar la propuesta, elaborar el modelo de datos, implementar los checkpoints 1 y 2, depurar el código, preparar pruebas y redactar la documentación. El estudiante debe revisar y comprender el código, practicar la demostración y poder explicar o modificar lo que entregue. Esta sección se actualizará conforme al uso real de la herramienta.
