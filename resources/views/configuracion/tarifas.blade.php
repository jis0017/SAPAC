@extends('layouts.app')

@section('title', 'Cuotas y Tarifas - SAPAC')

@section('content')
<div class="container-fluid px-0">

  <!-- ENCABEZADO PRINCIPAL ALINEADO EN UNA SOLA FILA -->
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div class="d-flex align-items-center">
      <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3 d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 50px; height: 50px;">
        <i class="bi bi-sliders fs-4" aria-hidden="true"></i>
      </div>
      <div>
        <span class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.72rem; letter-spacing: 0.05em;">Tabulador Oficial SAPAC</span>
        <h1 class="h4 fw-bold text-body mb-0">Cuotas Fijas y Tarifas por Medidor</h1>
      </div>
    </div>

    <!-- BOTONES REDISEÑADOS CON JERARQUÍA VISUAL (CON TEXTO GRUESO) -->
    <div class="d-flex flex-wrap flex-sm-nowrap gap-2 align-items-center">
      
      <!-- Botón Terciario -->
      <button class="btn btn-light border bg-white d-flex align-items-center gap-2 shadow-sm fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#modalGestionTiposPredio">
        <i class="bi bi-tags fs-6 text-warning"></i>
        <span>Tipos de Predio</span>
      </button>

      <!-- Botón Secundario -->
      <button class="btn bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 d-flex align-items-center gap-2 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTarifaMedida" onclick="prepararCreacionTarifaMedida()">
        <i class="bi bi-speedometer2 fs-6"></i>
        <span>Nueva Tarifa (m³)</span>
      </button>

      <!-- Botón Primario -->
      <button class="btn btn-primary d-flex align-items-center gap-2 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalCuotaPeriodo" onclick="prepararCreacionCuota()">
        <i class="bi bi-plus-circle fs-6 text-white"></i>
        <span>Nueva Cuota Anual</span>
      </button>
  
    </div>

  </div>

  <!-- TARJETAS RESUMEN (KPIs) -->
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-primary bg-opacity-10 p-2.5 text-primary me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-calendar-check fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Año en Curso / Vigencia</span>
            <h4 class="fw-bold mb-0 text-body">{{ date('Y') }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-success bg-opacity-10 p-2.5 text-success me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-cash-stack fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Tabuladores Cuota Fija</span>
            <h4 class="fw-bold mb-0 text-body">{{ isset($cuotas) ? count($cuotas) : 0 }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-info bg-opacity-10 p-2.5 text-info me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-speedometer2 fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Rangos Servicio Medido</span>
            <h4 class="fw-bold mb-0 text-body">{{ isset($tarifasMedidas) ? count($tarifasMedidas) : 0 }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-warning bg-opacity-10 p-2.5 text-warning me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-tags fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Tipos de Predio</span>
            <h4 class="fw-bold mb-0 text-body">{{ isset($tipoPredios) ? count($tipoPredios) : 0 }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4 border-0" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Atención:</strong> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  @endif

  <!-- TARJETA PRINCIPAL CON PESTAÑAS INTEGRADAS -->
  <div class="card border-0 shadow-sm">
    
    <div class="card-header bg-white pt-3 pb-0 px-4 border-bottom">
      <ul class="nav nav-tabs card-header-tabs gap-2" id="tabuladorTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link {{ session('tab_activo') == 'medido' ? '' : 'active' }} fw-bold px-3 py-2 d-flex align-items-center gap-2" id="tab-cuotas-btn" data-bs-toggle="tab" data-bs-target="#pane-cuotas" type="button" role="tab">
            <i class="bi bi-journal-text text-primary"></i>
            <span>Cuotas Fijas y Periodos Anuales</span>
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill ms-1">{{ isset($cuotas) ? count($cuotas) : 0 }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link {{ session('tab_activo') == 'medido' ? 'active' : '' }} fw-bold px-3 py-2 d-flex align-items-center gap-2" id="tab-medido-btn" data-bs-toggle="tab" data-bs-target="#pane-medido" type="button" role="tab">
            <i class="bi bi-speedometer2 text-info"></i>
            <span>Tarifas por Medidor</span>
            <span class="badge bg-info bg-opacity-10 text-info rounded-pill ms-1">{{ isset($tarifasMedidas) ? count($tarifasMedidas) : 0 }}</span>
          </button>
        </li>
      </ul>
    </div>

    <div class="tab-content" id="tabuladorTabsContent">

      <!-- =========================================================================
           PESTAÑA 1: TABLA DE CUOTAS Y PERIODOS ANUALES
           ========================================================================= -->
      <div class="tab-pane fade {{ session('tab_activo') == 'medido' ? '' : 'show active' }}" id="pane-cuotas" role="tabpanel">
        <div class="table-responsive w-100"> <!-- Asegurar que ocupe todo el ancho -->
          <table class="table table-hover align-middle mb-0 w-100">
            <thead class="table-light text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: 0.03em;">
              <tr class="text-center text-nowrap"> <!-- Evitar saltos de línea en cabecera -->
                <th class="ps-3 text-start" style="width: 70px;">Año</th>
                <th class="text-start" style="min-width: 250px;">Tipo de Predio y Localidad</th>
                <th style="min-width: 100px;">Cuota Mes</th>
                <th style="min-width: 100px;">Saneamiento</th>
                <th style="min-width: 100px;">Infraestructura</th>
                <th style="min-width: 120px;">Total Anual</th>
                <th style="min-width: 150px;">Descuentos (15% / 50%)</th>
                <th style="min-width: 120px;">Contrato / Reconex.</th>
                <th class="text-end pe-3" style="min-width: 90px; width: 90px;">Acciones</th>
              </tr>
            </thead>
            <tbody class="text-body" style="font-size: 0.84rem;">
              @forelse($cuotas as $cuota)
                <tr class="text-center text-nowrap"> <!-- Mantener fila en una sola línea -->
                  <td class="ps-3 text-start">
                    <span class="badge bg-secondary bg-opacity-10 text-body font-monospace px-2 py-1 border">
                      {{ $cuota->anio }}
                    </span>
                  </td>
                  <td class="text-start text-wrap"> <!-- Permitir texto largo solo aquí -->
                    <div class="fw-bold text-body text-uppercase" style="line-height: 1.2;">
                      {{ $cuota->tipoPredio->nombre_predio ?? 'GENERAL' }}
                    </div>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.75rem;">
                      <i class="bi bi-geo-alt me-1 text-primary"></i>{{ $cuota->localidad->nombre_localidad ?? 'TODAS (GENERAL)' }}
                    </small>
                  </td>
                  <td class="font-monospace">${{ number_format($cuota->cuota_mensual, 2) }}</td>
                  <td class="font-monospace">${{ number_format($cuota->saneamiento_mensual, 2) }}</td>
                  <td class="font-monospace">${{ number_format($cuota->otros_usos_mensual, 2) }}</td>
                  <td class="fw-bold text-primary font-monospace fs-6">
                    ${{ number_format($cuota->total_anual_sin_descuento, 2) }}
                  </td>
                  <td>
                    <div class="d-flex flex-column align-items-center gap-1">
                      <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 font-monospace px-2 py-0.5 text-nowrap" style="font-size: 0.73rem;">
                        {{ number_format($cuota->porcentaje_descto1, 0) }}%: ${{ number_format($cuota->total_anual_descto1, 2) }}
                      </span>
                      <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 font-monospace px-2 py-0.5 text-nowrap" style="font-size: 0.73rem;">
                        {{ number_format($cuota->porcentaje_descto2, 0) }}%: ${{ number_format($cuota->total_anual_descto2, 2) }}
                      </span>
                    </div>
                  </td>
                  <td>
                    <div class="small font-monospace" style="line-height: 1.3;">
                      <span class="text-muted" style="font-size: 0.72rem;">Cont:</span> <strong class="text-body">${{ number_format($cuota->costo_nuevo_contrato, 2) }}</strong>
                    </div>
                    <div class="small font-monospace" style="line-height: 1.3;">
                      <span class="text-muted" style="font-size: 0.72rem;">Rec:</span> <span class="text-secondary">${{ number_format($cuota->costo_reconexion, 2) }}</span>
                    </div>
                  </td>
                  <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-secondary" title="Editar Cuota" 
                        onclick="abrirModalEditar(
                          '{{ $cuota->id_cuota_periodo }}', '{{ $cuota->anio }}', '{{ $cuota->id_tipo_predio }}',
                          '{{ $cuota->id_localidad }}', '{{ $cuota->cuota_mensual }}', '{{ $cuota->saneamiento_mensual }}',
                          '{{ $cuota->otros_usos_mensual }}', '{{ $cuota->total_anual_sin_descuento }}',
                          '{{ $cuota->porcentaje_descto1 }}', '{{ $cuota->total_anual_descto1 }}',
                          '{{ $cuota->porcentaje_descto2 }}', '{{ $cuota->total_anual_descto2 }}',
                          '{{ $cuota->costo_nuevo_contrato }}', '{{ $cuota->costo_reconexion }}'
                        )">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button type="button" class="btn btn-outline-danger" title="Eliminar Cuota" onclick="abrirModalEliminarCuota('{{ $cuota->id_cuota_periodo }}', '{{ $cuota->anio }}', '{{ addslashes($cuota->tipoPredio->nombre_predio ?? 'GENERAL') }}')">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    No hay cuotas o periodos registrados en el tabulador.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <!-- =========================================================================
           PESTAÑA 2: TABLA DE SERVICIO MEDIDO
           ========================================================================= -->
      <div class="tab-pane fade {{ session('tab_activo') == 'medido' ? 'show active' : '' }}" id="pane-medido" role="tabpanel">
        <div class="table-responsive w-100"> <!-- Asegurar que ocupe todo el ancho -->
          <table class="table table-hover align-middle mb-0 text-center w-100">
            <thead class="table-light text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: 0.03em;">
              <tr class="text-nowrap"> <!-- Evitar saltos de línea en cabecera -->
                <th class="ps-3 text-start" style="width: 90px;">Vigencia</th>
                <th class="text-start" style="min-width: 250px;">Tipo de Predio</th>
                <th style="min-width: 150px;">Cuota Base (Límite)</th>
                <th style="min-width: 120px;">Costo Base</th>
                <th style="min-width: 150px;">Rango de Excedente</th>
                <th style="min-width: 150px;">Precio m³ Excedente</th>
                <th class="text-end pe-3" style="min-width: 100px; width: 100px;">Acciones</th>
              </tr>
            </thead>
            <tbody class="text-body" style="font-size: 0.85rem;">
              @forelse($tarifasMedidas as $tm)
                <tr class="text-nowrap"> <!-- Mantener fila en una sola línea -->
                  <td class="ps-3 text-start">
                    <span class="badge bg-secondary bg-opacity-10 text-body font-monospace px-2 py-1 border">
                      {{ $tm->anio_vigencia }}
                    </span>
                  </td>
                  <td class="text-start fw-bold text-uppercase text-wrap">{{ $tm->tipoPredio->nombre_predio ?? 'GENERAL' }}</td>
                  
                  <td><span class="badge bg-info bg-opacity-10 text-info border font-monospace px-2 py-1">Hasta {{ $tm->m3_base }} m³</span></td>
                  <td class="fw-bold text-dark font-monospace">${{ number_format($tm->cuota_base, 2) }}</td>
                  
                  <td>
                    <span class="badge bg-light text-body border font-monospace px-2.5 py-1">
                      {{ $tm->limite_inferior }} m³ <i class="bi bi-arrow-right mx-1 text-muted"></i> 
                      {{ $tm->limite_superior !== null ? $tm->limite_superior . ' m³' : 'EN ADELANTE' }}
                    </span>
                  </td>
                  
                  <td class="fw-bold text-danger font-monospace">${{ number_format($tm->precio_m3_excedente, 2) }}</td>
                  
                  <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-secondary" title="Editar Rango"
                        onclick="abrirModalEditarTarifaMedida(
                          '{{ $tm->id_tarifa }}',
                          '{{ $tm->anio_vigencia }}',
                          '{{ $tm->id_tipo_predio }}',
                          '{{ $tm->m3_base }}',
                          '{{ $tm->cuota_base }}',
                          '{{ $tm->limite_inferior }}',
                          '{{ $tm->limite_superior !== null ? $tm->limite_superior : '' }}',
                          '{{ $tm->precio_m3_excedente }}'
                        )">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button type="button" class="btn btn-outline-danger" title="Eliminar Rango"
                        onclick="abrirModalEliminarTarifaMedida(
                          '{{ $tm->id_tarifa }}',
                          '{{ $tm->limite_inferior }} a {{ $tm->limite_superior !== null ? $tm->limite_superior : 'En adelante' }} m³',
                          '{{ addslashes($tm->tipoPredio->nombre_predio ?? 'GENERAL') }}'
                        )">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-speedometer fs-1 d-block mb-2"></i>
                    No hay rangos de servicio medido registrados todavía.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL 1: GESTIÓN DE TIPOS DE PREDIO
     ========================================================================= -->
<div class="modal fade" id="modalGestionTiposPredio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="max-width: 700px; margin: auto;">
      
      <div class="modal-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="text-primary d-flex align-items-center justify-content-center me-1">
            <i class="bi bi-tags fs-4"></i>
          </div>
          <h5 class="modal-title fw-bold mb-0 text-body">Gestión de Tipos de Predio</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body p-4">
        
        <form id="formTipoPredio" action="{{ route('configuracion.tipo-predio.store') }}" method="POST" class="mb-4 bg-light p-3 rounded-3 border">
          @csrf
          <div id="methodPredioContainer"></div>
          <div class="row g-2 align-items-end">
            <div class="col">
              <label class="form-label small fw-semibold text-body mb-1" id="labelFormPredio">Registrar Nuevo Tipo de Predio</label>
              <input type="text" class="form-control form-control-sm text-uppercase" id="nombre_predio" name="nombre_predio" placeholder="EJ. COMERCIAL, DOMÉSTICO..." required autocomplete="off">
            </div>
            <div class="col-auto">
              <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm" id="btnGuardarPredio">
                <i class="bi bi-check-lg me-1"></i> Guardar
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary px-3 d-none" id="btnCancelarEdicionPredio" onclick="resetFormPredio()">
                Cancelar
              </button>
            </div>
          </div>
        </form>

        <h6 class="fw-bold text-secondary small text-uppercase mb-2"><i class="bi bi-list-ul me-1"></i> Predios Registrados en el Sistema</h6>
        <div class="table-responsive border rounded-3" style="max-height: 250px; overflow-y: auto;">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
              <tr>
                <th class="ps-3" style="width: 80px;">ID</th>
                <th>Nombre del Predio</th>
                <th class="text-end pe-3" style="width: 120px;">Acciones</th>
              </tr>
            </thead>
            <tbody class="small text-body">
              @forelse($tipoPredios as $tp)
                <tr>
                  <td class="ps-3 font-monospace fw-bold">#{{ $tp->id_tipo_predio }}</td>
                  <td class="fw-semibold text-uppercase">{{ $tp->nombre_predio }}</td>
                  <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-secondary" title="Editar" onclick="prepararEdicionPredio('{{ $tp->id_tipo_predio }}', '{{ addslashes($tp->nombre_predio) }}')">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button type="button" class="btn btn-outline-danger" title="Eliminar" onclick="abrirModalEliminarPredio('{{ $tp->id_tipo_predio }}', '{{ addslashes($tp->nombre_predio) }}')">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center py-3 text-muted">No hay tipos de predio registrados.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

      </div>

      <div class="modal-footer border-top py-2.5 px-4 bg-light">
        <button type="button" class="btn btn-sm btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL 2: CREAR Y EDITAR CUOTA / PERIODO 
     ========================================================================= -->
<div class="modal fade" id="modalCuotaPeriodo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="max-width: 850px; margin: auto;">
      
      <div class="modal-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="text-primary d-flex align-items-center justify-content-center me-1">
            <i class="bi bi-journal-plus fs-4"></i>
          </div>
          <h5 class="modal-title fw-bold mb-0 text-body" id="tituloModalCuota">Configurar Nueva Cuota y Periodo Anual</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formCuotaPeriodo" action="{{ route('configuracion.cuotas.store') }}" method="POST">
        @csrf
        <div id="methodFieldContainer"></div>
        
        <div class="modal-body p-4 row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold">Año Fiscal / Vigencia <span class="text-danger">*</span></label>
            <input type="number" class="form-control form-control-sm font-monospace fw-bold" id="anio" name="anio" value="{{ date('Y') }}" required>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold">Tipo de Predio <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="id_tipo_predio" name="id_tipo_predio" required>
              <option value="">Seleccionar Tipo de Predio...</option>
              @foreach($tipoPredios as $tp)
                <option value="{{ $tp->id_tipo_predio }}">{{ $tp->nombre_predio }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold">Localidad (Opcional)</label>
            <select class="form-select form-select-sm" id="id_localidad" name="id_localidad">
              <option value="">Todas (General)</option>
              @foreach($localidades ?? [] as $loc)
                <option value="{{ $loc->id_localidad }}">{{ $loc->nombre_localidad }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12"><hr class="my-1"></div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold text-primary">Cuota Base Mensual ($) *</label>
            <input type="number" step="0.01" class="form-control form-control-sm fw-bold font-monospace" id="cuota_mensual" name="cuota_mensual" value="0.00" oninput="calcularValoresAnuales()" required>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold">Saneamiento Mensual ($)</label>
            <input type="number" step="0.01" class="form-control form-control-sm" id="saneamiento_mensual" name="saneamiento_mensual" value="0.00" oninput="calcularValoresAnuales()" required>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold">Infraestructura Mensual ($)</label>
            <input type="number" step="0.01" class="form-control form-control-sm" id="otros_usos_mensual" name="otros_usos_mensual" value="0.00" oninput="calcularValoresAnuales()" required>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label small fw-semibold text-success">Total Anual Sin Descuento (Calculado) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" class="form-control form-control-sm fw-bold font-monospace bg-light" id="total_anual_sin_descuento" name="total_anual_sin_descuento" value="0.00" readonly required>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold">% Descuento 1 (Ej. 15%)</label>
            <input type="number" step="0.01" class="form-control form-control-sm" id="porcentaje_descto1" name="porcentaje_descto1" value="15.00" oninput="calcularValoresAnuales()">
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold">Total con Descto. 1 ($)</label>
            <input type="number" step="0.01" class="form-control form-control-sm font-monospace bg-light" id="total_anual_descto1" name="total_anual_descto1" value="0.00" readonly>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold">% Descuento 2 (Ej. 50%)</label>
            <input type="number" step="0.01" class="form-control form-control-sm" id="porcentaje_descto2" name="porcentaje_descto2" value="50.00" oninput="calcularValoresAnuales()">
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold">Total con Descto. 2 ($)</label>
            <input type="number" step="0.01" class="form-control form-control-sm font-monospace bg-light" id="total_anual_descto2" name="total_anual_descto2" value="0.00" readonly>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold">Costo Nuevo Contrato ($)</label>
            <input type="number" step="0.01" class="form-control form-control-sm font-monospace" id="costo_nuevo_contrato" name="costo_nuevo_contrato" value="717.99" required>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold">Costo Reconexión ($)</label>
            <input type="number" step="0.01" class="form-control form-control-sm font-monospace" id="costo_reconexion" name="costo_reconexion" value="350.00" required>
          </div>
        </div>

        <div class="modal-footer border-top py-2.5 px-4 bg-light bg-opacity-25 d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm" id="btnGuardarModalCuota">Guardar Cuota y Periodo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL 3: CREAR Y EDITAR TARIFA MEDIDA (TARIFAS_CONFIG)
     ========================================================================= -->
<div class="modal fade" id="modalTarifaMedida" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="max-width: 800px; margin: auto;">
      
      <div class="modal-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="text-info d-flex align-items-center justify-content-center me-1">
            <i class="bi bi-speedometer2 fs-4"></i>
          </div>
          <h5 class="modal-title fw-bold mb-0 text-body" id="tituloModalTarifaMedida">Configurar Rango de Servicio Medido (m³)</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formTarifaMedida" action="{{ route('configuracion.tarifas-medidas.store') }}" method="POST">
        @csrf
        <div id="methodTarifaMedidaContainer"></div>
        <input type="hidden" id="tm_id_edicion" value="">

        <div class="modal-body p-4 row g-3">
          
          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold">Año de Vigencia <span class="text-danger">*</span></label>
            <input type="number" class="form-control form-control-sm font-monospace fw-bold" id="tm_anio_vigencia" name="anio_vigencia" value="{{ date('Y') }}" required>
          </div>

          <div class="col-12 col-md-8">
            <label class="form-label small fw-semibold">Tipo de Predio <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="tm_id_tipo_predio" name="id_tipo_predio" required>
              <option value="">Seleccionar Tipo de Predio...</option>
              @foreach($tipoPredios as $tp)
                <option value="{{ $tp->id_tipo_predio }}">{{ $tp->nombre_predio }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12"><hr class="my-1"></div>
          <h6 class="fw-bold text-secondary small text-uppercase mb-0">Datos de Cuota Fija (Base)</h6>

          <div class="col-12 col-md-6">
            <label class="form-label small fw-semibold">Consumo Base Permitido (m³) <span class="text-danger">*</span></label>
            <input type="number" class="form-control form-control-sm font-monospace" id="tm_m3_base" name="m3_base" placeholder="Ej: 12 o 20" min="0" required>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label small fw-semibold text-primary">Costo de Cuota Base ($) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" class="form-control form-control-sm fw-bold font-monospace" id="tm_cuota_base" name="cuota_base" placeholder="Ej: 141.88 o 231.00" min="0" required>
          </div>

          <div class="col-12"><hr class="my-1"></div>
          <h6 class="fw-bold text-secondary small text-uppercase mb-0">Rango de Excedente</h6>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold">Límite Inferior (m³) <span class="text-danger">*</span></label>
            <input type="number" class="form-control form-control-sm font-monospace" id="tm_limite_inferior" name="limite_inferior" placeholder="Ej: 13" min="0" required>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold">Límite Superior (m³)</label>
            <input type="number" class="form-control form-control-sm font-monospace" id="tm_limite_superior" name="limite_superior" placeholder="Dejar vacío para 'en adelante'" min="0">
            <small class="text-muted" style="font-size: 0.7rem;">Vacío = 91 en adelante</small>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold text-danger">Precio m³ Excedente ($) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" class="form-control form-control-sm fw-bold font-monospace text-danger" id="tm_precio_m3_excedente" name="precio_m3_excedente" placeholder="Ej: 8.34" min="0" required>
          </div>

        </div>

        <div class="modal-footer border-top py-2.5 px-4 bg-light bg-opacity-25 d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm" id="btnGuardarTarifaMedida">Guardar Rango</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ELIMINAR CUOTA -->
<div class="modal fade" id="modalEliminarCuota" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
      <div class="modal-body py-4">
        <div class="mb-3">
          <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 4px solid rgba(220, 53, 69, 0.15);">
            <i class="bi bi-trash text-danger fs-1"></i>
          </div>
        </div>
        <h4 class="fw-bold mb-2 text-dark">¿Eliminar Cuota?</h4>
        <p class="text-muted small mb-4 px-2">Está a punto de eliminar el tabulador del año <strong id="anioCuotaEliminar" class="text-dark"></strong> para el predio <strong id="predioCuotaEliminar" class="text-dark"></strong>. Esta acción no se puede deshacer.</p>
        
        <form id="formEliminarCuota" method="POST">
          @csrf
          @method('DELETE')
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-outline-secondary px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger px-4 rounded-pill fw-semibold shadow-sm">Sí, Eliminar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ELIMINAR TARIFA MEDIDA -->
<div class="modal fade" id="modalEliminarTarifaMedida" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
      <div class="modal-body py-4">
        <div class="mb-3">
          <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 4px solid rgba(220, 53, 69, 0.15);">
            <i class="bi bi-trash text-danger fs-1"></i>
          </div>
        </div>
        <h4 class="fw-bold mb-2 text-dark">¿Eliminar Rango Medido?</h4>
        <p class="text-muted small mb-4 px-2">Está a punto de eliminar el rango <strong id="rangoMedidoEliminar" class="text-dark"></strong> para el predio <strong id="predioMedidoEliminar" class="text-dark"></strong>. Esta acción no se puede deshacer.</p>
        
        <form id="formEliminarTarifaMedida" method="POST">
          @csrf
          @method('DELETE')
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-outline-secondary px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger px-4 rounded-pill fw-semibold shadow-sm">Sí, Eliminar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ELIMINAR TIPO DE PREDIO -->
<div class="modal fade" id="modalEliminarPredio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
      <div class="modal-body py-4">
        <div class="mb-3">
          <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 4px solid rgba(220, 53, 69, 0.15);">
            <i class="bi bi-trash text-danger fs-1"></i>
          </div>
        </div>
        <h4 class="fw-bold mb-2 text-dark">¿Eliminar Tipo de Predio?</h4>
        <p class="text-muted small mb-4 px-2">Está a punto de eliminar el tipo de predio <strong id="nombrePredioEliminar" class="text-dark"></strong>. Esta acción no se puede deshacer.</p>
        
        <form id="formEliminarPredio" method="POST">
          @csrf
          @method('DELETE')
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-outline-secondary px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger px-4 rounded-pill fw-semibold shadow-sm">Sí, Eliminar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DE NOTIFICACIÓN DE ÉXITO ÚNICO -->
@if(session('success'))
<div class="modal fade" id="modalExitoAccion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
      <div class="modal-body py-4">
        <div class="mb-3">
          <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 84px; height: 84px; border: 4px solid rgba(25, 135, 84, 0.15);">
            <i class="bi bi-check-lg text-success fs-1 fw-bold"></i>
          </div>
        </div>
        <h4 class="fw-bold mb-2 text-dark">¡Operación Exitosa!</h4>
        <p class="text-muted small mb-4 px-2">{{ session('success') }}</p>
        <button type="button" class="btn btn-primary px-5 py-2 rounded-pill fw-semibold shadow-sm" data-bs-dismiss="modal">
          Aceptar
        </button>
      </div>
    </div>
  </div>
</div>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    var modalExito = new bootstrap.Modal(document.getElementById('modalExitoAccion'));
    modalExito.show();
  });
</script>
@endif

@endsection

@push('scripts')
  <!-- Archivo limpio de JavaScript para las interacciones y validaciones -->
  <script src="{{ asset('assets/js/configuracion_tarifas.js') }}"></script>
@endpush