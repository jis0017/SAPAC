@extends('layouts.app')

@section('title', 'Padrón de Usuarios - SAPAC')

@section('content')
<div class="container-fluid px-0">

  <!-- ENCABEZADO DE LA PÁGINA -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div class="d-flex align-items-center">
      <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px;">
        <i class="bi bi-people-fill fs-3" aria-hidden="true"></i>
      </div>
      <div>
        <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Gestión Municipal</span>
        <h1 class="h4 fw-bold text-body mb-0">Administración del Padrón de Ciudadanos</h1>
      </div>
    </div>
    
    <div class="d-flex flex-wrap gap-2">
      <!-- BOTÓN DE FILTRO CON DROPDOWN -->
      <div class="dropdown">
        <button class="btn btn-outline-secondary d-flex align-items-center gap-2 shadow-sm" type="button" id="dropdownFiltroUsuarios" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-funnel"></i>
          <span>Filtrar</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="dropdownFiltroUsuarios">
          <li><h6 class="dropdown-header fw-bold text-uppercase" style="font-size: 0.7rem;">Ordenar y Filtrar</h6></li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'alfabetico_az' || !request('filtro') ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : '' }}" href="{{ route('usuarios.index', ['filtro' => 'alfabetico_az']) }}">
              <i class="bi bi-sort-down"></i> Orden alfabético (A-Z)
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'alfabetico_za' ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : '' }}" href="{{ route('usuarios.index', ['filtro' => 'alfabetico_za']) }}">
              <i class="bi bi-sort-up"></i> Orden alfabético (Z-A)
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'recientes' || !request('filtro') ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : '' }}" href="{{ route('usuarios.index', ['filtro' => 'recientes']) }}">
              <i class="bi bi-sort-down"></i> Registros más recientes
            </a>
          </li>
          
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'antiguos' ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : '' }}" href="{{ route('usuarios.index', ['filtro' => 'antiguos']) }}">
              <i class="bi bi-sort-up"></i> Registros más antiguos
            </a>
          </li>
          
          <li><hr class="dropdown-divider"></li>
          
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'mas_tomas' ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : '' }}" href="{{ route('usuarios.index', ['filtro' => 'mas_tomas']) }}">
              <i class="bi bi-sort-numeric-down-alt"></i> Con más tomas de agua
            </a>
          </li>
          
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger {{ request('filtro') == 'sin_tomas' ? 'active bg-danger bg-opacity-10 fw-bold' : '' }}" href="{{ route('usuarios.index', ['filtro' => 'sin_tomas']) }}">
              <i class="bi bi-exclamation-circle"></i> Sin tomas asociadas
            </a>
          </li>
        </ul>
      </div>
      <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario">
        <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
        <span>Nuevo Usuario</span>
      </button>
    </div>
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
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Total Ciudadanos</span>
            <h4 class="fw-bold mb-0 text-body">{{ number_format(\App\Models\usuarios::count()) }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-success bg-opacity-10 p-2.5 text-success me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-droplet-fill fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Ciudadanos con Tomas</span>
            <h4 class="fw-bold mb-0 text-body">{{ number_format(\App\Models\usuarios::has('tomas')->count()) }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-info bg-opacity-10 p-2.5 text-info me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-shield-check fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Estatus Padrón</span>
            <h4 class="fw-bold mb-0 text-body">Activo / Verificado</h4>
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

  <!-- TABLA PRINCIPAL DE USUARIOS -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-uppercase small text-muted">
          <tr>
            <th class="ps-3" style="width: 130px;">No. Ciudadano</th>
            <th>Nombre / Razón Social</th>
            <th>Teléfono</th>
            <th>R.F.C.</th>
            <th>Tomas Asociadas</th>
            <th class="text-end pe-3" style="width: 140px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($usuarios as $usuario)
            <tr>
              <td class="ps-3">
                <span class="badge bg-secondary bg-opacity-10 text-body font-monospace px-2 py-1 fs-7 border">
                  {{ $usuario->no_ciudadano }}
                </span>
              </td>
              <td>
                <div class="fw-bold text-body">{{ $usuario->razon_social }}</div>
                @if(!empty($usuario->apellidos))
                  <small class="text-muted">{{ $usuario->apellidos }}</small>
                @endif
              </td>
              <td>
                <div class="text-body font-monospace">
                  {{ $usuario->telefono ?? 'S/N' }}
                </div>
              </td>
              <td>
                <span class="badge bg-light text-body border font-monospace">
                  {{ $usuario->rfc ?? 'RFC Sin Registrar' }}
                </span>
              </td>
              <td>
                <button type="button" class="btn btn-sm btn-outline-primary border-primary border-opacity-25 px-3 py-1 rounded-pill shadow-sm d-inline-flex align-items-center gap-1"
                        onclick="verTomasUsuario('{{ $usuario->id_usuario }}', '{{ addslashes($usuario->razon_social) }} {{ addslashes($usuario->apellidos ?? '') }}')">
                  <i class="bi bi-droplet-fill text-primary"></i> 
                  <span class="fw-semibold">{{ $usuario->tomas->count() }} Toma(s)</span>
                </button>
              </td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <button onclick="abrirModalEditar('{{ $usuario->id_usuario }}', '{{$usuario->no_ciudadano }}', '{{ addslashes($usuario->razon_social) }}', '{{ addslashes($usuario->apellidos ?? '') }}', '{{ $usuario->rfc ?? '' }}', '{{ $usuario->telefono ?? '' }}')" 
                          class="btn btn-outline-secondary" 
                          title="Editar Usuario">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-outline-danger" 
                          title="Dar de baja" 
                          onclick="confirmarEliminar('{{ $usuario->id_usuario }}', '{{ $usuario->no_ciudadano }}', '{{ addslashes($usuario->razon_social) }}')">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">No se encontraron ciudadanos registrados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- PAGINACIÓN -->
    <div class="card-footer bg-body border-0 py-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
      <small class="text-muted fw-semibold">
        Mostrando <span class="text-primary">{{ $usuarios->firstItem() ?? 0 }}</span> a <span class="text-primary">{{ $usuarios->lastItem() ?? 0 }}</span> de {{$usuarios->total() }} ciudadanos
      </small>
      
      <nav aria-label="Navegación de páginas">
        <ul class="pagination pagination-sm mb-0 shadow-sm" style="border-radius: 0.5rem; overflow: hidden;">
            @if ($usuarios->onFirstPage())
                <li class="page-item disabled"><span class="page-link border-0 text-muted bg-light px-3">&laquo; Ant</span></li>
            @else
                <li class="page-item"><a class="page-link border-0 text-primary fw-bold px-3" href="{{ $usuarios->previousPageUrl() }}">&laquo; Ant</a></li>
            @endif

            @if($usuarios->hasPages())
                @foreach ($usuarios->links()->elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled"><span class="page-link border-0">{{ $element }}</span></li>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page =>$url)
                            @if ($page ==$usuarios->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link border-0 bg-primary text-white fw-bold px-3">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link border-0 text-secondary bg-light px-3" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endif

            @if ($usuarios->hasMorePages())
                <li class="page-item"><a class="page-link border-0 text-primary fw-bold px-3" href="{{ $usuarios->nextPageUrl() }}">Sig &raquo;</a></li>
            @else
                <li class="page-item disabled"><span class="page-link border-0 text-muted bg-light px-3">Sig &raquo;</span></li>
            @endif
        </ul>
      </nav>
    </div>
  </div>
</div>

<!-- MODAL: CREAR CIUDADANO (Con validaciones de letras y números) -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg bg-body">
      <div class="modal-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
            <i class="bi bi-person-fill"></i>
          </div>
          <h5 class="modal-title fw-bold mb-0 text-body">Creación de Ciudadano</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form action="{{ route('usuarios.store') }}" method="POST">
        @csrf
        <div class="modal-body p-4 row g-3">
          <div class="col-12">
            <label for="razon_social" class="form-label small fw-semibold text-body mb-1">Nombre(s) o Razón Social <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="razon_social" name="razon_social" placeholder="EJ. JUAN LOPEZ SANCHEZ" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" title="Solo se permiten letras y espacios" required>
          </div>
          <div class="col-12">
            <label for="apellidos" class="form-label small fw-semibold text-body mb-1">Apellidos (Paterno y Materno)</label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="apellidos" name="apellidos" placeholder="EJ. LOPEZ SANCHEZ" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]*" title="Solo se permiten letras y espacios">
          </div>
          <div class="col-12 col-md-6">
            <label for="telefono" class="form-label small fw-semibold text-body mb-1">Teléfono de Contacto</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
              <input type="text" class="form-control" id="telefono" name="telefono" placeholder="Ej. 3781002233" pattern="[0-9\-\+\s\(\)]*" maxlength="10" title="Solo se permiten números y símbolos telefónicos">
            </div>
          </div>
          <div class="col-12 col-md-6">
            <label for="rfc" class="form-label small fw-semibold text-body mb-1">R.F.C.</label>
            <input type="text" class="form-control form-control-sm text-uppercase font-monospace" id="rfc" name="rfc" placeholder="XAXX010101000" maxlength="13" pattern="[A-Za-z0-9]{12,13}" title="Ingrese un RFC válido de 12 o 13 caracteres alfanuméricos">
          </div>
        </div>
        <div class="modal-footer border-top py-2.5 px-4 bg-light bg-opacity-25 d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm">Guardar Ciudadano</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: EDITAR CIUDADANO -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg bg-body">
      <div class="modal-header border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold mb-0 text-body">
          <i class="bi bi-pencil-square me-2 text-primary"></i>Actualizar Información del Ciudadano
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formEditarUsuario" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body p-4 row g-3">
          <div class="col-md-4">
            <label for="edit_folio" class="form-label small fw-semibold text-muted mb-1">Folio Ciudadano</label>
            <input type="text" class="form-control form-control-sm bg-light fw-bold text-secondary font-monospace" id="edit_folio" readonly>
          </div>
          <div class="col-md-8">
            <label for="edit_rfc" class="form-label small fw-semibold text-muted mb-1">RFC</label>
            <input type="text" class="form-control form-control-sm text-uppercase font-monospace" id="edit_rfc" name="rfc" placeholder="XAXX010101000" maxlength="13">
          </div>
          <div class="col-md-6">
            <label for="edit_razon_social" class="form-label small fw-semibold text-body mb-1">Nombre / Razón Social <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="edit_razon_social" name="razon_social" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" title="Solo letras y espacios" required>
          </div>
          <div class="col-md-6">
            <label for="edit_apellidos" class="form-label small fw-semibold text-body mb-1">Apellidos</label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="edit_apellidos" name="apellidos" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]*" title="Solo letras y espacios">
          </div>
          <div class="col-md-6">
            <label for="edit_telefono" class="form-label small fw-semibold text-body mb-1">Teléfono</label>
            <input type="text" class="form-control form-control-sm" id="edit_telefono" name="telefono" placeholder="Ej. 3781002233">
          </div>
        </div>
        <div class="modal-footer border-top py-2.5 px-4 bg-light bg-opacity-25 d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm">Actualizar Ciudadano</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL ELEGANTE: CONFIRMAR ELIMINACIÓN -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
      <div class="modal-body py-4">
        <div class="mb-3">
          <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 4px solid rgba(220, 53, 69, 0.15);">
            <i class="bi bi-trash text-danger fs-1"></i>
          </div>
        </div>
        <h4 class="fw-bold mb-2 text-dark">¿Eliminar Ciudadano?</h4>
        <p class="text-muted small mb-4 px-2">Está a punto de dar de eliminar al ciudadano <strong id="eliminar_nombre" class="text-dark"></strong> (Folio <span id="eliminar_no_ciudadano" class="text-danger fw-bold"></span>). Esta acción no se puede deshacer.</p>
        
        <form id="formEliminarUsuario" method="POST">
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

<!-- MODAL: VER TOMAS ASOCIADAS -->
<div class="modal fade" id="modalTomasUsuario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg bg-body">
      <div class="modal-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
            <i class="bi bi-droplet-half"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold mb-0 text-body">Tomas de Agua Asociadas</h5>
            <small class="text-muted" id="modalTitularTomas">Titular: --</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body p-4">
        <div class="table-responsive border rounded-3">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
              <tr>
                <th class="ps-3">ID Toma</th>
                <th>Domicilio / Ubicación</th>
                <th>Esquema / Medidor</th>
                <th class="text-end pe-3">Acción</th>
              </tr>
            </thead>
            <tbody id="tbodyTomasUsuario" class="small">
              <!-- AJAX -->
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer border-top py-2 px-4 bg-light">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DE NOTIFICACIÓN DE ÉXITO (Palomita Verde Global) -->
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
@endif
@endsection

@push('scripts')
<!-- Aquí mandamos a llamar el archivo JS externo desde la carpeta public/assets/js/ -->
<script src="{{ asset('assets/js/usuarios.js') }}"></script>
@endpush