'use strict';
// Ayuda visual; las fechas y reglas se vuelven a validar en PHP.
const prestamo = document.getElementById('fecha_prestamo');
const devolucion = document.getElementById('fecha_devolucion');
const preview = document.getElementById('fecha-limite-preview');
function actualizarPlazo() {
    if (!prestamo || !preview) return;
    if (devolucion) devolucion.min = prestamo.value || '1900-01-01';
    const fecha = new Date(prestamo.value + 'T12:00:00');
    if (Number.isNaN(fecha.getTime())) {
        preview.textContent = 'Se calcula al guardar';
        return;
    }
    fecha.setDate(fecha.getDate() + 7);
    preview.textContent = fecha.toLocaleDateString('es-GT', {day:'2-digit', month:'2-digit', year:'numeric'});
}
if (prestamo) {
    prestamo.addEventListener('change', actualizarPlazo);
    actualizarPlazo();
}
