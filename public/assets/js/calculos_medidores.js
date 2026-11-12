"use strict";

document.addEventListener("DOMContentLoaded", function() {
    let modalExito = document.getElementById('modalExitoAccion');
    if (modalExito && typeof bootstrap !== 'undefined') {
        new bootstrap.Modal(modalExito).show();
    }
});

/**
 * Filtra las tomas en el modal global de captura
 */
function filtrarTomas() {
    let input = document.getElementById("buscadorModal").value.toLowerCase();
    let select = document.getElementById("selectTomaGlobal");
    let options = select.getElementsByTagName("option");
    
    for (let i = 1; i < options.length; i++) { 
        let txtValue = options[i].textContent || options[i].innerText;
        if (txtValue.toLowerCase().indexOf(input) > -1) {
            options[i].hidden = false;
            options[i].style.display = ""; 
        } else {
            options[i].hidden = true;
            options[i].style.display = "none";
        }
    }
}

/**
 * Actualiza la ruta del formulario en el modal global y verifica si el mes ya tiene lectura
 */
function actualizarRutaFormulario() {
    let select = document.getElementById('selectTomaGlobal');
    let form = document.getElementById('formNuevaLecturaGlobal');
    let hidden = document.getElementById('toma_id_hidden');
    let id = select.value;
    
    if (id) {
        hidden.value = id;
        let baseUrl = form.getAttribute('data-action-base'); 
        form.action = baseUrl + "/" + id;
        verificarMesGlobal();
    } else {
        hidden.value = "";
        form.action = "";
    }
}

/**
 * Detecta en el modal global si el mes seleccionado ya tiene lectura (Modificar / Eliminar) o está vacío (Añadir)
 */
function verificarMesGlobal() {
    let selectToma = document.getElementById('selectTomaGlobal');
    let selectMes = document.getElementById('selectMesGlobal');
    let inputLectura = document.getElementById('inputLecturaGlobal');
    let alertaModo = document.getElementById('alertaModoGlobal');
    let btnSubmit = document.getElementById('btnSubmitGlobal');
    let btnEliminar = document.getElementById('btnEliminarGlobal');

    if (!selectToma || !selectMes || !selectToma.value || !selectMes.value) {
        if (alertaModo) alertaModo.classList.add('d-none');
        if (btnEliminar) btnEliminar.classList.add('d-none');
        return;
    }

    let opcionSeleccionada = selectToma.options[selectToma.selectedIndex];
    let lecturasJson = opcionSeleccionada.getAttribute('data-lecturas');
    let mapaLecturas = lecturasJson ? JSON.parse(lecturasJson) : {};
    let mes = selectMes.value;

    alertaModo.classList.remove('d-none', 'alert-warning', 'alert-info');

    if (mapaLecturas[mes] !== undefined && mapaLecturas[mes] !== null) {
        inputLectura.value = mapaLecturas[mes];
        alertaModo.classList.add('alert-warning');
        alertaModo.innerHTML = `<i class="bi bi-pencil-square me-1"></i> Este mes ya tiene lectura (<strong>${mapaLecturas[mes]} m³</strong>). Puedes <strong>modificarla</strong> o <strong>eliminarla</strong>.`;
        btnSubmit.innerText = 'Modificar Lectura';
        btnSubmit.classList.replace('btn-primary', 'btn-warning');
        if (btnEliminar) btnEliminar.classList.remove('d-none');
    } else {
        inputLectura.value = '';
        alertaModo.classList.add('alert-info');
        alertaModo.innerHTML = `<i class="bi bi-plus-circle me-1"></i> Mes sin registro previo. Se <strong>añadirá</strong> como nueva lectura.`;
        btnSubmit.innerText = 'Añadir Lectura';
        btnSubmit.classList.replace('btn-warning', 'btn-primary');
        if (btnEliminar) btnEliminar.classList.add('d-none');
    }
}

/**
 * Envía la eliminación desde el modal global
 */
function confirmarEliminarGlobal() {
    let selectToma = document.getElementById('selectTomaGlobal');
    let selectMes = document.getElementById('selectMesGlobal');
    let formDelete = document.getElementById('formEliminarLecturaGlobal');

    if (!selectToma.value || !selectMes.value) return;

    let nombreMes = selectMes.options[selectMes.selectedIndex].text;
    if (confirm(`¿Estás seguro de eliminar la lectura de ${nombreMes}?\n\nSe recalcularán automáticamente los demás meses del año.`)) {
        let baseUrl = formDelete.getAttribute('data-action-base');
        formDelete.action = `${baseUrl}/${selectToma.value}`;
        document.getElementById('deleteMesGlobal').value = selectMes.value;
        formDelete.submit();
    }
}

/**
 * Detecta en el modal individual de cada fila si el mes elegido es para Añadir, Modificar o Eliminar
 */
function detectarLecturaExistente(tomaId, mesSeleccionado) {
    let selectMes = document.getElementById(`selectMes_${tomaId}`);
    let inputLectura = document.getElementById(`inputLectura_${tomaId}`);
    let avisoMod = document.getElementById(`avisoModificacion_${tomaId}`);
    let badgeModo = document.getElementById(`badgeModoModal${tomaId}`);
    let btnGuardar = document.getElementById(`btnGuardar_${tomaId}`);
    let btnEliminar = document.getElementById(`btnEliminar_${tomaId}`);
    let inputMesEliminar = document.getElementById(`inputMesEliminar_${tomaId}`);

    if (!selectMes || !mesSeleccionado) {
        if (avisoMod) avisoMod.classList.add('d-none');
        if (badgeModo) badgeModo.classList.add('d-none');
        if (btnEliminar) btnEliminar.classList.add('d-none');
        inputLectura.value = '';
        return;
    }

    let opcion = selectMes.options[selectMes.selectedIndex];
    let lecturaExistente = opcion.getAttribute('data-lectura');

    badgeModo.classList.remove('d-none', 'bg-warning', 'text-dark', 'bg-success');

    if (lecturaExistente && lecturaExistente !== '') {
        // MODO MODIFICAR O ELIMINAR LECTURA EXISTENTE
        inputLectura.value = lecturaExistente;
        if (inputMesEliminar) inputMesEliminar.value = mesSeleccionado;
        avisoMod.classList.remove('d-none');
        badgeModo.classList.add('bg-warning', 'text-dark');
        badgeModo.innerText = 'MODO: MODIFICAR / ELIMINAR';
        btnGuardar.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Modificar';
        btnGuardar.classList.replace('btn-primary', 'btn-warning');
        if (btnEliminar) btnEliminar.classList.remove('d-none');
    } else {
        // MODO AÑADIR NUEVA LECTURA
        inputLectura.value = '';
        if (inputMesEliminar) inputMesEliminar.value = '';
        avisoMod.classList.add('d-none');
        badgeModo.classList.add('bg-success');
        badgeModo.innerText = 'MODO: AÑADIR';
        btnGuardar.innerHTML = '<i class="bi bi-check-lg me-1"></i>Añadir';
        btnGuardar.classList.replace('btn-warning', 'btn-primary');
        if (btnEliminar) btnEliminar.classList.add('d-none');
    }

    inputLectura.focus();
}

/**
 * Confirma y ejecuta la eliminación de la lectura desde el modal individual de la fila
 */
function confirmarEliminarLectura(tomaId) {
    let selectMes = document.getElementById(`selectMes_${tomaId}`);
    let formEliminar = document.getElementById(`formEliminar_${tomaId}`);
    if (!selectMes || !selectMes.value || !formEliminar) return;

    let textoMes = selectMes.options[selectMes.selectedIndex].text.split(' (')[0];

    if (confirm(`¿Estás seguro de eliminar la lectura del mes de ${textoMes}?\n\nEsta acción borrará el registro y recalculará los consumos del año.`)) {
        formEliminar.submit();
    }
}

function validarEnvioModalGlobal() {
    let hiddenId = document.getElementById('toma_id_hidden').value;
    if (!hiddenId) {
        alert("Seleccione un usuario de la lista de resultados.");
        return false;
    }
    return true;
}

/**
 * Envía la eliminación directamente desde el modal global y muestra el modal de éxito de Laravel
 */
function confirmarEliminarGlobal() {
    let selectToma = document.getElementById('selectTomaGlobal');
    let selectMes = document.getElementById('selectMesGlobal');
    let formDelete = document.getElementById('formEliminarLecturaGlobal');

    if (!selectToma.value || !selectMes.value) return;

    let baseUrl = formDelete.getAttribute('data-action-base');
    formDelete.action = `${baseUrl}/${selectToma.value}`;
    document.getElementById('deleteMesGlobal').value = selectMes.value;
    formDelete.submit();
}

/**
 * Ejecuta la eliminación directamente desde el modal individual y muestra la palomita verde
 */
function confirmarEliminarLectura(tomaId) {
    let selectMes = document.getElementById(`selectMes_${tomaId}`);
    let formEliminar = document.getElementById(`formEliminar_${tomaId}`);
    if (!selectMes || !selectMes.value || !formEliminar) return;

    formEliminar.submit();
}