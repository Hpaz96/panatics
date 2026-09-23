// =============================================
// PANATICS - Archivo de utilidades JS
// =============================================

// Opciones de marcas según el tipo de equipo
const MARCAS = {
    'Computadora de escritorio': ['Lenovo','HP','Dell','Acer','Apple','Asus','MSI','Alienware','Corsair','CyberPowerPC','iBuyPower','Microsoft','Huawei','Geekom','Ghia','otro'],
    'Todo en uno': ['HP','Apple','Lenovo','Dell','ASUS','Acer','MSI','Microsoft','Huawei','LG','Samsung','Ghia','Vorago','Lanix','Balam Rush','otro'],
    'Laptop': ['Lenovo','HP','Dell','Apple','ASUS','Acer','MSI','Microsoft','Huawei','Samsung','Razer','Gigabyte','LG','Gateway','Chuwi','otro'],
    'Videojuegos': ['PlayStation 4','PlayStation 4 pro','PlayStation 4 fat','PlayStation 5 fat','PlayStation 5','PlayStation 5 Pro','Nintendo Switch','Nintendo Switch oled','Nintendo Switchlite','Nintendo Switch 2','Xbox one','Xbox Series X','Xbox SeriesS','otros'],
    'Controles videojuegos': ['PlayStation 4','PlayStation 4 pro','PlayStation 4 fat','PlayStation 5 fat','PlayStation 5','PlayStation 5 Pro','Nintendo Switch','Nintendo Switch oled','Nintendo Switchlite','Nintendo Switch 2','Xbox one','Xbox Series X','Xbox SeriesS','otros'],
    'Otro': ['otro']
};

const TIPOS_EQUIPO = Object.keys(MARCAS);

// Versión PHP para el caso de tener datos ya cargados en el formulario
var MARCAS_PHP = {};
var TIPO_ACTUAL = '';

// Llena el select de marcas según el tipo de equipo seleccionado
function cargarMarcas(tipoSeleccionado) {
    const tipo = tipoSeleccionado || document.getElementById('tipo_equipo').value;
    const selMarca = document.getElementById('marca');
    if (!selMarca) return;

    let marcas = MARCAS[tipo] || MARCAS_PHP[tipo] || ['otro'];

    // Limpiar
    selMarca.innerHTML = '';

    // Si existe un valor previo mantenido (edición), intentar conservarlo
    selectOptions(selMarca, marcas);
}

function selectOptions(select, opciones) {
    const previo = select.getAttribute('data-valor') || '';
    opciones.forEach(function (op) {
        const opt = document.createElement('option');
        opt.value = op;
        opt.textContent = op;
        if (previo && op === previo) opt.selected = true;
        select.appendChild(opt);
    });
}

// =============================================
// PANATICS - Cálculo de saldo pendiente en PAGO
// =============================================
function calcularSaldo() {
    const monto = parseFloat(document.getElementById('monto').value) || 0;
    const anticipo = parseFloat(document.getElementById('anticipo').value) || 0;
    const saldo = monto - anticipo;
    const elSaldo = document.getElementById('saldo_pendiente');
    const elTotal = document.getElementById('costo_total') || null; // si está en el mismo form
    if (elSaldo) {
        elSaldo.value = saldo >= 0 ? saldo.toFixed(2) : '0.00';
    }
}

// =============================================
// PANATICS - Descuento de estudiante (25%)
// =============================================
function aplicarDescuentoEstudiante() {
    const check = document.getElementById('descuento_estudiante');
    const monto = document.getElementById('monto');
    const anticipo = document.getElementById('anticipo');
    const selRep = document.getElementById('Id_reparacion');
    if (!check || !monto) return;

    let base = 0;
    if (selRep && selRep.selectedIndex >= 0) {
        base = parseFloat(selRep.options[selRep.selectedIndex].getAttribute('data-costo')) || 0;
    }

    if (check.checked) {
        if (!monto.getAttribute('data-original')) {
            monto.setAttribute('data-original', monto.value);
        }
        const aBase = base || parseFloat(monto.getAttribute('data-original')) || 0;
        monto.value = (aBase * 0.75).toFixed(2);
    } else {
        const orig = monto.getAttribute('data-original');
        monto.value = (orig !== null && orig !== '') ? orig : (base ? base.toFixed(2) : monto.value);
        monto.removeAttribute('data-original');
    }
    if (anticipo && anticipo.value === '') anticipo.value = '0.00';
    calcularSaldo();
}

// =============================================
// PANATICS - Botón "Siguiente" del flujo guiado
// El botón siempre envía el formulario; la
// validación de campos requeridos la hace el
// navegador (atributo required). Si el formulario
// sube imágenes, se exige al menos 1 archivo.
// =============================================
function initSiguiente(formId) {
    const f = document.getElementById(formId);
    if (!f) return;

    f.addEventListener('submit', function (e) {
        if (!f.checkValidity()) return;
        const archivos = f.querySelectorAll('input[type="file"]');
        if (archivos.length > 0) {
            let conArchivo = false;
            archivos.forEach(function (el) {
                if (el.files && el.files.length > 0 && el.files[0].name !== '') conArchivo = true;
            });
            if (!conArchivo) {
                e.preventDefault();
                alert('Selecciona al menos una foto (puedes subir hasta 5 vistas del equipo).');
            }
        }
    });
}

// =============================================
// Inicialización cuando carga el documento
// =============================================
document.addEventListener('DOMContentLoaded', function () {
    // Selector de tipo de equipo
    const selTipo = document.getElementById('tipo_equipo');
    if (selTipo) {
        const previa = selTipo.getAttribute('data-valor') || '';
        // llenar tipos
        const tipos = Object.keys(MARCAS).length > 0 ? Object.keys(MARCAS) : TIPOS_EQUIPO;
        // construir opciones de tipo
        let tipoOptions = '';
        tipos.forEach(function (t) {
            const sel = (previa && t === previa) ? 'selected' : '';
            tipoOptions += '<option value="' + t + '" ' + sel + '>' + t + '</option>';
        });
        selTipo.innerHTML = tipoOptions;

        selTipo.addEventListener('change', function () {
            document.getElementById('marca').setAttribute('data-valor', '');
            cargarMarcas(this.value);
        });
        cargarMarcas(previa);
    }

    // Cálculo de saldo en formulario de pago
    const monto = document.getElementById('monto');
    const anticipo = document.getElementById('anticipo');
    const selRep = document.getElementById('Id_reparacion');
    const chkDesc = document.getElementById('descuento_estudiante');
    if (chkDesc) {
        chkDesc.addEventListener('change', aplicarDescuentoEstudiante);
    }
    if (monto && anticipo) {
        monto.addEventListener('input', calcularSaldo);
        anticipo.addEventListener('input', calcularSaldo);

        // Al seleccionar reparación, precargar el monto con su costo total
        if (selRep) {
            selRep.addEventListener('change', function () {
                const opcion = this.options[this.selectedIndex];
                const costo = opcion ? parseFloat(opcion.getAttribute('data-costo')) || 0 : 0;
                monto.value = costo.toFixed(2);
                calcularSaldo();
            });
        }

        // Recalcular si hay costo_total (auto-cálculo de monto)
        const costo = document.getElementById('costo_total');
        if (costo) {
            costo.addEventListener('input', function () {
                if (monto.value === '' || monto.value === '0') {
                    monto.value = costo.value;
                }
                calcularSaldo();
            });
        }
    }
});
