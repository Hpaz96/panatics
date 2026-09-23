// =============================================
// PANATICS - Zonas de falla (Hardware / Software)
// según el tipo de equipo seleccionado.
// Categorías: pc, videojuegos, controles, otro
// =============================================

const ZONAS_FALLA = {
    'pc': {
        h: ['Fuente de alimentación', 'Memoria RAM', 'Disco duro o SSD', 'Placa base (Tarjeta madre)',
            'Tarjeta gráfica (GPU)', 'Sistema de ventilación (Coolers y disipadores)', 'Procesador (CPU)',
            'Puertos físicos (USB, Audio, HDMI)', 'Botón de encendido y cables frontales',
            'Pasta térmica (Degradación)', 'Batería', 'Cargador y cable de alimentación',
            'Puerto de carga (Jack DC)', 'Pantalla (Panel LCD/LED)', 'Cable flexible de video (Flex de pantalla)',
            'Teclado y Touchpad', 'Bisagras y carcasas plásticas', 'Ventilador interno y rejillas de ventilación', 'Ninguno'],
        s: ['Sistema operativo', 'Controladores (Drivers)', 'Registro del sistema', 'Actualizaciones',
            'Programas de inicio y servicios en segundo plano', 'Memoria caché y archivos temporales del sistema',
            'Instalación de programas', 'Firmware y BIOS/UEFI', 'Ninguno']
    },
    'videojuegos': {
        h: ['Puerto HDMI', 'Sistema de metal líquido / Pasta térmica', 'Ventilador interno y rejillas de ventilación',
            'Fuente de alimentación interna', 'Unidad lectora', 'Chip de control de video', 'Almacenamiento',
            'Puerto de carga', 'Módulos de conectividad inalámbrica (Bluetooth y Wi-Fi)', 'Batería interna',
            'Corto interno', 'Ninguno'],
        s: ['Sistema operativo de la consola', 'Recuperación de información', 'Ninguno']
    },
    'controles': {
        h: ['Palancas analógicas', 'Mecanismos de los gatillos', 'Almohadillas de goma conductoras', 'Batería interna',
            'Puerto de carga', 'Placa de circuito impreso central', 'Botones superiores', 'Motores de vibración',
            'Conector de audio Jack de 3.5 mm', 'Antena o chip Bluetooth interno', 'Ninguno'],
        s: ['Mapeo de botones', 'Sincronización Bluetooth', 'Calibración del giroscopio y acelerómetro',
            'Modo de ahorro de energía', 'Ninguno']
    },
    'otro': {
        h: ['Cambio de componente', 'Ninguno'],
        s: ['Cambio de sistema', 'Ninguno']
    }
};

// Mapea el tipo de equipo a una categoría de zonas de falla
function categoriaEquipoZona(tipoEquipo) {
    const t = (tipoEquipo || '').toLowerCase();
    if (t.indexOf('escritorio') !== -1 || t.indexOf('laptop') !== -1 || t.indexOf('todo en uno') !== -1) return 'pc';
    if (t.indexOf('control') !== -1) return 'controles';
    if (t.indexOf('videojuegos') !== -1 || t.indexOf('consola') !== -1) return 'videojuegos';
    return 'otro';
}

// Llena un <select> respetando el valor guardado en data-valor
function llenarSelectZonas(sel, opciones) {
    const prev = sel.getAttribute('data-valor') || '';
    sel.innerHTML = '';
    opciones.forEach(function (op) {
        const o = document.createElement('option');
        o.value = op;
        o.textContent = op;
        if (prev && prev === op) o.selected = true;
        sel.appendChild(o);
    });
}

// Puebla los selects zona_falla_h y zona_falla_s según el tipo de equipo
function poblarZonas(tipoEquipo) {
    const lista = ZONAS_FALLA[categoriaEquipoZona(tipoEquipo)] || { h: [], s: [] };
    const sH = document.getElementById('zona_falla_h');
    const sS = document.getElementById('zona_falla_s');
    if (sH) llenarSelectZonas(sH, lista.h);
    if (sS) llenarSelectZonas(sS, lista.s);
}

// Puebla los selects directamente con una categoría conocida
function poblarZonasDirecto(categoria) {
    const lista = ZONAS_FALLA[categoria] || { h: [], s: [] };
    const sH = document.getElementById('zona_falla_h');
    const sS = document.getElementById('zona_falla_s');
    if (sH) llenarSelectZonas(sH, lista.h);
    if (sS) llenarSelectZonas(sS, lista.s);
}

// Inicializa el comportamiento según el <select> de equipo/reparación dado
// (data-zona contiene el tipo de equipo en cada opción)
function initZonas(selectId) {
    const sel = document.getElementById(selectId);
    if (!sel) return;

    function actualizar() {
        const opcion = sel.options[sel.selectedIndex];
        const tipo = opcion ? (opcion.getAttribute('data-zona') || 'otro') : 'otro';
        // Prellenar con los valores que ya trae la opción (edición)
        if (opcion) {
            const zH = document.getElementById('zona_falla_h');
            const zS = document.getElementById('zona_falla_s');
            if (zH && opcion.hasAttribute('data-zh')) zH.setAttribute('data-valor', opcion.getAttribute('data-zh'));
            if (zS && opcion.hasAttribute('data-zs')) zS.setAttribute('data-valor', opcion.getAttribute('data-zs'));
        }
        poblarZonas(tipo);
    }

    sel.addEventListener('change', function () {
        const opcion = sel.options[sel.selectedIndex];
        // Al cambiar, limpiar valores previos
        document.getElementById('zona_falla_h').setAttribute('data-valor', '');
        document.getElementById('zona_falla_s').setAttribute('data-valor', '');
        actualizar();
    });
    actualizar();
}