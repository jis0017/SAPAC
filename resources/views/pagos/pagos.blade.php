@extends('layouts.app')
@section('title', 'Ventanilla de Cobro - SAPAC')

@section('content')
<div class="container-fluid py-2">
  <!-- ENCABEZADO Y BUSCADOR COMPACTO -->
  <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <div class="d-flex align-items-center">
      <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3">
        <i class="bi bi-cash-stack fs-4"></i>
      </div>
      <div>
        <span class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">MÓDULO DE FACTURACIÓN</span>
        <h1 class="h5 fw-bold text-dark mb-0">Ventanilla de Cobro SAPAC</h1>
      </div>
    </div>
    <button class="btn {{ isset($corteActivo) && $corteActivo ? 'btn-outline-success' : 'btn-outline-danger' }} fw-bold px-3 py-1 border-2" data-bs-toggle="modal" data-bs-target="#modalCaja">
      <i class="bi bi-safe me-1"></i> {{ isset($corteActivo) && $corteActivo ? 'Estado / Corte de Caja' : 'Abrir Caja del Día' }}
    </button>
  </div>

  <!-- ALERTAS VISIBLES POR SI OCURRE UN ERROR EN EL CONTROLADOR -->
  @if($errors->any())
    <div class="alert alert-danger py-2 shadow-sm"><strong>Error:</strong> {{ $errors->first() }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger py-2 shadow-sm"><strong>Error:</strong> {{ session('error') }}</div>
  @endif

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-2">
      <form action="{{ route('pagos.index') }}" method="GET">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-primary"></i></span>
          <input type="text" class="form-control border-start-0 text-uppercase" name="q" value="{{ request('q') }}" placeholder="BUSCAR TOMA, CIUDADANO, CALLE Y NÚMERO O MEDIDOR..." required>
          <button class="btn btn-primary px-4 fw-bold" type="submit">Buscar</button>
        </div>
      </form>
    </div>
  </div>

  @if(isset($tomasBuscadas) && count($tomasBuscadas) > 1)
  <div class="card border-info bg-info bg-opacity-10 mb-3 shadow-sm">
      <div class="card-body p-2">
          <h6 class="fw-bold text-dark mb-2 small"><i class="bi bi-info-circle-fill me-1 text-info"></i>Se encontraron varias tomas. Selecciona una:</h6>
          <div class="table-responsive bg-white rounded">
              <table class="table table-sm table-hover align-middle mb-0 border">
                  <thead class="table-light">
                      <tr><th>ID TOMA</th><th>TITULAR</th><th>DOMICILIO</th><th>ACCIÓN</th></tr>
                  </thead>
                  <tbody>
                      @foreach($tomasBuscadas as $tBusqueda)
                      <tr>
                          <td class="fw-bold">#{{ $tBusqueda->id_toma }}</td>
                          <td class="text-uppercase">{{ $tBusqueda->nombre_usuario }} {{ $tBusqueda->apellido_usuario }}</td>
                          <td class="text-uppercase">{{ $tBusqueda->calle }} {{ $tBusqueda->numero_exterior }} {{ $tBusqueda->numero_interior }}</td>
                          <td><a href="{{ route('pagos.index', ['toma_id' => $tBusqueda->id_toma]) }}" class="btn btn-sm btn-primary py-0">Seleccionar</a></td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
          </div>
      </div>
  </div>
  @endif

  <!-- PANEL DE TOMA SELECCIONADA -->
  @if(isset($toma))
  @php
      $cMensual = $cuotaPeriodo ? $cuotaPeriodo->cuota_mensual : 0;
      $cSanMensual = $cuotaPeriodo ? ($cuotaPeriodo->saneamiento_mensual / 12) : 0;
      $cInfraMensual = $cuotaPeriodo ? ($cuotaPeriodo->otros_usos_mensual / 12) : 0;
      $cContrato = $cuotaPeriodo ? $cuotaPeriodo->costo_nuevo_contrato : 0;
      $cReconexion = $cuotaPeriodo ? $cuotaPeriodo->costo_reconexion : 0;
      $cAnualTotal = ($cMensual * 12) + ($cuotaPeriodo ? $cuotaPeriodo->saneamiento_mensual : 0) + ($cuotaPeriodo ? $cuotaPeriodo->otros_usos_mensual : 0);
      $primerBotonMarcado = false;
  @endphp

  <div class="row g-3">
    <!-- LADO IZQUIERDO: DATOS Y SELECCIÓN COMPACTA -->
    <div class="col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h5 class="fw-bold text-dark mb-0">NO. TOMA : {{ $toma->id_toma }}</h5>
            @if($toma->calculo_fijo == 1)
                <span class="badge bg-success"><i class="bi bi-droplet-fill me-1"></i> CUOTA FIJA</span>
            @else
                <span class="badge bg-warning text-dark"><i class="bi bi-speedometer2 me-1"></i> SERVICIO MEDIDO (Medidor: {{ $toma->no_medidor ?? 'S/N' }})</span>
            @endif
        </div>
        <h5 class="fw-bold text-dark mb-0 text-uppercase">{{ $toma->usuario->razon_social ?? '' }} {{ $toma->usuario->apellidos ?? '' }}</h5>
        <p class="text-muted fw-bold mb-2 text-uppercase small">{{ $toma->calle }} #{{ $toma->numero_exterior }} {{ $toma->numero_interior }}</p>

        <!-- COBROS DISPONIBLES -->
        <div class="border border-2 border-info rounded-4 p-3 mb-2 bg-white shadow-sm">
            <h6 class="fw-bold text-center mb-2">COBROS DISPONIBLES</h6>
            <div class="d-flex flex-column gap-1 ms-2">
                
                @if(isset($alertaContrato) && $alertaContrato)
                <div class="form-check fw-bold text-danger">
                    <input class="form-check-input check-cobro" type="radio" name="tipo_cobro" id="cobroContrato" value="contrato" 
                           data-base="{{ $cContrato }}" data-san="0" data-inf="0" data-rec="0" data-rez="0" data-sanc="0" data-bonif="0" 
                           onchange="calcularCanva()" {{ !$primerBotonMarcado ? 'checked' : '' }}>
                    <label class="form-check-label" for="cobroContrato">CONTRATO DE NUEVA TOMA (${{ number_format($cContrato, 2) }})</label>
                    @php $primerBotonMarcado = true; @endphp
                </div>
                @endif

                <div class="form-check fw-bold">
                    <input class="form-check-input check-cobro" type="radio" name="tipo_cobro" id="cobroReconexion" value="reconexion" 
                           data-base="0" data-san="0" data-inf="0" data-rec="{{ $cReconexion }}" data-rez="0" data-sanc="0" data-bonif="0" 
                           onchange="calcularCanva()" {{ !$primerBotonMarcado ? 'checked' : '' }}>
                    <label class="form-check-label" for="cobroReconexion">RECONEXIÓN (${{ number_format($cReconexion, 2) }})</label>
                    @php $primerBotonMarcado = true; @endphp
                </div>

                <div class="form-check fw-bold text-primary">
                    <input class="form-check-input check-cobro" type="radio" name="tipo_cobro" id="cobroAnualidad" value="anualidad" 
                           data-base="{{ $cMensual }}" data-san="{{ $cSanMensual }}" data-inf="{{ $cInfraMensual }}" data-rec="0" data-rez="0" data-sanc="0" data-bonif="0" 
                           onchange="calcularCanva()" {{ !$primerBotonMarcado ? 'checked' : '' }}>
                    <label class="form-check-label" for="cobroAnualidad">PAGAR ANUALIDAD COMPLETA (Ene - Dic)</label>
                    @php $primerBotonMarcado = true; @endphp
                </div>

                <div class="form-check fw-bold text-info">
                    <input class="form-check-input check-cobro" type="radio" name="tipo_cobro" id="cobroPeriodos" value="periodos" 
                           data-base="{{ $cMensual }}" data-san="{{ $cSanMensual }}" data-inf="{{ $cInfraMensual }}" data-rec="0" data-rez="0" data-sanc="0" data-bonif="0" 
                           onchange="calcularCanva()" {{ !$primerBotonMarcado ? 'checked' : '' }}>
                    <label class="form-check-label" for="cobroPeriodos">
                        PAGAR POR PERIODO / MESES <span id="spanMesesCobrados" class="badge bg-primary ms-1">12 MESES</span>
                    </label>
                    @php $primerBotonMarcado = true; @endphp
                </div>

                @foreach($facturasPendientes as $fPendiente)
                <div class="form-check fw-bold text-warning">
                    <input class="form-check-input check-cobro" type="radio" name="tipo_cobro" id="adeudo_{{ $fPendiente->id_factura }}" value="{{ $fPendiente->id_factura }}" 
                           data-base="{{ $fPendiente->cuota_base_agua }}" 
                           data-san="{{ $fPendiente->saneamiento }}" 
                           data-inf="{{ $fPendiente->infraestructura }}" 
                           data-rec="{{ $fPendiente->reconexion ?? 0 }}"
                           data-rez="{{ $fPendiente->rezagos ?? 0 }}" 
                           data-sanc="{{ $fPendiente->sancion ?? 0 }}"
                           data-bonif="{{ $fPendiente->bonificacion ?? 0 }}"
                           data-total="{{ $fPendiente->total_factura }}" 
                           onchange="calcularCanva()">
                    <label class="form-check-label" for="adeudo_{{ $fPendiente->id_factura }}">
                        FACTURA PENDIENTE: {{ $fPendiente->periodo_anio }} ({{ $fPendiente->periodo_mes }}) - ${{ number_format($fPendiente->total_factura, 2) }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        <!-- DESCUENTOS A SELECCIONAR -->
        <div class="border border-2 border-info rounded-4 p-3 mb-2 bg-white shadow-sm" id="cajaDescuentos">
            <h6 class="fw-bold text-center mb-2">DESCUENTOS A SELECCIONAR</h6>
            <div class="d-flex flex-column gap-1 ms-2">
                <div class="form-check fw-bold">
                    <input class="form-check-input" type="radio" name="select_descuento" id="descNinguno" value="ninguno" checked onchange="calcularCanva()">
                    <label class="form-check-label" for="descNinguno">NINGUNO</label>
                </div>
                <div class="form-check fw-bold">
                    <input class="form-check-input" type="radio" name="select_descuento" id="descInapam" value="adulto_mayor" onchange="calcularCanva()">
                    <label class="form-check-label" for="descInapam">ADULTO MAYOR (50%)</label>
                </div>
                <div class="form-check fw-bold" id="radioEneroDiv">
                    <input class="form-check-input" type="radio" name="select_descuento" id="descEnero" value="enero" onchange="calcularCanva()">
                    <label class="form-check-label" for="descEnero">ENERO AÑO COMPLETO 15%</label>
                </div>
                <div class="form-check fw-bold" id="radioMarzoDiv">
                    <input class="form-check-input" type="radio" name="select_descuento" id="descMarzo" value="marzo_abril" onchange="calcularCanva()">
                    <label class="form-check-label" for="descMarzo">MARZO - ABRIL AÑO COMP. 5%</label>
                </div>
            </div>
        </div>

        <!-- CONDONACIÓN DE AÑOS POR LEY -->
        <div class="border border-2 border-warning rounded-4 p-3 mb-2 bg-white shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark small"><i class="bi bi-calendar-x me-1 text-warning"></i> ¿TIENE AÑOS ATRASADOS POR CONDONAR?</span>
                <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold py-0" onclick="togglePanelCondonacion()">
                    Perdonar Años
                </button>
            </div>
            <div id="panelCondonacionAnios" class="mt-2 pt-2 border-top" style="display: none;">
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-5">
                        <label class="small fw-bold text-muted">Años que debe (Ej. 2022-2025):</label>
                        <input type="text" class="form-control form-control-sm" id="inputAniosTexto" placeholder="Ej. 2023 al 2025" oninput="sincronizarCondonacion()">
                    </div>
                    <div class="col-3">
                        <label class="small fw-bold text-muted">Cant. Años:</label>
                        <input type="number" class="form-control form-control-sm text-center fw-bold" id="inputCantAniosPerdonar" value="0" min="0" max="15" oninput="calcularPerdonAnios({{ $cAnualTotal }})">
                    </div>
                    <div class="col-4">
                        <label class="small fw-bold text-muted">Monto Perdonado:</label>
                        <input type="number" step="0.01" class="form-control form-control-sm text-end text-danger fw-bold" id="inputCondonacionMontoVis" value="0.00" oninput="sincronizarCondonacion()">
                    </div>
                </div>
                <input type="text" class="form-control form-control-sm" id="inputMotivoCondonacionVis" placeholder="Motivo / Oficio por Ley (Ej. Prescripción de adeudo)" oninput="sincronizarCondonacion()">
            </div>
        </div>

        <div>
            <label class="fw-bold small mb-1">FORMA DE PAGO</label>
            <select class="form-select border-2 border-info fw-bold" id="formaPagoInput" onchange="syncFormaPago()">
                <option value="EFECTIVO">EFECTIVO</option>
                <option value="TARJETA">TARJETA DÉBITO/CRÉDITO</option>
                <option value="TRANSFERENCIA">TRANSFERENCIA</option>
            </select>
        </div>
    </div>

    <!-- LADO DERECHO: DESGLOSE DEL COBRO COMPACTO (ESTILO ORIGINAL) -->
    <div class="col-lg-6">
        <form action="{{ route('pagos.store') }}" method="POST" id="formPago" target="_blank">
            @csrf
            <input type="hidden" name="toma_id" value="{{ $toma->id_toma }}">
            <input type="hidden" name="tipo_cobro" id="inputFormTipoCobroReal" value="">
            <input type="hidden" name="tipo_descuento" id="inputFormDescuento" value="ninguno">
            <input type="hidden" name="forma_pago" id="inputFormFormaPago" value="EFECTIVO">
            <input type="hidden" name="es_medido" value="{{ $toma->calculo_fijo == 0 ? 1 : 0 }}">
            <input type="hidden" name="monto_condonado" id="inputFormCondonacionMonto" value="0">
            <input type="hidden" name="anios_condonados" id="inputFormAniosCondonados" value="">
            <input type="hidden" name="motivo_condonacion" id="inputFormMotivoCondonacion" value="">

            <div class="card border-0 shadow-sm rounded-0 border border-primary border-2">
                <div class="card-header bg-primary text-white text-center py-2 rounded-0">
                    <h5 class="fw-bold mb-0 text-uppercase">DESGLOSE DEL COBRO</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless table-striped mb-0 fw-bold align-middle">
                        <tbody>
                            <tr>
                                <td class="py-2 ps-3">NO. FACTURA / FOLIO LOTE</td>
                                <td class="py-1 pe-3 text-end">
                                    <input type="text" class="form-control form-control-sm d-inline-block w-50 text-end fw-bold border-primary" name="no_factura" value="{{ $folioSugerido ?? '000001' }}" required>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-2 ps-3">FECHA LECTURA ANTERIOR</td>
                                <td class="py-1 pe-3 text-end">
                                    <input type="date" class="form-control form-control-sm d-inline-block w-auto text-end" name="fecha_lec_ant" id="fechaLecAnt" value="{{ date('Y-01-01') }}" onchange="forzarOpcionPeriodos()">
                                </td>
                            </tr>
                            <tr>
                                <td class="py-2 ps-3">FECHA LECTURA ACTUAL</td>
                                <td class="py-1 pe-3 text-end">
                                    <input type="date" class="form-control form-control-sm d-inline-block w-auto text-end" name="fecha_lec_act" id="fechaLecAct" value="{{ date('Y-12-31') }}" onchange="forzarOpcionPeriodos()">
                                </td>
                            </tr>

                            @if($toma->calculo_fijo == 0)
                            <tr class="table-warning">
                                <td class="py-1 ps-3">LECTURA ANTERIOR / ACTUAL (m³)</td>
                                <td class="py-1 pe-3 text-end">
                                    <div class="d-inline-flex gap-1 justify-content-end">
                                        <input type="number" class="form-control form-control-sm text-end" style="width: 80px;" name="lectura_anterior" id="inputLecAnt" value="{{ $ultimaLectura ?? 0 }}" min="0" oninput="calcularConsumo()" title="Lectura Anterior">
                                        <input type="number" class="form-control form-control-sm text-end" style="width: 80px;" name="lectura_actual" id="inputLecAct" value="{{ $ultimaLectura ?? 0 }}" min="0" oninput="calcularConsumo()" title="Lectura Actual">
                                    </div>
                                </td>
                            </tr>
                            <tr class="table-warning">
                                <td class="py-1 ps-3">CONSUMO (m³) / COBRO EXCEDENTE</td>
                                <td class="py-1 pe-3 text-end">
                                    <span class="badge bg-info text-dark me-2"><span id="txtConsumo">0</span> m³</span>
                                    <input type="hidden" name="consumo" id="inputConsumoOculto" value="0">
                                    <input type="number" step="0.01" class="form-control form-control-sm d-inline-block text-end fw-bold" style="width: 110px;" name="monto_excedente" id="inputExcedente" value="0.00" oninput="recalcularTotal()">
                                </td>
                            </tr>
                            @endif

                            <tr>
                                <td class="py-2 ps-3">CUOTA FIJA / BASE</td>
                                <td class="py-2 pe-3 text-end fs-6" id="txtBase">$0.00</td>
                            </tr>
                            <tr>
                                <td class="py-2 ps-3">SANEAMIENTO</td>
                                <td class="py-2 pe-3 text-end fs-6" id="txtSaneamiento">$0.00</td>
                            </tr>
                            <tr>
                                <td class="py-2 ps-3">INFRAESTRUCTURA</td>
                                <td class="py-2 pe-3 text-end fs-6" id="txtInfra">$0.00</td>
                            </tr>
                            <tr id="rowRec">
                                <td class="py-2 ps-3">RECONEXIÓN</td>
                                <td class="py-2 pe-3 text-end fs-6" id="txtRec">$0.00</td>
                            </tr>
                            <tr>
                                <td class="py-1 ps-3">REZAGO</td>
                                <td class="py-1 pe-3 text-end">
                                    <input type="number" step="0.01" class="form-control form-control-sm d-inline-block text-end text-danger fw-bold" style="width: 120px;" name="rezagos" id="inputRezagoManual" value="0.00" oninput="recalcularTotal()">
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1 ps-3">RECARGOS</td>
                                <td class="py-1 pe-3 text-end">
                                    <input type="number" step="0.01" class="form-control form-control-sm d-inline-block text-end fw-bold" style="width: 120px;" name="recargos" id="inputRecargoManual" value="0.00" oninput="recalcularTotal()">
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1 ps-3">SANCIÓN / MULTA</td>
                                <td class="py-1 pe-3 text-end">
                                    <input type="number" step="0.01" class="form-control form-control-sm d-inline-block text-end fw-bold" style="width: 120px;" name="sancion" id="inputSancionManual" value="0.00" oninput="recalcularTotal()">
                                </td>
                            </tr>
                            <tr>
                                <td class="py-2 ps-3">DESCUENTO / BONIF.</td>
                                <td class="py-2 pe-3 text-end fs-6 text-success" id="txtDescuento">NO</td>
                            </tr>
                            <tr class="border-top border-dark border-2">
                                <td class="py-3 ps-3 fs-4 fw-bolder">TOTAL</td>
                                <td class="py-3 pe-3 text-end fs-4 fw-bolder text-primary">
                                    <span id="txtTotal">$0.00</span>
                                    <input type="hidden" name="total_factura" id="inputTotalOculto" value="0">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="button" class="btn btn-outline-dark fw-bold px-5 py-2 fs-5 rounded-4 border-2 shadow-sm" onmouseover="this.classList.add('bg-dark','text-white')" onmouseout="this.classList.remove('bg-dark','text-white')" onclick="verificarYEnviar()">
                    PAGAR E IMPRIMIR
                </button>
            </div>
        </form>
    </div>
  </div>
  @endif

  <!-- ========================================================================= -->
  <!-- MODAL DE CONFIRMACIÓN Y ÉXITO (MISMO DISEÑO LIMPIO DE TOMAS DE AGUA)       -->
  <!-- ========================================================================= -->
  <div class="modal fade" id="modalConfirmarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
      <div class="modal-content border-0 shadow-lg rounded-4 p-4 text-center">
        <div class="modal-body p-2">
          <!-- Círculo verde suave idéntico a tu captura -->
          <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 84px; height: 84px; background-color: #e6f4ea; border: 2px solid #ceead6;">
            <i class="bi bi-check-lg" style="font-size: 2.8rem; color: #1e8e3e;"></i>
          </div>
          <h4 class="fw-bold text-dark mb-2" id="tituloModalPago">¿Confirmar Cobro?</h4>
          <p class="text-muted mb-4" id="subtituloModalPago">
            Se registrará la factura y se generará la papeleta por <strong class="text-dark" id="montoConfirmarModal">$0.00</strong>.
          </p>
          <div class="d-flex justify-content-center gap-2" id="botonesModalConfirmar">
            <button type="button" class="btn btn-light rounded-pill px-4 py-2 fw-bold text-secondary border" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary rounded-pill px-5 py-2 fw-bold" style="background-color: #1a66ff;" onclick="ejecutarPago()">Aceptar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if(session('success'))
  <div class="modal fade" id="modalExitoGlobal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
      <div class="modal-content border-0 shadow-lg rounded-4 p-4 text-center">
        <div class="modal-body p-2">
          <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 84px; height: 84px; background-color: #e6f4ea; border: 2px solid #ceead6;">
            <i class="bi bi-check-lg" style="font-size: 2.8rem; color: #1e8e3e;"></i>
          </div>
          <h4 class="fw-bold text-dark mb-2">¡Operación Exitosa!</h4>
          <p class="text-muted mb-4">{{ session('success') }}</p>
          <button type="button" class="btn btn-primary rounded-pill px-5 py-2 fw-bold" style="background-color: #1a66ff;" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>
  @endif

 <!-- ========================================================================= -->
  <!-- MODAL GRANDE DE CONTROL E HISTORIAL DE CORTES DE CAJA (cortes_caja)       -->
  <!-- ========================================================================= -->
  <div class="modal fade" id="modalCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg rounded-4">
        <div class="modal-header border-bottom py-3 px-4 bg-light">
          <div class="d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3">
              <i class="bi bi-safe-fill fs-4"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0">Control e Historial de Cortes de Caja</h5>
              <small class="text-muted">Apertura de turno, arqueo de efectivo y bitácora de cierres</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body p-4">
          <div class="row g-4">
            <!-- COLUMNA IZQUIERDA: OPERACIÓN DEL TURNO ACTUAL -->
            <div class="col-lg-4 border-end">
              @if(!isset($corteActivo) || !$corteActivo)
                  <div class="p-3 rounded-4 bg-warning bg-opacity-10 border border-warning mb-3">
                      <h6 class="fw-bold text-dark mb-1"><i class="bi bi-unlock-fill me-1 text-warning"></i> Apertura de Caja</h6>
                      <p class="small text-muted mb-0">No hay un turno abierto. Ingresa el fondo inicial de caja para comenzar a operar.</p>
                  </div>

                  <form action="{{ route('pagos.abrirCaja') }}" method="POST">
                      @csrf
                      <div class="mb-3">
                          <label class="form-label fw-bold small">Monto Inicial en Caja (Fondo):</label>
                          <div class="input-group">
                              <span class="input-group-text bg-white fw-bold">$</span>
                              <input type="number" step="0.01" min="0" class="form-control fw-bold fs-5 text-end" name="monto_inicial" value="0.00" required>
                          </div>
                      </div>
                      <div class="mb-3">
                          <label class="form-label fw-bold small">Observaciones de Apertura:</label>
                          <input type="text" class="form-control form-control-sm" name="observaciones" placeholder="Ej. Turno matutino ventanilla 1">
                      </div>
                      <button type="submit" class="btn btn-primary rounded-pill w-100 py-2 fw-bold shadow-sm">
                          <i class="bi bi-check2-circle me-1"></i> Abrir Caja del Día
                      </button>
                  </form>
              @else
                  @php
                      $esperadoTotal = ($corteActivo->monto_inicial ?? 0) + ($ingresosHoy ?? 0);
                  @endphp
                  <div class="d-flex justify-content-between align-items-center mb-3">
                      <span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-circle-fill me-1 small"></i> CAJA ABIERTA (Corte #{{ $corteActivo->id_corte }})</span>
                      <small class="text-muted fw-bold">{{ \Carbon\Carbon::parse($corteActivo->fecha_apertura)->format('d/m/Y H:i') }}</small>
                  </div>

                  <form action="{{ route('pagos.cerrarCaja') }}" method="POST">
                      @csrf
                      <input type="hidden" name="id_corte" value="{{ $corteActivo->id_corte }}">
                      
                      <div class="row g-2 mb-3 text-center">
                          <div class="col-6">
                              <div class="p-2 border rounded-3 bg-light">
                                  <span class="d-block text-muted small fw-bold">Fondo Inicial</span>
                                  <h6 class="fw-bold mb-0">${{ number_format($corteActivo->monto_inicial, 2) }}</h6>
                              </div>
                          </div>
                          <div class="col-6">
                              <div class="p-2 border rounded-3 bg-light">
                                  <span class="d-block text-muted small fw-bold">Cobrado Sistema ({{ $pagosHoyCount ?? 0 }})</span>
                                  <h6 class="fw-bold text-success mb-0">${{ number_format($ingresosHoy ?? 0, 2) }}</h6>
                              </div>
                          </div>
                      </div>

                      <div class="p-3 mb-3 border border-primary rounded-3 bg-primary bg-opacity-10 text-center">
                          <span class="d-block fw-bold text-primary small">TOTAL ESPERADO EN CAJA</span>
                          <h3 class="fw-bold text-primary mb-0" id="txtEsperadoCorte" data-esperado="{{ $esperadoTotal }}">${{ number_format($esperadoTotal, 2) }}</h3>
                      </div>

                      <div class="mb-2">
                          <label class="form-label fw-bold small">Total Efectivo Físico Entregado:</label>
                          <div class="input-group">
                              <span class="input-group-text bg-white fw-bold">$</span>
                              <input type="number" step="0.01" min="0" class="form-control fw-bold fs-5 text-end border-primary" name="total_efectivo_entregado" id="inputEfectivoEntregado" value="{{ number_format($esperadoTotal, 2, '.', '') }}" oninput="calcularDiferenciaCorte()" required>
                          </div>
                      </div>

                      <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-light border mb-3">
                          <span class="small fw-bold text-muted">Diferencia (Sobrante / Faltante):</span>
                          <span class="fw-bold fs-6 text-success" id="txtDiferenciaCorte">$0.00</span>
                      </div>

                      <div class="mb-3">
                          <label class="form-label fw-bold small">Observaciones de Cierre:</label>
                          <input type="text" class="form-control form-control-sm" name="observaciones" value="{{ $corteActivo->observaciones }}" placeholder="Notas de cierre o arqueo">
                      </div>

                      <button type="submit" class="btn btn-danger rounded-pill w-100 py-2 fw-bold shadow-sm">
                          <i class="bi bi-lock-fill me-1"></i> Cerrar Corte de Caja
                      </button>
                  </form>
              @endif
            </div>

            <!-- COLUMNA DERECHA: TABLA HISTÓRICA DE CORTES -->
            <div class="col-lg-8">
              <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-1 text-primary"></i> Bitácora Reciente de Cortes de Caja</h6>
                  <span class="badge bg-light text-secondary border">Últimos {{ isset($historialCortes) ? count($historialCortes) : 0 }} registros</span>
              </div>

              <div class="table-responsive border rounded-3" style="max-height: 420px; overflow-y: auto;">
                  <table class="table table-sm table-hover align-middle mb-0 text-center small">
                      <thead class="table-light text-uppercase sticky-top">
                          <tr>
                              <th># Corte</th>
                              <th>Apertura / Cierre</th>
                              <th>Fondo Inicial</th>
                              <th>Cobrado Sist.</th>
                              <th>Efectivo Entreg.</th>
                              <th>Diferencia</th>
                              <th>Estatus</th>
                              <th>Observaciones</th>
                          </tr>
                      </thead>
                      <tbody>
                          @forelse($historialCortes ?? [] as $corte)
                              <tr>
                                  <td class="fw-bold font-monospace">#{{ $corte->id_corte }}</td>
                                  <td class="text-start">
                                      <div><strong class="text-success">A:</strong> {{ $corte->fecha_apertura ? $corte->fecha_apertura->format('d/m/Y H:i') : '-' }}</div>
                                      <div class="text-muted"><strong>C:</strong> {{ $corte->fecha_cierre ? $corte->fecha_cierre->format('d/m/Y H:i') : 'En curso' }}</div>
                                  </td>
                                  <td>${{ number_format($corte->monto_inicial, 2) }}</td>
                                  <td class="fw-bold text-primary">${{ number_format($corte->total_cobrado_sistema, 2) }}</td>
                                  <td class="fw-bold">${{ number_format($corte->total_efectivo_entregado, 2) }}</td>
                                  <td>
                                      @if($corte->estatus === 'ABIERTO')
                                          <span class="text-muted">--</span>
                                      @elseif($corte->diferencia == 0)
                                          <span class="badge bg-success bg-opacity-10 text-success border">$0.00</span>
                                      @elseif($corte->diferencia < 0)
                                          <span class="badge bg-danger bg-opacity-10 text-danger border">${{ number_format($corte->diferencia, 2) }}</span>
                                      @else
                                          <span class="badge bg-warning bg-opacity-10 text-dark border">+${{ number_format($corte->diferencia, 2) }}</span>
                                      @endif
                                  </td>
                                  <td>
                                      @if($corte->estatus === 'ABIERTO')
                                          <span class="badge bg-success">ABIERTO</span>
                                      @else
                                          <span class="badge bg-secondary">CERRADO</span>
                                      @endif
                                  </td>
                                  <td class="text-start text-truncate" style="max-width: 150px;" title="{{ $corte->observaciones }}">
                                      {{ $corte->observaciones ?? '-' }}
                                  </td>
                              </tr>
                          @empty
                              <tr>
                                  <td colspan="8" class="py-4 text-muted">Aún no hay cortes de caja registrados.</td>
                              </tr>
                          @endforelse
                      </tbody>
                  </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JAVASCRIPT -->
  <script>
    let subtotalSinExtras = 0;
    let descuentoActual = 0;

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
        // Al perdonar años atrasados, se registra el monto condonado en la factura
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
        if(radioPeriodos && !radioPeriodos.checked) {
            radioPeriodos.checked = true;
        }
        calcularCanva();
    }

    function calcularConsumo() {
        let lecAnt = parseInt(document.getElementById('inputLecAnt').value) || 0;
        let lecAct = parseInt(document.getElementById('inputLecAct').value) || 0;
        let consumo = lecAct - lecAnt;
        if (consumo < 0) consumo = 0;
        
        document.getElementById('txtConsumo').innerText = consumo;
        document.getElementById('inputConsumoOculto').value = consumo;
    }

    function recalcularTotal() {
        let rezago = parseFloat(document.getElementById('inputRezagoManual').value) || 0;
        let recargo = parseFloat(document.getElementById('inputRecargoManual').value) || 0;
        let sancion = parseFloat(document.getElementById('inputSancionManual').value) || 0;
        let excedente = parseFloat(document.getElementById('inputExcedente') ? document.getElementById('inputExcedente').value : 0) || 0;
        let condonacion = parseFloat(document.getElementById('inputFormCondonacionMonto').value) || 0;

        let descuentoTotal = descuentoActual + condonacion;
        let txtDescuento = document.getElementById('txtDescuento');
        if (txtDescuento) {
            txtDescuento.innerText = (descuentoTotal > 0) ? '-$' + descuentoTotal.toFixed(2) : 'NO';
        }

        let nuevoTotal = subtotalSinExtras + excedente + rezago + recargo + sancion - descuentoTotal;
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

        let base = (parseFloat(cobroElegido.getAttribute('data-base')) || 0) * (isCobroPeriodico ? meses : 1);
        let san = (parseFloat(cobroElegido.getAttribute('data-san')) || 0) * (isCobroPeriodico ? meses : 1);
        let inf = (parseFloat(cobroElegido.getAttribute('data-inf')) || 0) * (isCobroPeriodico ? meses : 1);
        let rec = parseFloat(cobroElegido.getAttribute('data-rec')) || 0;
        
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

        document.getElementById('txtBase').innerText = '$' + base.toFixed(2);
        document.getElementById('txtSaneamiento').innerText = '$' + san.toFixed(2);
        document.getElementById('txtInfra').innerText = '$' + inf.toFixed(2);
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

    function ejecutarPago() {
        // 1. Envía el formulario hacia la pestaña nueva donde se genera el PDF
        document.getElementById('formPago').submit();

        // 2. Transforma el modal en "¡Operación Exitosa!" igual a tu vista de Tomas de Agua
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

        @if(session('success'))
            let modalExito = document.getElementById('modalExitoGlobal');
            if (modalExito) {
                new bootstrap.Modal(modalExito).show();
            }
        @endif
    });
  </script>
</div>
@endsection