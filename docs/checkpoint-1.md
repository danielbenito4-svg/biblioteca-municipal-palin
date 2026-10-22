# Demostración del checkpoint 1

Proyecto 4: Biblioteca Municipal de Palín. Entrega: 25 de octubre de 2026. Duración: 3 minutos.

## Preparación

Iniciar Apache y MySQL. Abrir `http://localhost/biblioteca-palin/`, el proyecto en VS Code y la estructura de `biblioteca_palin` en phpMyAdmin. Comprobar que aparezcan los 12 préstamos iniciales si es una instalación nueva.

Usar **La Odisea** y **Lucía Gómez (L-004)** para la demostración. En los datos iniciales ese libro tiene dos ejemplares libres y la lectora no tiene préstamos activos. Si ya se practicó, eliminar únicamente el registro creado durante la práctica para volver a contar con esa disponibilidad.

## Recorrido de tres minutos

| Tiempo | Acción | Explicación sugerida |
| --- | --- | --- |
| 0:00–0:20 | Mostrar las carpetas en VS Code. | «Uso MVC. index.php dirige la solicitud; el controlador recibe los datos; los modelos consultan la base y aplican las reglas; las vistas muestran HTML». |
| 0:20–0:40 | Mostrar las cuatro tablas en phpMyAdmin y `database.sql`. | «La tabla principal es prestamos. Tiene claves foráneas hacia libros, lectores y usuarios. Incluí doce préstamos de prueba». |
| 0:40–1:20 | Pulsar **Nuevo préstamo**, elegir La Odisea y Lucía Gómez; conservar la fecha de hoy y guardar. | «El servidor comprueba disponibilidad, límite de préstamos y atrasos. Calcula una fecha límite de siete días y registra el préstamo». |
| 1:20–1:45 | Buscar `L-004` y elegir **Sin devolver**. | «El listado permite buscar y filtrar. Los datos se consultan con parámetros». |
| 1:45–2:15 | Editar el préstamo recién creado; poner la fecha de hoy en devolución y guardar. | «La edición conserva el mismo identificador. Al devolver a tiempo, la multa es cero y el ejemplar queda disponible». |
| 2:15–2:40 | Abrir la confirmación de eliminación del registro creado y confirmar. | «La primera pantalla pide confirmación; la eliminación ocurre mediante POST y un token CSRF». |
| 2:40–3:00 | Mostrar la etiqueta `checkpoint-1` en GitHub y señalar `models/Prestamo.php`. | «El avance está versionado. Las reglas y las consultas preparadas están en el modelo y las vistas no contienen SQL». |

## Casos de validación para practicar

- **Sin ejemplares (RN-01):** intentar prestar Hombres de maíz a Lucía Gómez. El único ejemplar está prestado.
- **Máximo de tres (RN-02):** intentar prestar La Odisea a Roberto Díaz (L-003).
- **Lector con atraso (RN-04):** intentar prestar La Odisea a Carlos Pérez (L-002).
- **Multa (RN-03):** en un préstamo de práctica, poner una fecha de préstamo diez días anterior a hoy y devolver hoy. El límite será hace tres días y la multa será Q 6.00. Eliminar después ese registro de práctica.
- **Validación:** enviar fechas inválidas desde el navegador o una devolución anterior al préstamo. HTML ayuda al usuario y PHP vuelve a validar los datos.

Las fechas relativas se refieren al día en que se realiza cada práctica. La carga inicial incluye los casos descritos, pero los vencimientos cambian al pasar los días.

## Qué explicar del código

- `index.php`: lista de rutas, método HTTP permitido y comprobación del token CSRF.
- `controllers/PrestamoController.php`: recoge y normaliza los campos; llama al modelo y elige una vista o una redirección.
- `models/Prestamo.php`: consultas preparadas, validación de fechas y reglas RN-01 a RN-04. La transacción y los bloqueos de libro/lector coordinan los registros concurrentes.
- `models/Catalogo.php`: opciones para los selectores y cálculo de disponibilidad.
- `views/prestamos/`: las páginas no tienen SQL; usan `e()` al mostrar datos.
- `config/Conexion.php`: PDO y manejo de la conexión; las credenciales están fuera de las vistas.

El checkpoint 1 funciona localmente con el operador de prueba id 1. El login, los permisos por rol y AJAX se presentan en el checkpoint 2.

## Verificación antes de clase

1. Iniciar servicios y abrir el listado.
2. Comprobar que el código corresponda a la etiqueta `checkpoint-1`.
3. Practicar crear, buscar, editar, devolver y eliminar el registro de prueba.
4. Tener `database.sql` y este guion disponibles localmente.
5. Ejecutar las pruebas si se hizo algún cambio después de esta entrega.
