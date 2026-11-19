# Biblioteca Municipal de Palín — Proyecto final

Sistema de gestión web para la Biblioteca Municipal de Palín, Escuintla, Guatemala.
**Programación WEB · Sexto semestre · Fase 3 (30 puntos) · Noviembre de 2026**.

Este proyecto es individual y está desarrollado en **PHP 8.x + MySQL/MariaDB**, con arquitectura **Modelo–Vista–Controlador**, JavaScript nativo y CSS local. Funciona **sin Internet** una vez instalado XAMPP. No utiliza Laravel, React ni dependencias de CDN.

## Funcionalidades entregadas

- **Préstamos y devoluciones:** crear, consultar, buscar por libro, ISBN, carné o lector, filtrar por estado, editar, eliminar con confirmación (bibliotecario), devolver y calcular multas automáticamente.
- **Libros:** listado completo, búsqueda por título/autor/ISBN, alta, edición y eliminación con confirmación. No se elimina un libro que tenga préstamos históricos, por integridad referencial.
- **Lectores:** listado con filtro/búsqueda, alta, edición, eliminación cuando no hay historial y desactivación cuando tiene préstamos, conservando el historial.
- **Usuarios:** listado y búsqueda, alta, edición y desactivación (borrado lógico); contraseñas almacenadas exclusivamente como hashes. Un bibliotecario no puede desactivar su propia cuenta ni retirarse su rol.
- **Roles:** bibliotecario administra libros, lectores, usuarios, préstamos y reportes. Auxiliar consulta libros/lectores y gestiona préstamos y devoluciones, sin eliminar préstamos ni modificar catálogos, lectores o cuentas. Los permisos se verifican en **index.php** para cada petición, incluidas URL directas.
- **AJAX:** búsqueda de libros desde tres caracteres con `fetch()` sin recargar la página, endpoint JSON propio en `index.php?c=catalogo&a=buscar&q=...`, disponibilidad actualizada.
- **Reportes:** préstamos vencidos al corte de una fecha y diez libros más prestados en el mes seleccionado; ambos con `JOIN`, el segundo con `COUNT`, `GROUP BY` y filtro por mes.

## Reglas de negocio de la biblioteca

1. Disponibilidad: ejemplares totales menos préstamos sin fecha de devolución. No se permite prestar sin existencias.
2. Cada lector admite **máximo tres préstamos activos**.
3. Fecha límite = fecha del préstamo + **7 días**. Multa por atraso = **Q 2.00 por día**, nunca negativa.
4. Un lector con préstamos vencidos **no puede recibir** otro libro.
5. Se impiden préstamos a lectores inactivos, fechas futuras/inválidas y registros con referencias inexistentes. Los controles están en el servidor, no dependen de JavaScript.

## Instalación local en Windows (XAMPP)

Requisitos: Apache + MySQL/MariaDB desde XAMPP, PHP 8.2+ con `pdo_mysql`, `mbstring` y `curl` para las pruebas. Visual Studio Code y Edge son suficientes. No se requiere Composer, npm o Internet.

1. En XAMPP, iniciar **Apache** y **MySQL**.
2. Copiar el proyecto a `C:\xampp\htdocs\biblioteca-palin\`, conservando `index.php` en la raíz.
3. Abrir `http://localhost/phpmyadmin` y, desde la página principal del servidor, seleccionar **Importar** y escoger `database.sql`. El archivo crea la base **biblioteca_palin**, cuatro tablas relacionadas (`libros`, `lectores`, `usuarios`, `prestamos`) y los datos de demostración: 10 libros, 10 lectores, 12 préstamos, 2 usuarios. **Importar una sola vez en una base nueva**, no sobre datos propios existentes.
4. En XAMPP estándar se utiliza la conexión `127.0.0.1:3306`, usuario `root`, contraseña vacía. Para otros valores, copiar `config/database.local.example.php` a `config/database.local.php` y ajustar; **no subir** este archivo a Git. También se admiten variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`.
5. Abrir **http://localhost/biblioteca-palin/** e iniciar sesión con las credenciales de demostración.

**No hay que ejecutar comandos adicionales** para la interfaz. CSS y JavaScript están en `public/`, sin CDN.

### Usuarios de demostración

| Cuenta | Contraseña local de demostración | Rol |
| --- | --- | --- |
| `bibliotecario` | `Biblioteca2026!` | Bibliotecario |
| `auxiliar` | `Auxiliar2026!` | Auxiliar |

Las claves del SQL son **hash bcrypt**, no texto plano. Son cuentas ficticias: reemplazarlas antes de usar una instalación fuera del aula.

## Arquitectura MVC y archivos

```text
biblioteca-palin/
├── index.php                       Front controller: rutas, roles, CSRF y cabeceras
├── config/                         Conexión PDO, autenticación, rate limit y auditoría
├── controllers/                    Auth, Prestamo, Catalogo, Lector, Usuarios, Reporte
├── models/                         Reglas de negocio y consultas parametrizadas
├── views/                          Plantillas PHP, formularios, confirmaciones y reportes
│   ├── layout/
│   ├── auth/  ├── prestamos/  ├── catalogo/
│   ├── lectores/  ├── usuarios/  └── reportes/
├── public/css/app.css              Diseño responsive y recursos sin Internet
├── public/js/app.js                AJAX con fetch y textContent
├── database.sql                    Esquema reproducible + datos de prueba
├── tests/                           Pruebas de integración, HTTP y seguridad
├── docs/                            Capturas y checklist del checkpoint 2
└── README.md                        Instalación, requisitos y defensa
```

Todas las páginas internas pasan por `index.php`. Las vistas no ejecutan SQL; los controladores no construyen HTML; los modelos manejan PDO y la lógica de negocio. El operador y la multa se establecen **en el servidor**, no desde el formulario.

### Seguridad

- Consultas parametrizadas PDO (emulación de prepares desactivada); validación de tipos, rangos y longitudes.
- Escape HTML centralizado: `e()`/ `htmlspecialchars(..., ENT_QUOTES)`; en JS se usa `textContent`, no `innerHTML`.
- CSRF en **todos** los formularios POST y comprobación de método HTTP.
- `password_hash()` / `password_verify()`, protección de intentos de login (5 fallos/15 minutos) y bitácora interna.
- Sesión con regeneración, caducidad por inactividad, `HttpOnly`, `SameSite=Lax` y cookie `Secure` cuando hay HTTPS.
- Roles vigentes consultados en la BD, respuestas 401/403/404/405 según el caso; `X-Frame-Options: DENY`, CSP `frame-ancestors 'none'`, `nosniff`, `Referrer-Policy`.
- `storage/`, `config/`, `models/` y `tests/` no exponen información por Apache; no se entrega ningún log, contraseña local ni base real.

## Verificación

Con Apache y MySQL encendidos, desde la raíz del proyecto:

```powershell
C:\xampp\php\php.exe tests\integration.php
C:\xampp\php\php.exe tests\http.php
C:\xampp\php\php.exe tests\seguridad-semana17.php
```

La prueba de integración crea una BD temporal y la elimina; **no borra la BD principal**. La prueba HTTP trabaja sobre la instalación local y gestiona registros de prueba; ejecutar con datos ficticios. El conjunto existente fue probado durante Checkpoint 2 (47 integraciones y 67 HTTP); **después de añadir los módulos finales es necesario ejecutar de nuevo las pruebas**. Ver también `docs/seguridad-checkpoint-2.md` y `docs/checkpoint2-tarea9-2026.md`.

### Pasos para comprobar la entrega final

1. Iniciar sesión como bibliotecario, crear un libro y editarlo; intentar eliminar uno con historial (debe impedirlo).
2. Crear un lector y editarlo; comprobar eliminación de uno sin historial y desactivación de uno con préstamos.
3. Crear un usuario auxiliar, comprobar sus permisos y desactivarlo.
4. Registrar préstamo válido y demostrar: falta de ejemplares, límite de tres y lector moroso; registrar devolución y multa.
5. Buscar `Asturias` en Catálogo y observar **Network → Fetch/XHR → JSON 200**.
6. Ejecutar Reportes: préstamos vencidos al corte y los diez libros más solicitados de un mes.
7. Iniciar sesión como auxiliar; intentar entrar directamente a `?c=usuarios` o `?c=reportes` (403).

## Capturas y evidencias

El proyecto incluye capturas de inicio de sesión, préstamos, formulario, catálogo AJAX, vista móvil y acceso restringido en `docs/capturas/`. El checklist de Semana 17 está en `docs/seguridad-checkpoint-2.md`. No se presentan las nuevas pruebas finales como ejecutadas hasta volver a probarlas en XAMPP.

## Control de versiones y entrega

Repositorio: https://github.com/danielbenito4-svg/biblioteca-municipal-palin

Etiquetas históricas: `checkpoint-1`, `checkpoint-2`. Para la entrega final, crear la etiqueta **final** sobre el commit aceptado, y adjuntar el ZIP de ese commit más el enlace del repositorio. El historial se distribuye durante octubre y noviembre y documenta las mejoras de cada fase.

## Guion de defensa (10 minutos)

**4 minutos — demostración:** login con dos roles; CRUD; reglas 7 días, tres préstamos, mora; AJAX y dos reportes. **3 minutos — código:** `index.php` (enrutamiento y CSRF), `models/Prestamo.php` (reglas y PDO), `controllers/CatalogoController.php` y `public/js/app.js` (JSON + fetch). **3 minutos — preguntas:** explicar consultas preparadas, autorización, XSS, rate limit y realizar un cambio menor controlado. Tener XAMPP y BD preparados **antes de las 8:00**; universidad sin Internet.

## Uso de inteligencia artificial

Se utilizó ChatGPT/Codex como apoyo para analizar los requisitos, implementar y depurar módulos, explicar decisiones técnicas, crear pruebas y redactar documentación. El estudiante revisa el código, practica la defensa y debe poder explicarlo y modificarlo. No se usaron frameworks ni generadores de CRUD externos.


## Diseño adaptable para móviles y tabletas

En pantallas menores de 1200 px, la navegación lateral se transforma en un **menú desplegable «Menú»**. Se abre con el botón superior y se cierra con «×», tocando fuera o con la tecla Escape. En teléfonos (hasta 720 px), los listados de préstamos, libros, lectores, usuarios y reportes se convierten en tarjetas con etiquetas de campo, sin cortes ni desplazamiento horizontal de toda la página. La interfaz mantiene los permisos de cada rol: **el menú no concede autorización**; las rutas siguen comprobando los roles en PHP.

Si copias estos archivos a tu instalación XAMPP, sustituye solamente los archivos de código. **No vuelvas a importar `database.sql`** cuando ya tengas datos locales. En Edge/Brave del teléfono, recarga para mostrar los estilos nuevos (los recursos incluyen versión de caché `responsive-20261118`).
