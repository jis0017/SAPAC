@extends('layouts.app')

@section('title', 'Catálogo de Trabajadores - SAPAC')

@section('content')
<div class="container-fluid px-0">

  <!-- ENCABEZADO -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div class="d-flex align-items-center">
      <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px;">
        <i class="bi bi-people-fill fs-3" aria-hidden="true"></i>
      </div>
      <div>
        <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Administración Interna</span>
        <h1 class="h4 fw-bold text-body mb-0">Catálogo de Trabajadores y Operadores</h1>
      </div>
    </div>
    
    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-outline-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTiposTrabajador">
        <i class="bi bi-tags-fill"></i>
        <span>Gestionar Tipos / Roles</span>
      </button>

      <button class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTrabajador" onclick="prepararModalNuevo()">
        <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
        <span>Nuevo Trabajador</span>
      </button>
    </div>
  </div>

  <!-- TARJETAS DE MÉTRICAS (KPIs) -->
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-4">
    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-primary bg-opacity-10 p-2.5 text-primary me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-people fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Total Trabajadores</span>
            <h4 class="fw-bold mb-0 text-body">{{ number_format(\App\Models\Trabajadores::count()) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-success bg-opacity-10 p-2.5 text-success me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-shield-check fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Cuentas Activas</span>
            <h4 class="fw-bold mb-0 text-body">{{ number_format(\App\Models\Trabajadores::where('activo', 1)->count()) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-warning bg-opacity-10 p-2.5 text-warning me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-truck fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Operadores / Piperos</span>
            <h4 class="fw-bold mb-0 text-body">{{ \App\Models\Trabajadores::whereHas('tipoTrabajador', function($q){ $q->where('tipo_trabajador', 'LIKE', '%Pipero%'); })->count() }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4 border-0" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Atención:</strong> {{ $errors->first() }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  @endif

  <!-- BARRA DE BÚSQUEDA -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form action="{{ route('trabajadores.trabajadores') }}" method="GET">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-primary"></i></span>
          <input type="text" class="form-control border-start-0 text-uppercase" name="buscar" value="{{ request('buscar') }}" placeholder="BUSCAR POR NOMBRE O USUARIO DE ACCESO...">
          <button class="btn btn-primary px-4 fw-bold" type="submit">Buscar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- TABLA PRINCIPAL -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-uppercase small text-muted">
          <tr>
            <th class="ps-3" style="width: 90px;">ID</th>
            <th>Nombre del Trabajador</th>
            <th>Usuario de Acceso</th>
            <th>Tipo / Rol</th>
            <th>Estatus</th>
            <th class="text-end pe-3" style="width: 150px;">Acciones</th>
          </tr>
        </thead>
        <tbody class="text-body">
          @forelse($trabajadores as $trabajador)
            <tr>
              <td class="ps-3">
                <span class="badge bg-secondary bg-opacity-10 text-body font-monospace px-2 py-1 fs-7 border">
                  {{ str_pad($trabajador->id_trabajador, 3, '0', STR_PAD_LEFT) }}
                </span>
              </td>
              <td>
                <div class="fw-bold text-body">{{ $trabajador->nombre }}</div>
              </td>
              <td>
                <span class="font-monospace text-primary fw-semibold">{{ $trabajador->usuario_trabajador }}</span>
              </td>
              <td>
                <span class="badge bg-info bg-opacity-10 text-info border px-2 py-1">
                  {{ $trabajador->tipoTrabajador->tipo_trabajador ?? 'SIN ROL' }}
                </span>
              </td>
              <td>
                <span class="badge bg-{{ $trabajador->activo ? 'success' : 'danger' }} bg-opacity-10 text-{{ $trabajador->activo ? 'success' : 'danger' }} border px-2 py-1">
                  {{ $trabajador->activo ? 'ACTIVO' : 'INACTIVO' }}
                </span>
              </td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-secondary" title="Editar Trabajador" 
                    onclick="abrirModalEditar('{{ $trabajador->id_trabajador }}', '{{ addslashes($trabajador->nombre) }}', '{{ $trabajador->usuario_trabajador }}', '{{ $trabajador->id_tipo_trabajador }}', '{{ $trabajador->activo }}')">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button type="button" class="btn btn-outline-danger" title="Eliminar Trabajador" 
                    onclick="abrirModalEliminar('{{ $trabajador->id_trabajador }}', '{{ addslashes($trabajador->nombre) }}')">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">No hay trabajadores registrados en la base de datos.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- PAGINACIÓN -->
    <div class="card-footer bg-body border-0 py-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
      <small class="text-muted fw-semibold">
        Mostrando <span class="text-primary">{{ $trabajadores->firstItem() ?? 0 }}</span> a <span class="text-primary">{{ $trabajadores->lastItem() ?? 0 }}</span> de {{ $trabajadores->total() }} registros
      </small>
      {{ $trabajadores->links() }}
    </div>
  </div> 
</div> 

<!-- ========================================================================= -->
<!-- MODAL: GESTIÓN DE TIPOS / ROLES DE TRABAJADOR                             -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalTiposTrabajador" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-tags-fill me-2 text-primary"></i>Catálogo de Tipos / Roles de Trabajador</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-4">
          <!-- Formulario para agregar / editar rol -->
          <div class="col-md-5 border-end">
            <h6 class="fw-bold mb-3 text-secondary" id="tituloFormRol">Registrar Nuevo Rol</h6>
            <form id="formTipoTrabajador" action="{{ route('trabajadores.tipos.store') }}" method="POST">
              @csrf
              <div id="methodRolContainer"></div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Nombre del Rol <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm text-uppercase" id="inputNombreRol" name="tipo_trabajador" required placeholder="Ej. PIPERO, SECRETARIA" autocomplete="off">
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary w-50" onclick="resetFormRol()">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary w-50 fw-semibold" id="btnGuardarRol">Guardar Rol</button>
              </div>
            </form>
          </div>
          <!-- Listado de roles existentes -->
          <div class="col-md-7">
            <h6 class="fw-bold mb-3 text-secondary">Roles Existentes</h6>
            <div class="table-responsive border rounded-3" style="max-height: 250px; overflow-y: auto;">
              <table class="table table-sm table-hover align-middle mb-0 text-center">
                <thead class="table-light small">
                  <tr>
                    <th>ID</th>
                    <th>Nombre del Rol</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tbody class="small">
                  @foreach($tiposTrabajador as $tipo)
                  <tr>
                    <td class="font-monospace fw-bold">#{{ $tipo->id_tipo_trabajador }}</td>
                    <td class="text-uppercase fw-semibold text-start ps-3">{{ $tipo->tipo_trabajador }}</td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary py-0 px-2" title="Editar Rol" onclick="editarRol('{{ $tipo->id_tipo_trabajador }}', '{{ addslashes($tipo->tipo_trabajador) }}')">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger py-0 px-2" title="Eliminar Rol" onclick="abrirModalEliminarRol('{{ $tipo->id_tipo_trabajador }}', '{{ addslashes($tipo->tipo_trabajador) }}')">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ELIMINAR ROL -->
<div class="modal fade" id="modalEliminarRol" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
      <div class="modal-body py-4">
        <div class="mb-3">
          <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 4px solid rgba(220, 53, 69, 0.15);">
            <i class="bi bi-trash text-danger fs-1"></i>
          </div>
        </div>
        <h4 class="fw-bold mb-2 text-dark">¿Eliminar Rol?</h4>
        <p class="text-muted small mb-4 px-2">Está a punto de eliminar el tipo de trabajador <strong id="nombreRolEliminar" class="text-dark"></strong>. Si hay personal asignado, el sistema denegará la acción.</p>
        
        <form id="formEliminarRol" method="POST">
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

<!-- MODAL NUEVA / EDITAR TRABAJADOR -->
<div class="modal fade" id="modalTrabajador" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="text-primary d-flex align-items-center justify-content-center me-1">
            <i class="bi bi-person-badge fs-4"></i>
          </div>
          <h5 class="modal-title fw-bold mb-0 text-body" id="tituloModalTrabajador">Registrar Nuevo Trabajador</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formTrabajador" action="{{ route('trabajadores.store') }}" method="POST">
        @csrf
        <div id="methodFieldContainer"></div>

        <div class="modal-body p-4 row g-3">
          <div class="col-12">
            <label for="nombre" class="form-label small fw-semibold text-body mb-1">Nombre Completo <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="nombre" name="nombre" required placeholder="Ej. CARLOS RAMÍREZ PÉREZ" autocomplete="off">
          </div>

          <div class="col-12 col-md-6">
            <label for="usuario_trabajador" class="form-label small fw-semibold text-body mb-1">Usuario de Acceso <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm font-monospace" id="usuario_trabajador" name="usuario_trabajador" required placeholder="ej. cramirez" autocomplete="off">
          </div>

          <div class="col-12 col-md-6">
            <label for="password" class="form-label small fw-semibold text-body mb-1">Contraseña <span class="text-danger" id="spanAsteriscoPass">*</span></label>
            <input type="password" class="form-control form-control-sm" id="password" name="password" placeholder="Mínimo 6 caracteres">
            <small class="text-muted fs-8" id="AyudaPassword" style="display: none;">Dejar en blanco para conservar la actual.</small>
          </div>

          <div class="col-12 col-md-6">
            <label for="id_tipo_trabajador" class="form-label small fw-semibold text-body mb-1">Rol / Tipo de Trabajador <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="id_tipo_trabajador" name="id_tipo_trabajador" required>
              <option value="">Seleccionar Rol...</option>
              @foreach ($tiposTrabajador as $tipo)
                <option value="{{ $tipo->id_tipo_trabajador }}">{{ $tipo->tipo_trabajador }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label for="activo" class="form-label small fw-semibold text-body mb-1">Estatus de la Cuenta <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="activo" name="activo" required>
              <option value="1" selected>ACTIVO</option>
              <option value="0">INACTIVO</option>
            </select>
          </div>
        </div>

        <div class="modal-footer border-top py-2.5 px-4 bg-light bg-opacity-25 d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm" id="btnGuardarTrabajador">Guardar Trabajador</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ELIMINAR -->
<div class="modal fade" id="modalEliminarTrabajador" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
      <div class="modal-body py-4">
        <div class="mb-3">
          <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 4px solid rgba(220, 53, 69, 0.15);">
            <i class="bi bi-trash text-danger fs-1"></i>
          </div>
        </div>
        <h4 class="fw-bold mb-2 text-dark">¿Eliminar Trabajador?</h4>
        <p class="text-muted small mb-4 px-2">Está a punto de eliminar el acceso de <strong id="nombreTrabajadorEliminar" class="text-dark"></strong>. Esta acción no se puede deshacer.</p>
        
        <form id="formEliminarTrabajador" method="POST">
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

<!-- ========================================================================= -->
<!-- MODAL DE ÉXITO GLOBAL (Palomita Verde estilo Tomas de Agua / Pozos)        -->
<!-- ========================================================================= -->
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
<script src="{{ asset('assets/js/trabajadores.js') }}"></script>

@if(session('success'))
<script>
  document.addEventListener("DOMContentLoaded", function() {
    var elModalExito = document.getElementById('modalExitoGlobal');
    if (typeof bootstrap !== 'undefined' && elModalExito) {
      new bootstrap.Modal(elModalExito).show();
    }
  });
</script>
@endif
@endpush