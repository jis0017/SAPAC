let subtotalSinExtras = 0;
let descuentoActual = 0;
let basePeriodoGlobal = 0;
let sanPeriodoGlobal = 0;
let infPeriodoGlobal = 0;
let recPeriodoGlobal = 0;

function syncFormaPago() {
    document.getElementById('inputFormFormaPago').value = document.getElementById('formaPagoInput').value;
}

function togglePanelCondonacion() {
    let panel = document.getElementById('panelCondonacionAnios');
    panel.style.display = (panel.style.display === 'none') ? 'block' : 'none';
}

function calcularPerdonAnios(costoAnual) {
    let cantAnios = parseInt(document.getElementById('inputCantAniosPerdonar').value) || 0;
    let montoPerdonado = cantAnios * parseFloat(costoAnual || 0);
    document.getElementById('inputCondonacionMontoVis').value = montoPerdonado.toFixed(2);
    sincronizarCondonacion();
}

function sincronizarCondonacion() {
    let montoCondonado = parseFloat(document.getElementById('inputCondonacionMontoVis').value) || 0;
    let aniosTexto = document.getElementById('inputAniosTexto').value || '';
    let motivoTexto = document.getElementById('inputMotivoCondonacionVis').value || '';

    document.getElementById('inputFormCondonacionMonto').value = montoCondonado;
    document.getElementById('inputFormAniosCondonados').value = aniosTexto;
    document.getElementById('inputFormMotivoCondonacion').value = motivoTexto;
    recalcularTotal();
}

function forzarOpcionPeriodos() {
    let radioPeriodos = document.getElementById('cobroPeriodos');
    if (radioPeriodos && !radioPeriodos.checked) {
        radioPeriodos.checked = true;
    }
    calcularCanva();
}

/**
 * Calcula el precio por m³ excedente según la tabla tarifa_config o el Art. 58 de la Ley de Ingresos 2026
 */
function obtenerPrecioM3Excedente(excedenteM3, anio) {
    if (excedenteM3 <= 0) return 0;

    if (window.tarifasMedidorToma && window.tarifasMedidorToma.length > 0) {
        let tarifaEncontrada = window.tarifasMedidorToma.find(t =>
            parseInt(t.anio_vigencia) === parseInt(anio) &&
            parseFloat(t.limite_inferior) <= excedenteM3 &&
            (t.limite_superior === null || parseFloat(t.limite_superior) >= excedenteM3)
        );
        if (tarifaEncontrada && parseFloat(tarifaEncontrada.precio_m3_excedente) > 0) {
            return parseFloat(tarifaEncontrada.precio_m3_excedente);
        }
    }

    // Tabulador Oficial Ley de Ingresos Acatic 2026 (Art. 58)
    let m3Rango = excedenteM3 + 12;
    if (m3Rango <= 20) return 8.34;
    if (m3Rango <= 30) return 10.21;
    if (m3Rango <= 45) return 11.33;
    if (m3Rango <= 60) return 13.69;
    if (m3Rango <= 75) return 17.80;
    if (m3Rango <= 90) return 20.54;
    return 25.63;
}

/**
 * Extrae automáticamente las lecturas de la tabla "calculos" según el rango de fechas elegido (ej. Enero a Mayo)
 */
function sincronizarLecturasPorFechas() {
    let inputLecAnt = document.getElementById('inputLecAnt');
    let inputLecAct = document.getElementById('inputLecAct');
    if (!inputLecAnt || !inputLecAct || !window.lecturasMedidorToma) return;

    let fechaAntStr = document.getElementById('fechaLecAnt').value;
    let fechaActStr = document.getElementById('fechaLecAct').value;
    if (!fechaAntStr || !fechaActStr) return;

    let fechaAnt = new Date(fechaAntStr + 'T00:00:00');
    let fechaAct = new Date(fechaActStr + 'T00:00:00');

    let anioInicio = fechaAnt.getFullYear();
    let mesInicio = fechaAnt.getMonth() + 1;
    let anioFin = fechaAct.getFullYear();
    let mesFin = fechaAct.getMonth() + 1;

    // Filtrar las lecturas capturadas dentro del rango de meses seleccionado
    let lecturasEnRango = window.lecturasMedidorToma.filter(l => {
        let valorFecha = (parseInt(l.anio) * 100) + parseInt(l.mes);
        let minFecha = (anioInicio * 100) + mesInicio;
        let maxFecha = (anioFin * 100) + mesFin;
        return valorFecha >= minFecha && valorFecha <= maxFecha;
    });

    if (lecturasEnRango.length > 0) {
        let primeraDelRango = lecturasEnRango[0];
        let ultimaDelRango = lecturasEnRango[lecturasEnRango.length - 1];

        // Colocar la lectura del primer mes (ej. Enero = 12) y la del último mes (ej. Mayo = 111)
        inputLecAnt.value = primeraDelRango.lectura;
        inputLecAct.value = ultimaDelRango.lectura;

        // Calcular el consumo total acumulado en el año hasta el mes final para saber si rebasó los 144 m³
        let baseAnual = (window.configTomaMedida && window.configTomaMedida.lectura_base) ? parseFloat(window.configTomaMedida.lectura_base) : 0;
        let franquicia = (window.configTomaMedida && window.configTomaMedida.franquicia_anual) ? parseFloat(window.configTomaMedida.franquicia_anual) : 144;

        // Gasto total desde la base anual (0) hasta el último mes del periodo (111) -> 111 m³
        let consumoTotalPeriodo = parseFloat(ultimaDelRango.lectura) - baseAnual;
        if (consumoTotalPeriodo < 0) consumoTotalPeriodo = 0;

        document.getElementById('txtConsumo').innerText = consumoTotalPeriodo;
        document.getElementById('inputConsumoOculto').value = consumoTotalPeriodo;

        // Verificar si rebasa la franquicia anual de 144 m³
        let m3Excedentes = consumoTotalPeriodo > franquicia ? (consumoTotalPeriodo - franquicia) : 0;
        let inputExcedente = document.getElementById('inputExcedente');

        if (inputExcedente) {
            if (m3Excedentes > 0) {
                let precioM3 = obtenerPrecioM3Excedente(m3Excedentes, anioFin);
                inputExcedente.value = (m3Excedentes * precioM3).toFixed(2);
            } else {
                inputExcedente.value = "0.00";
            }
        }
    }
}

/**
 * Si el cajero edita manualmente los inputs de Lectura Anterior / Actual
 */
function calcularConsumo() {
    let lecAnt = parseInt(document.getElementById('inputLecAnt').value) || 0;
    let lecAct = parseInt(document.getElementById('inputLecAct').value) || 0;
    let consumo = lecAct - lecAnt;
    if (consumo < 0) consumo = 0;
    
    document.getElementById('txtConsumo').innerText = consumo;
    document.getElementById('inputConsumoOculto').value = consumo;

    let franquicia = (window.configTomaMedida && window.configTomaMedida.franquicia_anual) ? parseFloat(window.configTomaMedida.franquicia_anual) : 144;
    let inputExcedente = document.getElementById('inputExcedente');

    if (inputExcedente) {
        if (consumo > franquicia) {
            let m3Excedentes = consumo - franquicia;
            let anioActual = new Date().getFullYear();
            let precioM3 = obtenerPrecioM3Excedente(m3Excedentes, anioActual);
            inputExcedente.value = (m3Excedentes * precioM3).toFixed(2);
        } else {
            inputExcedente.value = "0.00";
        }
    }

    recalcularTotal();
}

function recalcularTotal() {
    let rezago = parseFloat(document.getElementById('inputRezagoManual').value) || 0;
    let recargo = parseFloat(document.getElementById('inputRecargoManual').value) || 0;
    let sancion = parseFloat(document.getElementById('inputSancionManual').value) || 0;
    let excedente = parseFloat(document.getElementById('inputExcedente') ? document.getElementById('inputExcedente').value : 0) || 0;
    let condonacion = parseFloat(document.getElementById('inputFormCondonacionMonto').value) || 0;

    // Si hay cobro excedente, sumar también su 25% de saneamiento y 5% de infraestructura en pantalla
    let sanTotal = sanPeriodoGlobal + (excedente > 0 ? excedente * 0.25 : 0);
    let infTotal = infPeriodoGlobal + (excedente > 0 ? excedente * 0.05 : 0);

    document.getElementById('txtBase').innerText = '$' + (basePeriodoGlobal + excedente).toFixed(2);
    document.getElementById('txtSaneamiento').innerText = '$' + sanTotal.toFixed(2);
    document.getElementById('txtInfra').innerText = '$' + infTotal.toFixed(2);

    let descuentoTotal = descuentoActual + condonacion;
    let txtDescuento = document.getElementById('txtDescuento');
    if (txtDescuento) {
        txtDescuento.innerText = (descuentoTotal > 0) ? '-$' + descuentoTotal.toFixed(2) : 'NO';
    }

    let nuevoTotal = basePeriodoGlobal + excedente + sanTotal + infTotal + recPeriodoGlobal + rezago + recargo + sancion - descuentoTotal;
    if (nuevoTotal < 0) nuevoTotal = 0;

    document.getElementById('txtTotal').innerText = '$' + nuevoTotal.toFixed(2);
    document.getElementById('inputTotalOculto').value = nuevoTotal.toFixed(2);
}

function calcularCanva() {
    let cobroElegido = document.querySelector('input[name="tipo_cobro"]:checked');
    if(!cobroElegido) return;

    let tipoCobroValue = cobroElegido.value;
    let inputLecAnt = document.getElementById('fechaLecAnt');
    let inputLecAct = document.getElementById('fechaLecAct');
    let currentYear = new Date().getFullYear();

    if (tipoCobroValue === 'anualidad') {
        inputLecAnt.value = currentYear + "-01-01";
        inputLecAct.value = currentYear + "-12-31";
    }
    
    let tipoParaElBackend = (tipoCobroValue === 'anualidad' || tipoCobroValue === 'periodos') ? 'periodos' : tipoCobroValue;
    document.getElementById('inputFormTipoCobroReal').value = tipoParaElBackend;
    
    let fechaAnt = new Date(inputLecAnt.value + 'T00:00:00');
    let fechaAct = new Date(inputLecAct.value + 'T00:00:00');
    let meses = 1;
    
    if (!isNaN(fechaAnt) && !isNaN(fechaAct)) {
        meses = (fechaAct.getFullYear() - fechaAnt.getFullYear()) * 12 + (fechaAct.getMonth() - fechaAnt.getMonth()) + 1;
        if (meses < 1) meses = 1;
    }
    
    let spanMeses = document.getElementById('spanMesesCobrados');
    if (spanMeses) spanMeses.innerText = meses + " MES(ES)";

    let isCobroPeriodico = (tipoCobroValue === 'anualidad' || tipoCobroValue === 'periodos');

    // Sincronizar las lecturas capturadas del medidor según los meses seleccionados
    if (isCobroPeriodico) {
        sincronizarLecturasPorFechas();
    }

    let base = (parseFloat(cobroElegido.getAttribute('data-base')) || 0) * (isCobroPeriodico ? meses : 1);
    let san = (parseFloat(cobroElegido.getAttribute('data-san')) || 0) * (isCobroPeriodico ? meses : 1);
    let inf = (parseFloat(cobroElegido.getAttribute('data-inf')) || 0) * (isCobroPeriodico ? meses : 1);
    let rec = parseFloat(cobroElegido.getAttribute('data-rec')) || 0;

    basePeriodoGlobal = base;
    sanPeriodoGlobal = san;
    infPeriodoGlobal = inf;
    recPeriodoGlobal = rec;
    
    let descuentoManual = 0;
    let cajaDescuentos = document.getElementById('cajaDescuentos');
    let descElement = document.querySelector('input[name="select_descuento"]:checked');
    let descElegido = descElement ? descElement.value : 'ninguno';
    
    if (isCobroPeriodico) {
        cajaDescuentos.style.opacity = '1';
        cajaDescuentos.style.pointerEvents = 'auto';
        
        let isAnual = (meses === 12);
        document.getElementById('radioEneroDiv').classList.toggle('text-muted', !isAnual);
        document.getElementById('radioMarzoDiv').classList.toggle('text-muted', !isAnual);

        if (!isAnual && (descElegido === 'enero' || descElegido === 'marzo_abril')) {
            document.querySelector('input#descNinguno').checked = true;
            descElegido = 'ninguno';
        }
        document.getElementById('inputFormDescuento').value = descElegido;

        if (descElegido === 'adulto_mayor') descuentoManual = base * 0.50; 
        else if (descElegido === 'enero' && isAnual) descuentoManual = base * 0.15;
        else if (descElegido === 'marzo_abril' && isAnual) descuentoManual = base * 0.05;
    } else {
        cajaDescuentos.style.opacity = '0.5';
        cajaDescuentos.style.pointerEvents = 'none';
        let descNinguno = document.querySelector('input#descNinguno');
        if (descNinguno) descNinguno.checked = true;
        document.getElementById('inputFormDescuento').value = 'ninguno';
        descuentoManual = 0;
    }

    document.getElementById('txtRec').innerText = '$' + rec.toFixed(2);
    document.getElementById('rowRec').style.display = (rec > 0) ? 'table-row' : 'none';
    
    descuentoActual = descuentoManual;

    let inputRezago = document.getElementById('inputRezagoManual');
    let inputSancion = document.getElementById('inputSancionManual');
    
    if (!isCobroPeriodico && tipoCobroValue !== 'contrato' && tipoCobroValue !== 'reconexion') {
        if(inputRezago) {
            inputRezago.value = parseFloat(cobroElegido.getAttribute('data-rez')).toFixed(2);
            inputRezago.readOnly = true;
        }
        if(inputSancion) {
            inputSancion.value = parseFloat(cobroElegido.getAttribute('data-sanc')).toFixed(2);
            inputSancion.readOnly = true;
        }
        subtotalSinExtras = base + san + inf + rec; 
    } else {
        if(inputRezago) inputRezago.readOnly = false;
        if(inputSancion) inputSancion.readOnly = false;
        subtotalSinExtras = base + san + inf + rec;
    }

    recalcularTotal();
}

function verificarYEnviar() {
    let tipoCobro = document.getElementById('inputFormTipoCobroReal').value;
    if (!tipoCobro) return;
    
    let total = parseFloat(document.getElementById('inputTotalOculto').value) || 0;
    document.getElementById('montoConfirmarModal').innerText = "$" + total.toFixed(2);
    
    let modalEl = document.getElementById('modalConfirmarPago');
    let myModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    myModal.show();
}

function calcularDiferenciaCorte() {
    let txtEsperado = document.getElementById('txtEsperadoCorte');
    let inputEntregado = document.getElementById('inputEfectivoEntregado');
    let txtDif = document.getElementById('txtDiferenciaCorte');

    if (!txtEsperado || !inputEntregado || !txtDif) return;

    let esperado = parseFloat(txtEsperado.getAttribute('data-esperado')) || 0;
    let entregado = parseFloat(inputEntregado.value) || 0;
    let diferencia = entregado - esperado;

    txtDif.innerText = (diferencia > 0 ? '+$' : '$') + diferencia.toFixed(2);
    txtDif.className = 'fw-bold fs-6 ' + (diferencia < 0 ? 'text-danger' : (diferencia > 0 ? 'text-warning' : 'text-success'));
}



function ejecutarPago() {
    document.getElementById('formPago').submit();

    document.getElementById('tituloModalPago').innerText = '¡Operación Exitosa!';
    document.getElementById('subtituloModalPago').innerText = 'El pago ha sido registrado y la papeleta se generó correctamente.';
    document.getElementById('botonesModalConfirmar').innerHTML = `
        <button type="button" class="btn btn-primary rounded-pill px-5 py-2 fw-bold" style="background-color: #1a66ff;" onclick="window.location.reload()">Aceptar</button>
    `;
}

document.addEventListener("DOMContentLoaded", function() {
    setTimeout(() => {
        calcularCanva();
    }, 10);
});