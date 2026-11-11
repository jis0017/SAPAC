let modalEliminarCuotaBS;
let modalEliminarPredioBS;
let modalEliminarTarifaMedidaBS;

document.addEventListener("DOMContentLoaded", function() {
    const elModalEliminarC = document.getElementById('modalEliminarCuota');
    if (typeof bootstrap !== 'undefined' && elModalEliminarC) {
        modalEliminarCuotaBS = new bootstrap.Modal(elModalEliminarC);
    }
    const elModalEliminarP = document.getElementById('modalEliminarPredio');
    if (typeof bootstrap !== 'undefined' && elModalEliminarP) {
        modalEliminarPredioBS = new bootstrap.Modal(elModalEliminarP);
    }
    const elModalEliminarTM = document.getElementById('modalEliminarTarifaMedida');
    if (typeof bootstrap !== 'undefined' && elModalEliminarTM) {
        modalEliminarTarifaMedidaBS = new bootstrap.Modal(elModalEliminarTM);
    }
    
    const modalExito = document.getElementById('modalExitoAccion');
    if (typeof bootstrap !== 'undefined' && modalExito) {
        new bootstrap.Modal(modalExito).show();
    }
});

// ==========================================
// FUNCIONES PARA CUOTAS (Pestaña 1)
// ==========================================
function calcularValoresAnuales() {
    let cuotaMes = parseFloat(document.getElementById('cuota_mensual').value) || 0;
    let saneamientoMes = parseFloat(document.getElementById('saneamiento_mensual').value) || 0;
    let otrosUsosMes = parseFloat(document.getElementById('otros_usos_mensual').value) || 0;

    let totalAnualSinDesc = (cuotaMes + saneamientoMes + otrosUsosMes) * 12;
    document.getElementById('total_anual_sin_descuento').value = totalAnualSinDesc.toFixed(2);

    let pDesc1 = parseFloat(document.getElementById('porcentaje_descto1').value) || 0;
    let totalDesc1 = totalAnualSinDesc - (totalAnualSinDesc * (pDesc1 / 100));
    document.getElementById('total_anual_descto1').value = totalDesc1.toFixed(2);

    let pDesc2 = parseFloat(document.getElementById('porcentaje_descto2').value) || 0;
    let totalDesc2 = totalAnualSinDesc - (totalAnualSinDesc * (pDesc2 / 100));
    document.getElementById('total_anual_descto2').value = totalDesc2.toFixed(2);
}

function prepararCreacionCuota() {
    document.getElementById('tituloModalCuota').innerText = 'Configurar Nueva Cuota y Periodo Anual';
    document.getElementById('methodFieldContainer').innerHTML = '';
    document.getElementById('btnGuardarModalCuota').innerText = 'Guardar Cuota y Periodo';
    document.getElementById('formCuotaPeriodo').reset();
    document.getElementById('anio').value = new Date().getFullYear();
    document.getElementById('costo_nuevo_contrato').value = '717.99';
    document.getElementById('costo_reconexion').value = '350.00';
    document.getElementById('porcentaje_descto1').value = '15.00';
    document.getElementById('porcentaje_descto2').value = '50.00';
    calcularValoresAnuales();
}

function abrirModalEditar(id, anio, tipoPredioId, localidadId, cuotaMensual, saneamiento, otrosUsos, totalSinDesc, pDesc1, tDesc1, pDesc2, tDesc2, costoContrato, costoReconexion) {
    document.getElementById('tituloModalCuota').innerText = 'Actualizar Cuota y Periodo Anual';
    document.getElementById('formCuotaPeriodo').action = `/configuracion/cuotas/${id}`;
    document.getElementById('methodFieldContainer').innerHTML = '@method("PUT")';
    document.getElementById('btnGuardarModalCuota').innerText = 'Actualizar Cuota';

    document.getElementById('anio').value = anio;
    document.getElementById('id_tipo_predio').value = tipoPredioId;
    document.getElementById('id_localidad').value = localidadId ? localidadId : '';
    document.getElementById('cuota_mensual').value = cuotaMensual;
    document.getElementById('saneamiento_mensual').value = saneamiento;
    document.getElementById('otros_usos_mensual').value = otrosUsos;
    document.getElementById('total_anual_sin_descuento').value = totalSinDesc;
    document.getElementById('porcentaje_descto1').value = pDesc1;
    document.getElementById('total_anual_descto1').value = tDesc1;
    document.getElementById('porcentaje_descto2').value = pDesc2;
    document.getElementById('total_anual_descto2').value = tDesc2;
    document.getElementById('costo_nuevo_contrato').value = costoContrato;
    document.getElementById('costo_reconexion').value = costoReconexion;

    var modalCuota = new bootstrap.Modal(document.getElementById('modalCuotaPeriodo'));
    modalCuota.show();
}

function abrirModalEliminarCuota(idCuota, anio, nombrePredio) {
    document.getElementById('anioCuotaEliminar').innerText = anio;
    document.getElementById('predioCuotaEliminar').innerText = nombrePredio;
    document.getElementById('formEliminarCuota').action = `/configuracion/cuotas/${idCuota}`;
    modalEliminarCuotaBS.show();
}

// ==========================================
// FUNCIONES PARA TARIFAS MEDIDAS (Pestaña 2 - Actualizado)
// ==========================================
function prepararCreacionTarifaMedida() {
    document.getElementById('tm_id_edicion').value = '';
    document.getElementById('tituloModalTarifaMedida').innerText = 'Configurar Nuevo Rango de Servicio Medido (m³)';
    document.getElementById('methodTarifaMedidaContainer').innerHTML = '';
    document.getElementById('btnGuardarTarifaMedida').innerText = 'Guardar Rango Medido';
    document.getElementById('formTarifaMedida').reset();
    document.getElementById('tm_anio_vigencia').value = new Date().getFullYear();
}

function abrirModalEditarTarifaMedida(id, anio, tipoPredioId, m3Base, cuotaBase, limInf, limSup, precioM3) {
    document.getElementById('tm_id_edicion').value = id;
    document.getElementById('tituloModalTarifaMedida').innerText = 'Actualizar Rango de Servicio Medido (m³)';
    document.getElementById('formTarifaMedida').action = `/configuracion/tarifas-medidas/${id}`;
    document.getElementById('methodTarifaMedidaContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('btnGuardarTarifaMedida').innerText = 'Actualizar Rango';

    document.getElementById('tm_anio_vigencia').value = anio;
    document.getElementById('tm_id_tipo_predio').value = tipoPredioId;
    document.getElementById('tm_m3_base').value = m3Base;
    document.getElementById('tm_cuota_base').value = cuotaBase;
    document.getElementById('tm_limite_inferior').value = limInf;
    document.getElementById('tm_limite_superior').value = limSup !== '' ? limSup : '';
    document.getElementById('tm_precio_m3_excedente').value = precioM3;

    var modalTM = new bootstrap.Modal(document.getElementById('modalTarifaMedida'));
    modalTM.show();
}

function abrirModalEliminarTarifaMedida(idTarifa, textoRango, nombrePredio) {
    document.getElementById('rangoMedidoEliminar').innerText = textoRango;
    document.getElementById('predioMedidoEliminar').innerText = nombrePredio;
    document.getElementById('formEliminarTarifaMedida').action = `/configuracion/tarifas-medidas/${idTarifa}`;
    modalEliminarTarifaMedidaBS.show();
}

// ==========================================
// FUNCIONES PARA TIPOS DE PREDIO
// ==========================================
function prepararEdicionPredio(id, nombre) {
    document.getElementById('labelFormPredio').innerText = 'Actualizar Tipo de Predio';
    document.getElementById('formTipoPredio').action = `/configuracion/tipo-predio/${id}`;
    document.getElementById('methodPredioContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('nombre_predio').value = nombre;
    document.getElementById('btnGuardarPredio').innerText = 'Actualizar';
    document.getElementById('btnCancelarEdicionPredio').classList.remove('d-none');
}

function resetFormPredio() {
    document.getElementById('labelFormPredio').innerText = 'Registrar Nuevo Tipo de Predio';
    document.getElementById('methodPredioContainer').innerHTML = '';
    document.getElementById('nombre_predio').value = '';
    document.getElementById('btnGuardarPredio').innerText = 'Guardar';
    document.getElementById('btnCancelarEdicionPredio').classList.add('d-none');
}

function abrirModalEliminarPredio(idPredio, nombrePredio) {
    document.getElementById('nombrePredioEliminar').innerText = nombrePredio;
    document.getElementById('formEliminarPredio').action = `/configuracion/tipo-predio/${idPredio}`;
    modalEliminarPredioBS.show();
}