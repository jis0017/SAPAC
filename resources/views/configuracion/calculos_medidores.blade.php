@extends('layouts.app')

@section('content')


<div class="container-fluid py-4 px-2">

    <!-- ENCABEZADO -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex justify-content-center align-items-center shadow-sm flex-shrink-0" style="width: 40px; height: 40px;">
                <i class="bi bi-speedometer2 fs-5"></i>
            </div>
            <div>
                <span class="text-uppercase fw-bold text-muted d-block" style="font-size: 0.65rem; letter-spacing: 0.05em;">GESTIÓN MUNICIPAL</span>
                <h5 class="fw-bold mb-0 text-dark">Cálculos Medidores</h5>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <select class="form-select shadow-sm border-0 px-2 py-1 fw-semibold text-dark rounded-3" aria-label="Seleccionar Año" onchange="window.location.href='?anio='+this.value" style="width: auto; font-size: 0.8rem;">
                @for($i = 2025; $i <= date('Y') + 10; $i++)
                    <option value="{{ $i }}" {{ request('anio', date('Y')) == $i ? 'selected' : '' }}>Año {{ $i }}</option>
                @endfor
            </select>
            
            <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm rounded-3 px-2 py-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaLecturaGlobal">
                <i class="bi bi-plus-circle"></i>
                <span>Capturar</span>
            </button>
        </div>
    </div>

    <!-- ALERTA DE ÉXITO -->
    @if(session('success'))
    <div class="modal fade" id="modalExitoAccion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 350px;">
            <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
                <div class="modal-body py-3">
                    <div class="mb-2">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border: 3px solid rgba(25, 135, 84, 0.15);">
                            <i class="bi bi-check-lg text-success fs-2 fw-bold"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark">¡Exitoso!</h5>
                    <p class="text-muted small mb-3">{{ session('success') }}</p>
                    <button type="button" class="btn btn-sm btn-primary px-4 rounded-pill fw-semibold shadow-sm" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            
            <!-- CONTENEDOR DE LA TABLA -->
            <div class="w-100" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                <table class="table table-striped align-middle mb-0 text-center tabla-medidores">
                    <thead class="bg-white sticky-top text-uppercase text-muted border-bottom" style="z-index: 2;">
                        <tr>
                            <th rowspan="2" class="col-titular text-start ps-2 border-0">TITULAR</th>
                            <th rowspan="2" class="col-lect-ant border-0 bg-secondary bg-opacity-10">L.<br>ANT</th>
                            <th colspan="12" class="border-bottom text-center">MESES</th>
                            <th rowspan="2" class="col-m3 border-0 bg-warning bg-opacity-10">M3<br>MES</th>
                            <th rowspan="2" class="col-exe text-danger border-0 bg-danger bg-opacity-10">M3<br>EX</th>
                            <th rowspan="2" class="col-med border-0">MED.</th>
                            <th rowspan="2" class="col-acciones border-0 text-end pe-2">ACC.</th>
                        </tr>
                        <tr>
                            <th class="border-0 col-mes">EN</th><th class="border-0 col-mes">FE</th><th class="border-0 col-mes">MA</th>
                            <th class="border-0 col-mes">AB</th><th class="border-0 col-mes">MY</th><th class="border-0 col-mes">JU</th>
                            <th class="border-0 col-mes">JL</th><th class="border-0 col-mes">AG</th><th class="border-0 col-mes">SE</th>
                            <th class="border-0 col-mes">OC</th><th class="border-0 col-mes">NO</th><th class="border-0 col-mes">DI</th>
                        </tr>
                    </thead>
                    <tbody class="text-body border-top-0">
                        @forelse($tomas as $index => $toma)
                            @php
                                $limite = $toma->tarifaConfig->m3_base ?? 12;
                                $ultima = collect($toma->calculos)->sortByDesc('mes')->first();
                                $acumulado = $ultima ? $ultima->consumo_acumulado : 0;
                                $excedente = $acumulado > $limite ? ($acumulado - $limite) : 0;
                            @endphp

                            <tr>
                                <td class="col-titular text-start ps-2">
                                    <div class="nombre-titular text-truncate">{{ $toma->usuario->razon_social ?? 'USUARIO' }}</div>
                                    
                                    <div class="datos-titular">
                                        <i class="bi bi-geo-alt-fill text-primary"></i> 
                                        {{ $toma->calle ?? '' }} {{ $toma->numero_exterior ?? '' }} {{ $toma->numero_interior ? 'Int. '.$toma->numero_interior : '' }}
                                    </div>
                                </td>

                                <td class="col-lect-ant fw-bold text-secondary bg-secondary bg-opacity-10">
                                    {{ $toma->lectura_base ?? 0 }} 
                                </td>

                                @for($mes = 1; $mes <= 12; $mes++)
                                    @php 
                                        $lecturaMes = collect($toma->calculos)->where('mes', $mes)->first(); 
                                    @endphp
                                    <td class="col-mes {{ $lecturaMes && $lecturaMes->es_excedente ? 'text-danger fw-bold' : 'text-muted' }}">
                                        {{ $lecturaMes ? $lecturaMes->lectura : '-' }}
                                    </td>
                                @endfor

                                <td class="col-m3 fw-bold text-dark bg-warning bg-opacity-10">
                                    {{ $ultima ? $ultima->consumo_mes : 0 }}
                                </td>

                                <td class="col-exe text-danger fw-bold bg-danger bg-opacity-10">
                                    {{ $excedente }}
                                </td>

                                <td class="col-med">
                                    <span class="text-muted font-monospace" style="font-size: 0.55rem;">
                                        {{ $toma->no_medidor ?? 'S/N' }}
                                    </span>
                                </td>

                                <td class="col-acciones text-end pe-2">
                                    <div class="d-flex justify-content-end gap-1 flex-nowrap">
                                        
                                        <!-- Botón de Captura -->
                                        <button class="btn btn-sm btn-outline-primary py-0 px-1" title="Capturar Lectura" data-bs-toggle="modal" data-bs-target="#modalLectura{{ $toma->id_toma }}">
                                            <i class="bi bi-pencil" style="font-size: 0.65rem;"></i>
                                        </button>

                                        @if($excedente > 0)
                                            <!-- Botón 1: Solo ver/imprimir PDF (Seguro, no altera BD) -->
                                            <a href="{{ route('calculos_medidor.GenerarPdf', ['toma_id' => $toma->id_toma, 'anio' => request('anio', date('Y'))]) }}" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-1 shadow-sm" title="Ver PDF">
                                                <i class="bi bi-file-earmark-pdf-fill" style="font-size: 0.65rem;"></i>
                                            </a>
                                            
                                            <!-- Botón 2: Generar Deuda (Abre advertencia y manda a BD) -->
                                            <form action="{{ route('calculos_medidor.facturar', ['toma_id' => $toma->id_toma, 'anio' => request('anio', date('Y'))]) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('¿Está seguro de generar el cobro para esta toma?\n\nEsto enviará la deuda al sistema de caja y reiniciará los metros excedentes a 0.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success py-0 px-1 shadow-sm" title="Facturar Excedente">
                                                    <i class="bi bi-currency-dollar" style="font-size: 0.65rem;"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="18" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No hay tomas de agua con servicio medido.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINACIÓN -->
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

<!-- ========================================================================================= -->
<!-- MODALES -->
<!-- ========================================================================================= -->

<!-- MODAL GLOBAL CON BUSCADOR EN VIVO -->
<div class="modal fade" id="modalNuevaLecturaGlobal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content border-0 shadow-lg text-start rounded-4">
            <div class="modal-header border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold text-dark" style="font-size: 0.9rem;">
                    <i class="bi bi-plus-circle text-primary me-1"></i>Capturar Nueva Lectura
                </h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="formNuevaLecturaGlobal" method="POST" data-action-base="{{ url('/medidores/guardar-lectura') }}" onsubmit="return validarEnvioModalGlobal()">
                @csrf
                <div class="modal-body p-3">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Buscar y Seleccionar Titular / Toma <span class="text-danger">*</span></label>
                        <input type="text" id="buscadorModal" class="form-control form-control-sm mb-1 border-primary border-opacity-25" placeholder="Escriba nombre o ID..." onkeyup="filtrarTomas()">
                        
                        <select id="selectTomaGlobal" class="form-select form-select-sm border-secondary border-opacity-25 shadow-sm" size="4" required onchange="actualizarRutaFormulario()">
                            <option value="" class="d-none">Seleccionar una toma...</option>
                            @if(isset($todasTomasMedidas))
                                @foreach($todasTomasMedidas as $t)
                                    <option value="{{ $t->id_toma }}">ID {{ $t->id_toma }} - {{ $t->usuario->razon_social ?? 'S/N' }}</option>
                                @endforeach
                            @endif
                        </select>
                        <input type="hidden" id="toma_id_hidden" required>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted mb-1">Año <span class="text-danger">*</span></label>
                            <input type="number" name="anio" class="form-control form-control-sm bg-light" value="{{ request('anio', date('Y')) }}" required readonly>
                        </div>
                        
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted mb-1">Mes <span class="text-danger">*</span></label>
                            <select name="mes" class="form-select form-select-sm" required>
                                <option value="">Mes...</option>
                                @php $nombresMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']; @endphp
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">{{ $nombresMeses[$m-1] }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="form-label small fw-semibold text-primary mb-1">Lectura en Medidor (M³) <span class="text-danger">*</span></label>
                        <input type="number" name="lectura" class="form-control fw-bold text-primary border-primary border-opacity-50" placeholder="Ej. 1250" required>
                    </div>
                    
                </div>
                
                <div class="modal-footer bg-light border-top py-2 px-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 rounded-pill shadow-sm fw-semibold">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODALES INDIVIDUALES -->
@foreach($tomas as $toma)
<div class="modal fade" id="modalLectura{{ $toma->id_toma }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 350px;">
        <div class="modal-content border-0 shadow-lg text-start rounded-4">
            <div class="modal-header border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold text-dark" style="font-size: 0.9rem;">Capturar Lectura - Toma {{ $toma->id_toma }}</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('calculos_medidor.guardar_lectura', ['toma_id' => $toma->id_toma]) }}" method="POST">
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Año</label>
                        <input type="number" name="anio" class="form-control form-control-sm bg-light" value="{{ request('anio', date('Y')) }}" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Mes</label>
                        <select name="mes" class="form-select form-select-sm" required>
                            <option value="">Seleccionar...</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ $nombresMeses[$m-1] }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold text-primary mb-1">Lectura (M³)</label>
                        <input type="number" name="lectura" class="form-control form-control-sm fw-bold text-primary border-primary border-opacity-50" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2 px-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 rounded-pill shadow-sm">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script src="{{ asset('assets/js/calculos_medidores.js') }}"></script>
@endpush
@endsection