@extends('layouts.app')

@section('title', 'Pedidos de Pipas - SAPAC Acatic')

@section('content')
<div class="container-fluid px-0">

  <!-- ENCABEZADO DE PÁGINA -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div class="d-flex align-items-center">
      <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px;">
        <i class="bi bi-truck fs-3" aria-hidden="true"></i>
      </div>
      <div>
        <p class="text-uppercase text-muted fw-bold small mb-0 tracking-wide">Atención y Logística de Distribución</p>
        <h1 class="h3 fw-bold mb-0 text-body">Control de Solicitudes de Pipas</h1>
        <p class="text-muted small mb-0">Gestión de abastecimiento extraordinario, validación de solvencia y asignación de unidades.</p>
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
      <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPedidoPipa" onclick="prepararModalNuevoPedido()">
        <i class="bi bi-plus-circle" aria-hidden="true"></i>
        <span>Nueva Solicitud de Pipa</span>
      </button>
    </div>
  </div>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4 border-0" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Atención:</strong> {{ $errors->first() }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  @endif

  <!-- BARRA DE BÚSQUEDA Y FILTRADO -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form action="{{ route('pedidos.pipas') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-body border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control border-start-0 ps-0" name="buscar" placeholder="Buscar por Folio, Solicitante, Teléfono o Domicilio..." value="{{ request('buscar') }}">
          </div>
        </div>

        <div class="col-6 col-md-3">
          <select class="form-select text-muted" name="estado">
            <option value="">-- Todos los Estados --</option>
            <option value="Pendiente" {{ request('estado') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="En camino" {{ request('estado') == 'En camino' ? 'selected' : '' }}>En camino</option>
            <option value="Entregado" {{ request('estado') == 'Entregado' ? 'selected' : '' }}>Entregado</option>
            <option value="Cancelado" {{ request('estado') == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <input type="date" class="form-control text-muted" name="fecha" value="{{ request('fecha') }}">
        </div>

        <div class="col-12 col-md-2 d-grid">
          <button type="submit" class="btn btn-secondary d-flex align-items-center justify-content-center gap-1">
            <i class="bi bi-funnel"></i> Filtrar
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- TABLA PRINCIPAL DE PEDIDOS -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-uppercase small text-muted">
          <tr>
            <th class="ps-3" style="width: 120px;">Folio / ID</th>
            <th>Toma / Solicitante</th>
            <th>Ubicación de Entrega</th>
            <th>Capacidad</th>
            <th>Programación</th>
            <th>Chófer / Pozo</th>
            <th>Estatus</th>
            <th class="text-end pe-3" style="width: 140px;">Acciones</th>
          </tr>
        </thead>
        <tbody class="text-body">
          @forelse($pedidos as $pipa)
            <tr>
              <td class="ps-3">
                <span class="badge bg-secondary bg-opacity-10 text-body font-monospace px-2 py-1 fs-7 border">
                  {{ str_pad($pipa->id_pedido,  STR_PAD_LEFT) }} <!-- Formato de folio con ceros a la izquierda -->
                </span>
              </td>
              <td>
                <div class="fw-bold text-body">{{ $pipa->solicitante }}</div>
                <small class="text-muted">
                  <i class="bi bi-droplet-half me-1"></i>Toma #{{ $pipa->toma_id ?? 'Particular' }} ·
                  <i class="bi bi-telephone ms-1 me-1"></i>{{ $pipa->telefono }}
                </small>
              </td>
              <td>
                <div class="text-body fw-semibold text-truncate" style="max-width: 250px;">
                  {{ $pipa->domicilio_entrega }}
                </div>
                @if(!empty($pipa->observaciones))
                  <small class="text-muted text-truncate d-block" style="max-width: 250px;" title="{{ $pipa->observaciones }}">
                    <i class="bi bi-info-circle me-1"></i>{{ $pipa->observaciones }}
                  </small>
                @endif
              </td>
              <td>
                <span class="fw-bold text-body">{{ number_format($pipa->capacidad_litros) }} Lts</span>
                <small class="d-block text-muted">
                  {{ $pipa->capacidad_litros >= 10000 ? '(Pipa Completa)' : '(Media Pipa)' }}
                </small>
              </td>
              <td>
                <span class="small fw-semibold text-body d-block">
                  {{ $pipa->fecha_entrega ? date('d/M/Y', strtotime($pipa->fecha_entrega)) : '-' }}
                </span>
                <small class="text-muted">{{ $pipa->fecha_entrega ? date('h:i A', strtotime($pipa->fecha_entrega)) : '' }}</small>
              </td>
              <td>
                @if($pipa->trabajador)
                  <span class="small fw-semibold text-primary d-block">
                    <i class="bi bi-person-badge me-1"></i>{{ $pipa->trabajador->nombre }}
                  </span>
                @else
                  <span class="small text-muted d-block">
                    <i class="bi bi-clock me-1"></i>Sin asignar
                  </span>
                @endif
                <small class="text-muted font-monospace"><i class="bi bi-geo me-1"></i>{{ $pipa->pozo->nombre_pozo ?? 'Pozo Matriz' }}</small>
              </td>
              <td>
                @if($pipa->estado_entrega == 'Entregado')
                  <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                    <i class="bi bi-check2-circle me-1"></i>Entregado
                  </span>
                @elseif($pipa->estado_entrega == 'En camino')
                  <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
                    <i class="bi bi-truck me-1"></i>En camino
                  </span>
                @elseif($pipa->estado_entrega == 'Cancelado')
                  <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                    <i class="bi bi-x-circle me-1"></i>Cancelado
                  </span>
                @else
                  <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                    <i class="bi bi-hourglass-split me-1"></i>Pendiente
                  </span>
                @endif
              </td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-primary" title="Asignar Chófer / Cambiar Estado"
                    onclick="abrirModalEstado({ id: '{{ $pipa->id_pedido }}', solicitante: '{{ addslashes($pipa->solicitante) }}', chofer: '{{ $pipa->id_trabajador ?? '' }}', pozo: '{{ $pipa->pozo_id ?? '' }}', estado: '{{ $pipa->estado_entrega }}' })">
                    <i class="bi bi-arrow-repeat"></i>
                  </button>
                  <button class="btn btn-outline-secondary" title="Editar Pedido"
                    onclick="abrirModalEditarPedido({ id: '{{ $pipa->id_pedido }}', toma_id: '{{ $pipa->toma_id }}', solicitante: '{{ addslashes($pipa->solicitante) }}', telefono: '{{ $pipa->telefono }}', domicilio: '{{ addslashes($pipa->domicilio_entrega) }}', capacidad: '{{ $pipa->capacidad_litros }}', pozo_id: '{{ $pipa->pozo_id }}', chofer: '{{ $pipa->id_trabajador ?? '' }}', fecha: '{{ $pipa->fecha_entrega ? date('Y-m-d\TH:i', strtotime($pipa->fecha_entrega)) : '' }}', observaciones: '{{ addslashes($pipa->observaciones ?? '') }}' })">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button type="button" class="btn btn-outline-danger" title="Eliminar Pedido" onclick="abrirModalEliminarPedido('{{ $pipa->id_pedido }}', '{{ addslashes($pipa->solicitante) }}')">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No se encontraron solicitudes de pipas registradas.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Paginador Real de Laravel -->
    <div class="card-footer bg-body border-0 py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <small class="text-muted">Mostrando registros del {{ $pedidos->firstItem() ?? 0 }} al {{ $pedidos->lastItem() ?? 0 }} de {{ $pedidos->total() }} solicitudes</small>
      {{ $pedidos->links() }}
    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL: REGISTRAR / EDITAR SOLICITUD DE PIPA
     ========================================================================= -->
<div class="modal fade" id="modalPedidoPipa" tabindex="-1" aria-labelledby="modalPedidoHeader" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">

      <div class="modal-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
            <i class="bi bi-truck"></i>
          </div>
          <h5 class="modal-title fw-bold mb-0 text-body" id="modalPedidoHeader">Nueva Solicitud de Pipa</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formPedidoPipa" action="{{ route('pedidos.pipas.store') }}" method="POST">
        @csrf
        <input type="hidden" name="_method" id="pedidoMethod" value="POST">
        <input type="hidden" name="id" id="pedido_id">
        <input type="hidden" name="toma_id" id="toma_id_seleccionada">

        <div class="modal-body p-4 row g-3">

          <!-- 1. Búsqueda y Validación de Toma Física -->
          <div class="col-12">
            <label class="form-label small fw-semibold text-primary mb-1">
              <i class="bi bi-search me-1"></i> 1. Validar Solvencia de Toma de Agua <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-sm">
              <input type="text" class="form-control" id="buscadorTomaInput" placeholder="Ingresa No. de Toma, Nombre del Ciudadano, Domicilio o Medidor..." onkeydown="handleEnterBusqueda(event)">
              <button class="btn btn-primary px-3" type="button" id="btnBuscarToma" onclick="buscarTomaSolvencia()">
                <span id="btnBuscarTexto"><i class="bi bi-check2-circle me-1"></i> Verificar Toma</span>
                <span id="btnBuscarSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
              </button>
            </div>
            <div id="mensajeBusquedaVacio" class="text-danger small mt-1" style="display: none;"></div>
          </div>

          <!-- Tarjeta de Solvencia -->
          <div class="col-12" id="contenedorEstadoToma" style="display: none;">
            <div class="card border p-3 rounded-3 shadow-sm bg-body" id="cardSolvencia">
              <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                <div>
                  <h6 class="fw-bold mb-0 text-body" id="resNombreTitular">Nombre del Titular</h6>
                  <small class="text-muted" id="resDetalleToma">Toma #0000 · Tipo Propiedad</small>
                </div>
                <div id="resBadgeEstado"></div>
              </div>
              <div class="small text-muted mb-2">
                <i class="bi bi-geo-alt me-1"></i><span id="resDireccionCompleta">Dirección registrada</span>
              </div>

              <!-- Alerta de Adeudo -->
              <div id="alertaAdeudo" class="alert alert-danger p-2 mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2" style="display: none;">
                <div>
                  <i class="bi bi-exclamation-octagon-fill me-1"></i>
                  <strong>Presenta un adeudo acumulado de <span id="montoAdeudoTxt">$0.00</span></strong>
                </div>
                <a href="{{ route('pagos.index') }}" target="_blank" class="btn btn-sm btn-danger fw-semibold">
                  <i class="bi bi-cash-coin me-1"></i> Ir a Caja a Cobrar
                </a>
              </div>
            </div>
          </div>

          <div class="col-12"><hr class="my-1 text-muted opacity-25"></div>

          <!-- 2. Datos del Pedido -->
          <div class="col-12">
            <h6 class="fw-bold text-body mb-0 small text-uppercase tracking-wide"><i class="bi bi-card-checklist me-1"></i> 2. Detalles del Pedido y Entrega</h6>
          </div>

          <div class="col-12 col-md-6">
            <label for="solicitante" class="form-label small fw-semibold text-body mb-1">Nombre de Quien Recibe <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="solicitante" name="solicitante" placeholder="Nombre completo" required>
          </div>

          <!-- TELÉFONO LIMITADO A 10 DÍGITOS -->
          <div class="col-12 col-md-6">
            <label for="telefono" class="form-label small fw-semibold text-body mb-1">Teléfono de Contacto (10 Dígitos) <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm font-monospace" id="telefono" name="telefono" placeholder="Ej. 3781234567" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
          </div>

          <div class="col-12 col-md-4">
            <label for="capacidad_litros" class="form-label small fw-semibold text-body mb-1">Volumen / Capacidad <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="capacidad_litros" name="capacidad_litros" required>
              <option value="5000">5,000 Lts (Media Pipa)</option>
              <option value="10000" selected>10,000 Lts (Pipa Completa)</option>
              <option value="15000">15,000 Lts (Granja / Industrial)</option>
            </select>
          </div>

          <!-- ASIGNACIÓN EXCLUSIVA DE PIPEROS -->
          <div class="col-12 col-md-4">
            <label for="id_trabajador" class="form-label small fw-semibold text-body mb-1">Asignar Pipero</label>
            <select class="form-select form-select-sm" id="id_trabajador" name="id_trabajador">
              <option value="">-- Sin asignar (En espera) --</option>
              @foreach($choferes as $chof)
                <option value="{{ $chof->id_trabajador }}">{{ $chof->nombre }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label for="pozo_id" class="form-label small fw-semibold text-body mb-1">Pozo de Abastecimiento</label>
            <select class="form-select form-select-sm" id="pozo_id" name="pozo_id">
              <option value="">Seleccionar Pozo...</option>
              @foreach($pozos as $pozo)
                <option value="{{ $pozo->id_pozo }}">{{ $pozo->nombre_pozo }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label for="fecha_entrega" class="form-label small fw-semibold text-body mb-1">Fecha y Hora Programada <span class="text-danger">*</span></label>
            <input type="datetime-local" class="form-control form-control-sm" id="fecha_entrega" name="fecha_entrega" value="{{ date('Y-m-d\TH:i') }}" required>
          </div>

          <div class="col-12 col-md-6">
            <label for="domicilio_entrega" class="form-label small fw-semibold text-body mb-1">Domicilio Exacto de Entrega <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="domicilio_entrega" name="domicilio_entrega" placeholder="Calle, número exterior/interior, colonia" required>
          </div>

          <div class="col-12">
            <label for="observaciones" class="form-label small fw-semibold text-body mb-1">Referencias de Acceso / Capacidad de Aljibe</label>
            <textarea class="form-control form-control-sm" id="observaciones" name="observaciones" rows="2" placeholder="Ej. Casa portón café junto al templo..."></textarea>
          </div>

        </div>

        <div class="modal-footer border-top py-2 px-4 bg-light bg-opacity-25">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary fw-semibold" id="btnGuardarPedido">
            <i class="bi bi-save2 me-1"></i> Guardar Solicitud de Pipa
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL: ASIGNAR CHÓFER Y CAMBIAR ESTADO LOGÍSTICO
     ========================================================================= -->
<div class="modal fade" id="modalEstadoPipa" tabindex="-1" aria-labelledby="modalEstadoPipaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">

      <div class="modal-header border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold text-body mb-0" id="modalEstadoPipaLabel">
          <i class="bi bi-arrow-repeat text-primary me-2"></i>Estatus de Ruta y Pipero
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formEstadoPipa" action="" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body p-4 row g-3">
          <div class="col-12">
            <span class="text-muted small d-block">Ciudadano Solicitante:</span>
            <h6 class="fw-bold text-body mb-0" id="txtSolicitante">--</h6>
          </div>

          <div class="col-12">
            <label for="chofer_asignado" class="form-label small fw-semibold text-body mb-1">Pipero Asignado</label>
            <select class="form-select form-select-sm" id="chofer_asignado" name="id_trabajador">
              <option value="">-- Sin asignar / Lista de espera --</option>
              @foreach($choferes as $chof)
                <option value="{{ $chof->id_trabajador }}">{{ $chof->nombre }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12">
            <label for="pozo_carga_id" class="form-label small fw-semibold text-body mb-1">Pozo de Suministro</label>
            <select class="form-select form-select-sm" id="pozo_carga_id" name="pozo_id">
              @foreach($pozos as $p)
                <option value="{{ $p->id_pozo }}">{{ $p->nombre_pozo }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12">
            <label for="estado_pedido" class="form-label small fw-semibold text-body mb-1">Estatus de la Entrega <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="estado_pedido" name="estado_entrega" required>
              <option value="Pendiente">Pendiente (En espera de ruta)</option>
              <option value="En camino">En camino (Unidad en traslado)</option>
              <option value="Entregado">Entregado (Servicio completado)</option>
              <option value="Cancelado">Cancelado</option>
            </select>
          </div>
        </div>

        <div class="modal-footer border-top py-2 px-4 bg-light bg-opacity-25">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-check-circle me-1"></i> Actualizar Estatus
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL DE CONFIRMACIÓN PARA ELIMINAR PEDIDO
     ========================================================================= -->
<div class="modal fade" id="modalEliminarPedido" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
      <div class="modal-body py-4">
        <div class="mb-3">
          <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 4px solid rgba(220, 53, 69, 0.15);">
            <i class="bi bi-trash text-danger fs-1"></i>
          </div>
        </div>
        <h4 class="fw-bold mb-2 text-dark">¿Eliminar Solicitud?</h4>
        <p class="text-muted small mb-4 px-2">Está a punto de eliminar el pedido de <strong id="nombrePedidoEliminar" class="text-dark"></strong>. Esta acción no se puede deshacer.</p>
        
        <form id="formEliminarPedido" method="POST">
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

<!-- =========================================================================
     MODAL DE ÉXITO GLOBAL (Palomita Verde Institucional)
     ========================================================================= -->
@if(session('success'))
<div class="modal fade" id="modalExitoGlobal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content border-0 shadow-lg p-4 text-center rounded-4">
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
@endsection
@push('scripts')
<script src="{{ asset('assets/js/pedido_pipa.js') }}"></script>
@endpush