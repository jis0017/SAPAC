@extends('layouts.app')

@section('title', 'Tomas de Agua - SAPAC')

@section('content')
<div class="container-fluid px-0">

  <!-- ENCABEZADO -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div class="d-flex align-items-center">
      <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px;">
        <i class="bi bi-droplet-half fs-3" aria-hidden="true"></i>
      </div>
      <div>
        <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Gestión Municipal</span>
        <h1 class="h4 fw-bold text-body mb-0">Padrón de Tomas de Agua</h1>
      </div>
    </div>
    
    
    <div class="d-flex flex-wrap gap-2">
      <!-- BOTÓN DE FILTRO CON DROPDOWN -->
      <div class="dropdown">
        <button class="btn btn-outline-secondary d-flex align-items-center gap-2 shadow-sm" type="button" id="dropdownFiltroTomas" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-funnel"></i>
          <span>Filtrar</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="dropdownFiltroTomas">
          <li><h6 class="dropdown-header fw-bold text-uppercase" style="font-size: 0.7rem;">Ordenar y Filtrar</h6></li>
          
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'recientes' || !request('filtro') ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : '' }}" href="{{ route('tomas.index', ['filtro' => 'recientes', 'buscar' => request('buscar'), 'id_exacto' => request('id_exacto')]) }}">
              <i class="bi bi-sort-down"></i> Tomas más recientes
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'antiguos' ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : '' }}" href="{{ route('tomas.index', ['filtro' => 'antiguos', 'buscar' => request('buscar'), 'id_exacto' => request('id_exacto')]) }}">
              <i class="bi bi-sort-up"></i> Tomas más antiguas
            </a>
          </li>
          
          <li><hr class="dropdown-divider"></li>
          
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'medido' ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : '' }}" href="{{ route('tomas.index', ['filtro' => 'medido', 'buscar' => request('buscar'), 'id_exacto' => request('id_exacto')]) }}">
              <i class="bi bi-speedometer2"></i> Solo Servicio Medido
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'cuota_fija' ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : '' }}" href="{{ route('tomas.index', ['filtro' => 'cuota_fija', 'buscar' => request('buscar'), 'id_exacto' => request('id_exacto')]) }}">
              <i class="bi bi-house-door"></i> Solo Cuota Fija
            </a>
          </li>

          <!-- NUEVOS FILTROS DE SOLVENCIA AQUÍ -->
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'al_corriente' ? 'active bg-success bg-opacity-10 text-success fw-bold' : '' }}" href="{{ route('tomas.index', ['filtro' => 'al_corriente', 'buscar' => request('buscar'), 'id_exacto' => request('id_exacto')]) }}">
              <i class="bi bi-wallet2"></i> Tomas Al Corriente
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ request('filtro') == 'con_adeudo' ? 'active bg-danger bg-opacity-10 text-danger fw-bold' : '' }}" href="{{ route('tomas.index', ['filtro' => 'con_adeudo', 'buscar' => request('buscar'), 'id_exacto' => request('id_exacto')]) }}">
              <i class="bi bi-exclamation-octagon"></i> Tomas con Adeudo
            </a>
          </li>
          <!-- FIN NUEVOS FILTROS -->
          
          <li><hr class="dropdown-divider"></li>

          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-success {{ request('filtro') == 'activos' ? 'active bg-success bg-opacity-10 fw-bold' : '' }}" href="{{ route('tomas.index', ['filtro' => 'activos', 'buscar' => request('buscar'), 'id_exacto' => request('id_exacto')]) }}">
              <i class="bi bi-check-circle"></i> Tomas Activas
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger {{ request('filtro') == 'baja' ? 'active bg-danger bg-opacity-10 fw-bold' : '' }}" href="{{ route('tomas.index', ['filtro' => 'baja', 'buscar' => request('buscar'), 'id_exacto' => request('id_exacto')]) }}">
              <i class="bi bi-x-circle"></i> Tomas dadas de Baja
            </a>
          </li>
        </ul>
      </div>

      <!-- BOTÓN DE NUEVA TOMA -->
      <button class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaToma">
        <i class="bi bi-plus-circle" aria-hidden="true"></i>
        <span>Nueva Toma</span>
      </button>
    </div>

  </div>

  <!-- TARJETAS DE MÉTRICAS (KPIs) -->
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-primary bg-opacity-10 p-2.5 text-primary me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-water fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Padrón de Tomas</span>
            <h4 class="fw-bold mb-0 text-body">{{ number_format(\App\Models\TomasAgua::count()) }}</h4>
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
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Servicio Medido</span>
            <h4 class="fw-bold mb-0 text-body">{{ number_format(\App\Models\TomasAgua::where('calculo_fijo', 0)->count()) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-warning bg-opacity-10 p-2.5 text-warning me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-house-door fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Cuota Fija</span>
            <h4 class="fw-bold mb-0 text-body">{{ number_format(\App\Models\TomasAgua::where('calculo_fijo', 1)->count()) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="d-flex align-items-center">
          <div class="rounded-3 bg-success bg-opacity-10 p-2.5 text-success me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-check-circle-fill fs-4"></i>
          </div>
          <div class="min-w-0">
            <span class="text-muted d-block text-truncate" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700;">Tomas Activas</span>
            <h4 class="fw-bold mb-0 text-body">{{ number_format(\App\Models\TomasAgua::where('estatus', 'ACTIVO')->count()) }}</h4>
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

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4 border-0" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Error de validación:</strong> {{ $errors->first() }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  @endif

  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-uppercase small text-muted">
          <tr>
            <th class="ps-3" style="width: 110px;">ID Toma</th>
            <th>Titular / Contrato</th>
            <th>Ubicación y Domicilio</th>
            <th>No Medidor / Cuota Fija</th>
            <th>Tipo de Predio</th>
            <th>Estatus</th>
            <th class="text-end pe-3" style="width: 210px;">Acciones</th>
          </tr>
        </thead>
        <tbody class="text-body">
          @forelse($tomas as $toma)
            <tr>
              <td class="ps-3">
                <span class="badge bg-secondary bg-opacity-10 text-body font-monospace px-2 py-1 fs-7 border">
                  {{ str_pad($toma->id_toma, 4, '0', STR_PAD_LEFT) }}  
                </span>
              </td>
              <td>
                <div class="fw-bold text-body">
                  @if($toma->usuario)
                    {{ $toma->usuario->razon_social ?? '' }}
                  @else
                    <span class="text-danger">TITULAR NO ASIGNADO</span>
                  @endif
                </div>
                <small class="text-muted font-monospace">No. Ciudadano: {{ $toma->usuario->no_ciudadano ?? 'S/N' }}</small>
              </td>
              <td>
                <div class="text-body fw-semibold">
                  {{ $toma->calle }} #{{ $toma->numero_exterior ?? 'S/N' }}
                  @if(!empty($toma->numero_interior))
                    <span class="text-muted">(Int. {{ $toma->numero_interior }})</span>
                  @endif
                </div>
                <small class="text-muted">
                  <i class="bi bi-geo-alt me-1 text-primary"></i>
                  {{ $toma->localidad->nombre_localidad ?? 'Cabecera Municipal' }}, {{ $toma->municipio ?? 'ACATIC' }}
                </small>
              </td>
              <td>
                @if($toma->calculo_fijo == 1 )
                  <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">Cuota Fija</span>
                @else
                  <span class="badge bg-info bg-opacity-10 text-info border px-2 py-1">Medido</span>
                  <small class="d-block font-monospace text-primary fw-semibold">{{ $toma->no_medidor }}</small>
                @endif
              </td>
              <td>
                <span class="badge bg-light text-body border">
                  {{ $toma->tipoPredio->nombre_predio ?? 'GENERAL' }}
                </span>
              </td>
              <td>
                <span class="badge bg-{{ strtoupper($toma->estatus) == 'ACTIVO' ? 'success' : 'danger' }} bg-opacity-10 text-{{ strtoupper($toma->estatus) == 'ACTIVO' ? 'success' : 'danger' }} border px-2 py-1">
                  {{ $toma->estatus }}
                </span>
              </td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-1 px-2" title="Historial y Estado de Cuenta" onclick="verHistoricoPagos('{{ $toma->id_toma }}', '{{ addslashes($toma->usuario->razon_social ?? '') }} {{ addslashes($toma->usuario->apellidos ?? '') }}', '{{ addslashes($toma->calle . ' #' . ($toma->numero_exterior ?? 'S/N')) }}', '{{ $toma->no_medidor ?? 'Cuota Fija' }}', '{{ $toma->tipoPredio->nombre_predio ?? 'DOMÉSTICO' }}')">
                    <i class="bi bi-clock-history"></i>
                    <span>Historial</span>
                  </button>
                  <button type="button" class="btn btn-outline-secondary" title="Editar Toma" onclick="abrirModalEditar('{{ $toma->id_toma }}', '{{ $toma->usuario_id }}', '{{ addslashes($toma->usuario->razon_social ?? '') }} {{ addslashes($toma->usuario->apellidos ?? '') }}', '{{ $toma->pozo_id }}', '{{ $toma->id_localidad }}', '{{ addslashes($toma->calle) }}', '{{ $toma->numero_exterior }}', '{{ $toma->numero_interior }}', '{{ $toma->id_tipo_predio }}', '{{ $toma->clave_catastral }}', '{{ $toma->calculo_fijo }}', '{{$toma->no_medidor }}', '{{ addslashes($toma->municipio ?? 'ACATIC') }}', '{{ $toma->estatus }}')">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button type="button" class="btn btn-outline-danger" title="Eliminar Toma" onclick="abrirModalEliminar('{{ $toma->id_toma }}', '{{ addslashes($toma->usuario->razon_social ?? '') }}')">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">No hay tomas registradas en la base de datos.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer bg-body border-0 py-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
      <small class="text-muted fw-semibold">
        Mostrando <span class="text-primary">{{ $tomas->firstItem() ?? 0 }}</span> a <span class="text-primary">{{ $tomas->lastItem() ?? 0 }}</span> de {{$tomas->total() }} tomas
      </small>
      
      <nav aria-label="Navegación de páginas">
        <ul class="pagination pagination-sm mb-0 shadow-sm" style="border-radius: 0.5rem; overflow: hidden;">
            @if ($tomas->onFirstPage())
                <li class="page-item disabled"><span class="page-link border-0 text-muted bg-light px-3">&laquo; Ant</span></li>
            @else
                <li class="page-item"><a class="page-link border-0 text-primary fw-bold px-3" href="{{ $tomas->previousPageUrl() }}">&laquo; Ant</a></li>
            @endif

            @if($tomas->hasPages())
                @foreach ($tomas->links()->elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled"><span class="page-link border-0">{{ $element }}</span></li>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page =>$url)
                            @if ($page ==$tomas->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link border-0 bg-primary text-white fw-bold px-3">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link border-0 text-secondary bg-light px-3" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endif

            @if ($tomas->hasMorePages())
                <li class="page-item"><a class="page-link border-0 text-primary fw-bold px-3" href="{{ $tomas->nextPageUrl() }}">Sig &raquo;</a></li>
            @else
                <li class="page-item disabled"><span class="page-link border-0 text-muted bg-light px-3">Sig &raquo;</span></li>
            @endif
        </ul>
      </nav>
    </div>
  </div> 
</div> 

<!-- MODAL NUEVA / EDITAR TOMA (Con validaciones estrictas por tipo de dato) -->
<div class="modal fade" id="modalNuevaToma" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="max-width: 800px; margin: auto;">
      
      <div class="modal-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="text-primary d-flex align-items-center justify-content-center me-1">
            <i class="bi bi-water fs-4"></i>
          </div>
          <h5 class="modal-title fw-bold mb-0 text-body" id="tituloModalToma">Alta de Contrato de Toma de Agua</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <form id="formTomaAgua" action="{{ url('/tomas') }}" method="POST">
        @csrf
        <div id="methodFieldContainer"></div>
        <div class="modal-body p-4 row g-3">
          
          <div class="col-12 position-relative">
            <label class="form-label small fw-semibold text-body mb-1">
              Usuario Titular / Ciudadano <span class="text-danger">*</span> 
              <small class="text-muted fw-normal">(Escriba el nombre o apellidos para buscar)</small>
            </label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" class="form-control border-start-0 ps-0 text-uppercase" id="buscadorUsuarioModal" placeholder="Escriba el nombre o apellidos..." autocomplete="off">
              <input type="hidden" id="usuario_id" name="usuario_id" required>
            </div>
            <div id="listaResultadosUsuariosModal" class="list-group position-absolute shadow-lg border mt-1 w-100 d-none" style="max-height: 220px; overflow-y: auto; z-index: 1080;"></div>
            <div id="badgeTitularSeleccionadoModal" class="mt-1.5 small text-success fw-semibold d-none">
              <i class="bi bi-check-circle-fill me-1"></i> Titular vinculado: <span id="textoTitularElegidoModal" class="text-dark"></span>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <label for="pozo_id" class="form-label small fw-semibold text-body mb-1">Pozo de Abastecimiento</label>
            <select class="form-select form-select-sm" id="pozo_id" name="pozo_id">
              <option value="">Seleccionar Pozo...</option>
              @foreach(\App\Models\Pozos::where('estado', 'ACTIVO')->get() as $pozo)
                <option value="{{ $pozo->id_pozo }}">{{ $pozo->nombre_pozo }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label for="id_localidad" class="form-label small fw-semibold text-body mb-1">Localidad <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="id_localidad" name="id_localidad" required>
              <option value="">Seleccionar Localidad...</option>
              @foreach(\App\Models\Localidades::all() as $localidad)
                <option value="{{ $localidad->id_localidad }}">{{ $localidad->nombre_localidad }}</option>
              @endforeach
            </select>
          </div>

          <!-- CALLE: Letras, números, puntos, diagonales y espacios -->
          <div class="col-12 col-md-6">
            <label for="calle" class="form-label small fw-semibold text-body mb-1">Calle / Vialidad <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="calle" name="calle" required placeholder="Nombre de la calle" autocomplete="off">
          </div>

          <!-- NÚMERO EXTERIOR: Alfanumérico y espacios (ej. 101, 12-A, S/N) -->
          <div class="col-6 col-md-3">
            <label for="numero_exterior" class="form-label small fw-semibold text-body mb-1">No. Exterior <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="numero_exterior" name="numero_exterior" required value="S/N" pattern="[A-Za-z0-9\/\-\#\.\s]+" title="Ingrese número exterior válido (ej. 101, 12-A, S/N)" autocomplete="off">
          </div>

          <!-- NÚMERO INTERIOR: Opcional alfanumérico y espacios -->
          <div class="col-6 col-md-3">
            <label for="numero_interior" class="form-label small fw-semibold text-body mb-1">No. Interior</label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="numero_interior" name="numero_interior" placeholder="Opcional" pattern="[A-Za-z0-9\/\-\#\.\s]*" title="Ingrese número interior válido" autocomplete="off">
          </div>
          

          <!-- MUNICIPIO: Solo letras y espacios -->
          <div class="col-12 col-md-6">
            <label for="municipio" class="form-label small fw-semibold text-body mb-1">Municipio</label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="municipio" name="municipio" value="ACATIC" placeholder="ACATIC" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" title="Solo se permiten letras y espacios">
          </div>

          <div class="col-12 col-md-6">
            <label for="id_tipo_predio" class="form-label small fw-semibold text-body mb-1">Tipo de Predio <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="id_tipo_predio" name="id_tipo_predio" required>
              <option value="">Seleccionar Tipo de Predio...</option>
              @foreach (\App\Models\TipoPredio::all() as $tipoPredio)
                <option value="{{ $tipoPredio->id_tipo_predio }}">{{ $tipoPredio->nombre_predio }}</option>
              @endforeach
            </select>
          </div>

          <!-- CLAVE CATASTRAL: Alfanumérica -->
          <div class="col-12 col-md-6">
            <label for="clave_catastral" class="form-label small fw-semibold text-body mb-1">Clave Catastral</label>
            <input type="text" class="form-control form-control-sm font-monospace text-uppercase" id="clave_catastral" name="clave_catastral" placeholder="Opcional" maxlength="50" pattern="[A-Za-z0-9\-]*" title="Solo caracteres alfanuméricos y guiones">
          </div>

          <div class="col-12 col-md-6">
            <label for="estatus" class="form-label small fw-semibold text-body mb-1">Estatus del Contrato <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="estatus" name="estatus" required>
              <option value="ACTIVO" selected>ACTIVO</option>
              <option value="BAJA">BAJA</option>
            </select>
          </div>

          <div class="col-12 col-md-6">
          <label for="calculo_fijo" class="form-label small fw-semibold text-body mb-1">Esquema de Cobro <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" id="calculo_fijo" name="calculo_fijo" onchange="toggleMedidorModal(this.value)" required>
            <!-- AQUI ESTABA EL ERROR: Cambiamos los valores -->
            <option value="1" selected>Cuota Fija (Sin Medición)</option> 
            <option value="0">Servicio Medido (Requiere Medidor)</option>
          </select>
          </div>

          <!-- NÚMERO DE MEDIDOR: Requerido solo si esquema es medido, alfanumérico -->
          <div class="col-12 col-md-6" id="divMedidorModal" style="display: none;">
            <label for="no_medidor" class="form-label small fw-semibold text-body mb-1">No. de Medidor <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm font-monospace text-uppercase" id="no_medidor" name="no_medidor" placeholder="Número de aparato" pattern="[A-Za-z0-9\-]+" title="Ingrese el número de serie o medidor">
          </div>

        </div>

        <div class="modal-footer border-top py-2.5 px-4 bg-light bg-opacity-25 d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm" id="btnGuardarModal">Guardar Contrato de Toma</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ELIMINAR -->
<div class="modal fade" id="modalEliminarToma" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
      <div class="modal-body py-4">
        <div class="mb-3">
          <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 4px solid rgba(220, 53, 69, 0.15);">
            <i class="bi bi-trash text-danger fs-1"></i>
          </div>
        </div>
        <h4 class="fw-bold mb-2 text-dark">¿Eliminar Toma?</h4>
        <p class="text-muted small mb-4 px-2">Está a punto de eliminar el contrato de la toma del titular <strong id="nombreTitularEliminar" class="text-dark"></strong>. Esta acción no se puede deshacer.</p>
        
        <form id="formEliminarToma" method="POST">
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

<!-- MODAL HISTORIAL -->
<div class="modal fade" id="modalHistoricoPagos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-bottom py-3 px-4">
        <div>
          <h5 class="modal-title fw-bold text-body mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Estado de Cuenta e Historial</h5>
          <span class="text-muted small" id="subtituloTomaModal">Toma #</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="p-3 bg-body rounded-3 border h-100">
              <span class="text-muted small d-block mb-1">Titular de la Conexión:</span>
              <strong class="d-block text-body fs-6" id="modTitular">--</strong>
              <small class="text-muted" id="modDireccion">--</small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 bg-body rounded-3 border h-100">
              <span class="text-muted small d-block mb-1">Régimen y Aparato Medidor:</span>
              <span class="badge bg-primary bg-opacity-10 text-primary me-1" id="modTipoPropiedad">--</span>
              <span class="badge bg-secondary bg-opacity-10 text-secondary" id="modMedidor">--</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 bg-body rounded-3 border h-100 d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small d-block mb-1">Solvencia de la Toma:</span>
                <h5 class="fw-bold mb-0 text-secondary" id="modTextoSolvencia">VERIFICANDO...</h5>
              </div>
              <i class="bi bi-check-circle-fill text-success fs-2 d-none" id="iconSolvenciaOk"></i>
              <i class="bi bi-exclamation-triangle-fill text-danger fs-2 d-none" id="iconSolvenciaAdeudo"></i>
            </div>
          </div>
        </div>

        <div class="table-responsive border rounded-3 mb-3">
          <table class="table table-hover align-middle mb-0 text-center">
            <thead class="table-light small text-uppercase">
              <tr>
                <th>Periodo Fiscal</th>
                <th>Concepto Base</th>
                <th>Agua</th>
                <th>Saneamiento</th>
                <th>Infraestructura</th>
                <th>Bonificación</th>
                <th>Total Recibo</th>
                <th>Fecha de Caja</th>
                <th>Estatus</th>
              </tr>
            </thead>
            <tbody id="tbodyHistorialPagos" class="text-body small"></tbody>
          </table>
        </div>

        <div class="text-center d-none" id="contenedorBtnVerTodo">
          <button type="button" class="btn btn-sm btn-outline-primary px-4 shadow-sm" id="btnAlternarHistorial" onclick="alternarVistaHistorial()">
            <i class="bi bi-arrow-down-circle me-1"></i> Ver historial completo
          </button>
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
<script src="{{ asset('assets/js/tomas.js') }}"></script>
@endpush