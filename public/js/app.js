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

const catalogo = document.querySelector('[data-catalogo]');
if (catalogo) {
    const input = document.getElementById('catalogo-q');
    const status = document.getElementById('catalogo-estado');
    const results = document.getElementById('catalogo-resultados');
    let timer;
    let request;
    let version = 0;

    function element(tag, className, text) {
        const node = document.createElement(tag);
        node.className = className;
        node.textContent = text; // Los datos de la BD nunca se interpretan como HTML.
        return node;
    }

    function showBooks(books) {
        const fragment = document.createDocumentFragment();
        for (const book of books) {
            const card = element('article', 'card book-card', '');
            card.append(element('span', 'eyebrow', book.categoria));
            card.append(element('h2', '', book.titulo));
            card.append(element('p', 'book-author', book.autor));
            card.append(element('small', '', `ISBN ${book.isbn} · ${book.anio}`));
            const available = Number(book.disponibles);
            const foot = element('div', 'book-card-foot', '');
            foot.append(element('span', `status ${available > 0 ? 'activo' : 'vencido'}`,
                available > 0 ? `${available} de ${book.ejemplares_totales} disponibles` : 'Sin ejemplares disponibles'));
            if (catalogo.dataset.admin === '1') {
                const link = element('a', 'text-link', 'Editar libro');
                const url = new URL(catalogo.dataset.editUrl, window.location.href);
                url.searchParams.set('id', book.id);
                link.href = url.href;
                link.setAttribute('aria-label', `Editar ${book.titulo}`);
                foot.append(link);
            }
            card.append(foot);
            fragment.append(card);
        }
        results.replaceChildren(fragment);
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        request?.abort();
        const current = ++version;
        const query = input.value.trim();
        results.replaceChildren();
        results.setAttribute('aria-busy', 'false');
        if (query.length < 3) {
            status.textContent = 'Escribe al menos 3 caracteres para buscar.';
            return;
        }
        status.textContent = 'Buscando libros…';
        results.setAttribute('aria-busy', 'true');
        timer = setTimeout(async () => {
            const pending = new AbortController();
            request = pending;
            const timeout = setTimeout(() => pending.abort(), 10000);
            try {
                const url = new URL(catalogo.dataset.endpoint, window.location.href);
                url.searchParams.set('q', query);
                const response = await fetch(url, {signal: pending.signal,
                    credentials: 'same-origin', headers: {'Accept': 'application/json'}, cache: 'no-store'});
                const data = await response.json();
                if (current !== version) return;
                if (response.status === 401) {
                    status.textContent = 'Tu sesión terminó. Inicia sesión nuevamente para buscar.';
                    const link = element('a', 'button secondary', 'Iniciar sesión');
                    link.href = catalogo.dataset.loginUrl;
                    results.append(link);
                    return;
                }
                if (!response.ok) throw new Error('No fue posible consultar el catálogo.');
                showBooks(data.libros);
                status.textContent = data.libros.length === 0 ? 'No encontramos libros para esa búsqueda.'
                    : `${data.libros.length} resultado${data.libros.length === 1 ? '' : 's'}`
                        + (data.libros.length === data.limite ? ' · Se muestran hasta 30. Afina tu búsqueda.' : ' · Disponibilidad actualizada.');
            } catch (error) {
                if (current !== version) return;
                status.textContent = 'No pudimos consultar el catálogo. Revisa la conexión y vuelve a escribir.';
            } finally {
                clearTimeout(timeout);
                if (current === version) results.setAttribute('aria-busy', 'false');
            }
        }, 300);
    });
}

// Navegación accesible para móviles y tabletas. La autorización sigue en el servidor.
(() => {
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.getElementById('sidebar-menu');
    const close = document.querySelector('.menu-close');
    const backdrop = document.querySelector('.sidebar-backdrop');
    if (!toggle || !sidebar || !close || !backdrop) return;
    const mobile = window.matchMedia('(max-width: 1199px)');
    let previousFocus = null;
    const focusable = () => [...sidebar.querySelectorAll('a[href], button:not([disabled])')];
    function syncNavigation() {
        const open = mobile.matches && document.body.classList.contains('nav-open');
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Cerrar menú principal' : 'Abrir menú principal');
        sidebar.inert = mobile.matches && !open;
        sidebar.setAttribute('aria-hidden', String(mobile.matches && !open));
        backdrop.hidden = !open;
    }
    function openNavigation() {
        if (!mobile.matches) return;
        previousFocus = document.activeElement;
        document.body.classList.add('nav-open');
        syncNavigation();
        close.focus();
    }
    function closeNavigation(restoreFocus = true) {
        const wasOpen = document.body.classList.contains('nav-open');
        document.body.classList.remove('nav-open');
        syncNavigation();
        if (restoreFocus && wasOpen && mobile.matches) {
            (previousFocus instanceof HTMLElement ? previousFocus : toggle).focus();
        }
    }
    toggle.addEventListener('click', () => {
        document.body.classList.contains('nav-open') ? closeNavigation() : openNavigation();
    });
    close.addEventListener('click', () => closeNavigation());
    backdrop.addEventListener('click', () => closeNavigation());
    sidebar.querySelectorAll('nav a').forEach(link => {
        link.addEventListener('click', () => closeNavigation(false));
    });
    document.addEventListener('keydown', event => {
        if (!document.body.classList.contains('nav-open')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            closeNavigation();
        } else if (event.key === 'Tab') {
            const items = focusable();
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
    mobile.addEventListener('change', () => closeNavigation(false));
    syncNavigation();
})();