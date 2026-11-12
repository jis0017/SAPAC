@extends('layouts.app')
@section('title', 'Cálculos de Medidores - SAPAC')
@section('content')
<div class="container-fluid py-3 px-2">

    <!-- ENCABEZADO -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex justify-content-center align-items-center shadow-sm flex-shrink-0" style="width: 48px; height: 48px;">
                <i class="bi bi-speedometer2 fs-4"></i>
            </div>
            <div>
                <span class="text-uppercase fw-bold text-muted d-block" style="font-size: 0.7rem; letter-spacing: 0.05em;">GESTIÓN MUNICIPAL · TEQUILILLA (12 m³/MES · 144 m³/AÑO)</span>
                <h4 class="fw-bold mb-0 text-dark">Cálculos de Medidores</h4>
                <small class="text-muted"><i class="bi bi-hand-index-thumb me-1 text-primary"></i>Haz clic sobre cualquier fila para añadir o modificar lecturas, ver PDF o facturar excedentes.</small>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <select class="form-select form-select-sm shadow-sm border py-1.5 fw-bold text-dark rounded-3 select-anio-limpio" aria-label="Seleccionar Año" onchange="window.location.href='?anio='+this.value">
                @for($i = 2025; $i <= date('Y') + 15; $i++)
                    <option value="{{ $i }}" {{ request('anio', date('Y')) == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
            <button class="btn btn-primary btn-sm d-flex align-items-center gap-2 shadow-sm rounded-3 px-3 py-1.5 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaLecturaGlobal">
                <i class="bi bi-plus-circle"></i>
                <span>Capturar Lectura</span>
            </button>
        </div>
    </div>
    <!-- ALERTAS DE ERROR O ÉXITO-->
    @if(session('error'))
        <div class="alert alert-danger py-2 shadow-sm mb-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
    <div class="modal fade" id="modalExitoAccion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content border-0 shadow-lg p-3 text-center rounded-4">
                <div class="modal-body py-3">
                    <div class="mb-2">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px; border: 3px solid rgba(25, 135, 84, 0.15);">
                            <i class="bi bi-check-lg text-success fs-2 fw-bold"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark">¡Operación Exitosa!</h5>
                    <p class="text-muted small mb-3">{{ session('success') }}</p>
                    <button type="button" class="btn btn-sm btn-primary px-4 rounded-pill fw-semibold shadow-sm" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <!-- TABLA PRINCIPAL -->
            <div class="table-responsive" style="max-height: 74vh;">
                <table class="table table-striped align-middle mb-0 text-center tabla-medidores">
                    <thead class="sticky-top text-uppercase text-muted" style="z-index: 3;">
                        <tr>
                            <th rowspan="2" class="col-titular text-start ps-3">TITULAR / MEDIDOR</th>
                            <th rowspan="2" class="col-resumen bg-secondary bg-opacity-10 text-dark" title="Lectura Base del Año Anterior">L. ANT</th>
                            <th colspan="12" class="py-1 text-primary bg-primary bg-opacity-10">LECTURAS MENSUALES DEL MEDIDOR (M³)</th>
                            <th rowspan="2" class="col-resumen bg-warning bg-opacity-10 text-dark" title="Consumo registrado en el último mes">GASTO<br>MES</th>
                            <th rowspan="2" class="col-resumen bg-danger bg-opacity-10 text-danger" title="Metros Cúbicos Excedentes (> 144 m³ anuales)">EXCEDENTE<br>(M³)</th>
                        </tr>
                        <tr>
                            <th class="col-mes">ENE</th><th class="col-mes">FEB</th><th class="col-mes">MAR</th>
                            <th class="col-mes">ABR</th><th class="col-mes">MAY</th><th class="col-mes">JUN</th>
                            <th class="col-mes">JUL</th><th class="col-mes">AGO</th><th class="col-mes">SEP</th>
                            <th class="col-mes">OCT</th><th class="col-mes">NOV</th><th class="col-mes">DIC</th>
                        </tr>
                    </thead>
                    <tbody class="text-body">
                        @forelse($tomas as $toma)
                            @php
                                $limiteAnual = ($toma->aplica_franquicia_anual && $toma->volumen_franquicia_m3 > 0) ? $toma->volumen_franquicia_m3 : 144;
                                $lecturasOrdenadas = collect($toma->calculos)->sortBy('mes')->values();
                                $ultima = $lecturasOrdenadas->last();
                                $penultima = $lecturasOrdenadas->count() > 1 ? $lecturasOrdenadas->slice(-2, 1)->first() : null;

                                $acumulado = $ultima ? $ultima->consumo_acumulado : 0;
                                $acumuladoPrevio = $penultima ? $penultima->consumo_acumulado : 0;

                                if ($acumulado <= $limiteAnual) {
                                    $excedente = 0;
                                } elseif ($acumuladoPrevio <= $limiteAnual) {
                                    $excedente = $acumulado - $limiteAnual;
                                } else {
                                    $excedente = $ultima ? $ultima->consumo_mes : 0;
                                }

                                $consumoUltimoMes = $ultima ? $ultima->consumo_mes : 0;
                            @endphp
                            <tr class="fila-seleccionable" data-bs-toggle="modal" data-bs-target="#modalLectura{{ $toma->id_toma }}" title="Clic para añadir/modificar lectura o gestionar excedente">
                                <td class="col-titular text-start ps-3">
                                    <div class="d-flex justify-content-between align-items-center gap-1">
                                        <div class="nombre-titular text-truncate">
                                            {{ $toma->usuario->razon_social ?? 'USUARIO' }}
                                        </div>
                                        @if($excedente > 0)
                                            <span class="badge bg-danger rounded-pill px-2 py-0.5 flex-shrink-0" style="font-size: 0.65rem;">
                                                +{{ $excedente }} m³
                                            </span>
                                        @endif
                                    </div>
                                    <div class="datos-titular d-flex align-items-center justify-content-between gap-1 mt-1">
                                        <span class="text-truncate">
                                            <i class="bi bi-geo-alt-fill text-primary"></i>
                                            {{ $toma->calle ?? '' }} {{ $toma->numero_exterior ?? '' }}
                                        </span>
                                        <span class="badge bg-light text-secondary border font-monospace px-1.5 py-0 flex-shrink-0" style="font-size: 0.72rem;">
                                            <i class="bi bi-speedometer2 me-1"></i>{{ $toma->no_medidor ?? 'S/N' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="col-resumen fw-bold text-secondary bg-secondary bg-opacity-10">
                                    {{ $toma->lectura_base ?? 0 }}
                                </td>
                                @for($mes = 1; $mes <= 12; $mes++)
                                    @php 
                                        $lecturaMes = $lecturasOrdenadas->where('mes', $mes)->first(); 
                                    @endphp
                                    <td class="col-mes {{ $lecturaMes ? ($lecturaMes->es_excedente ? 'text-danger fw-bold bg-danger bg-opacity-10' : 'text-dark') : 'text-muted opacity-50' }}"
                                        title="{{ $lecturaMes ? 'Lectura: '.$lecturaMes->lectura.' m³ (Consumo mes: '.$lecturaMes->consumo_mes.' m³)' : 'Sin lectura' }}">
                                        {{ $lecturaMes ? $lecturaMes->lectura : '·' }}
                                    </td>
                                @endfor
                                <td class="col-resumen fw-bold text-dark bg-warning bg-opacity-10" title="{{ number_format($consumoUltimoMes * 1000) }} Litros consumidos en el mes">
                                    {{ $consumoUltimoMes }} m³
                                </td>
                                <td class="col-resumen fw-bold {{ $excedente > 0 ? 'text-danger bg-danger bg-opacity-10' : 'text-muted bg-light' }}" title="{{ number_format($excedente * 1000) }} Litros excedentes">
                                    {{ $excedente }} m³
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No hay tomas de agua con servicio medido registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <!-- PAGINACIÓN -->
        <div class="card-footer bg-body border-top py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <small class="text-muted fw-semibold">
                Mostrando <span class="text-primary">{{ $tomas->firstItem() ?? 0 }}</span> a <span class="text-primary">{{ $tomas->lastItem() ?? 0 }}</span> de {{ $tomas->total() }} tomas
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
                                @foreach ($element as $page => $url)
                                    @if ($page == $tomas->currentPage())
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
<!-- MODALES -->
@php 
    $nombresMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']; 
@endphp
<!-- MODAL GLOBAL CON BUSCADOR-->
<div class="modal fade" id="modalNuevaLecturaGlobal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content border-0 shadow-lg text-start rounded-4">
            <div class="modal-header border-bottom py-2.5 px-3">
                <h6 class="modal-title fw-bold text-dark mb-0">
                    <i class="bi bi-plus-circle text-primary me-1"></i>Capturar, Modificar o Eliminar Lectura
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
                                    @php
                                        $mapaGlobal = collect($t->calculos ?? [])->pluck('lectura', 'mes');
                                    @endphp
                                    <option value="{{ $t->id_toma }}" data-lecturas='@json($mapaGlobal)'>
                                        ID {{ $t->id_toma }} - {{ $t->usuario->razon_social ?? 'S/N' }} (Med: {{ $t->no_medidor ?? 'S/N' }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <input type="hidden" id="toma_id_hidden" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-5">
                            <label class="form-label small fw-semibold text-muted mb-1">Año <span class="text-danger">*</span></label>
                            <input type="number" name="anio" id="inputAnioGlobal" class="form-control form-control-sm bg-light fw-bold" value="{{ request('anio', date('Y')) }}" required readonly>
                        </div>
                        <div class="col-7">
                            <label class="form-label small fw-semibold text-muted mb-1">Mes <span class="text-danger">*</span></label>
                            <select name="mes" id="selectMesGlobal" class="form-select form-select-sm" required onchange="verificarMesGlobal()">
                                <option value="">Seleccionar mes...</option>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">{{ $nombresMeses[$m-1] }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div id="alertaModoGlobal" class="alert py-1.5 px-2.5 small mb-2 d-none"></div>
                    <div>
                        <label class="form-label small fw-semibold text-primary mb-1" id="labelLecturaGlobal">Lectura en Medidor (m³) <span class="text-danger">*</span></label>
                        <input type="number" name="lectura" id="inputLecturaGlobal" class="form-control fw-bold text-primary border-primary border-opacity-50" placeholder="Ej. 1250" required>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-top py-2 px-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                    <div class="d-flex gap-2">
                        <button type="button" id="btnEliminarGlobal" class="btn btn-sm btn-outline-danger px-3 rounded-pill fw-semibold d-none" onclick="confirmarEliminarGlobal()">
                            <i class="bi bi-trash me-1"></i>Eliminar
                        </button>
                        <button type="submit" id="btnSubmitGlobal" class="btn btn-sm btn-primary px-4 rounded-pill shadow-sm fw-semibold">Guardar Lectura</button>
                    </div>
                </div>
            </form>

            <form id="formEliminarLecturaGlobal" method="POST" data-action-base="{{ url('/medidores/eliminar-lectura') }}" class="d-none">
                @csrf
                @method('DELETE')
                <input type="hidden" name="anio" id="deleteAnioGlobal" value="{{ request('anio', date('Y')) }}">
                <input type="hidden" name="mes" id="deleteMesGlobal">
            </form>
        </div>
    </div>
</div>
<!-- MODALES INDIVIDUALES HORIZONTALES (2 COLUMNAS) -->
@foreach($tomas as $toma)
    @php
        $limiteModal = ($toma->aplica_franquicia_anual && $toma->volumen_franquicia_m3 > 0) 
            ? $toma->volumen_franquicia_m3
            : 144;

        $lecturasModal = collect($toma->calculos)->sortBy('mes')->values();
        $ultimaModal = $lecturasModal->last();
        $penultimaModal = $lecturasModal->count() > 1
            ? $lecturasModal->slice(-2, 1)->first()
            : null;

        $acumuladoModal = $ultimaModal ? $ultimaModal->consumo_acumulado : 0;
        $acumuladoPrevioModal = $penultimaModal ? $penultimaModal->consumo_acumulado : 0;

        if ($acumuladoModal <= $limiteModal) {
            $excedenteModal = 0;
        } elseif ($acumuladoPrevioModal <= $limiteModal) {
            $excedenteModal = $acumuladoModal - $limiteModal;
        } else {
            $excedenteModal = $ultimaModal ? $ultimaModal->consumo_mes : 0;
        }

        $mapaLecturasMes = $lecturasModal->pluck('lectura', 'mes');
    @endphp

    <div class="modal fade modal-medidor"
         id="modalLectura{{ $toma->id_toma }}"
         tabindex="-1"
         aria-labelledby="tituloModalLectura{{ $toma->id_toma }}"
         aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content text-start">

                <!-- ENCABEZADO -->
                <div class="modal-header border-bottom">
                    <div class="pe-2">
                        <h6 class="modal-title fw-bold text-dark mb-0" id="tituloModalLectura{{ $toma->id_toma }}">
                            {{ $toma->usuario->razon_social ?? 'USUARIO' }}
                        </h6>
                        <div class="small text-muted">
                            Toma #{{ $toma->id_toma }}
                            <span class="mx-1">·</span>
                            Medidor: <strong>{{ $toma->no_medidor ?? 'S/N' }}</strong>
                            <span class="mx-1">·</span>
                            Base Anterior: <strong>{{ $toma->lectura_base ?? 0 }} m³</strong>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-6">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="resumen-indicador h-100">
                                        <span class="resumen-etiqueta">Acumulado anual</span>
                                        <div class="resumen-valor">
                                            {{ number_format($acumuladoModal, 0) }} <small class="fs-6">m³</small>
                                        </div>
                                        <span class="resumen-detalle">{{ number_format($acumuladoModal * 1000) }} litros</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="resumen-indicador h-100">
                                        <span class="resumen-etiqueta">Franquicia anual</span>
                                        <div class="resumen-valor text-primary">
                                            {{ number_format($limiteModal, 0) }} <small class="fs-6">m³</small>
                                        </div>
                                        <span class="resumen-detalle">12 m³ por mes</span>
                                    </div>
                                </div>
                            </div>
                            @if($lecturasModal->isNotEmpty())
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="titulo-seccion">
                                        <i class="bi bi-calendar-check me-1"></i>Historial de lecturas
                                    </h6>
                                    <span class="badge rounded-pill bg-light text-secondary border">
                                        {{ $lecturasModal->count() }} {{ $lecturasModal->count() === 1 ? 'mes' : 'meses' }}
                                    </span>
                                </div>
                                <div class="historial-lecturas">
                                    @foreach($lecturasModal as $reg)
                                        <div class="lectura-item">
                                            <div class="min-w-0">
                                                <div class="d-flex align-items-center flex-wrap gap-2">
                                                    <strong class="text-dark small">{{ $nombresMeses[$reg->mes - 1] }}</strong>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border">
                                                        {{ number_format($reg->lectura, 0) }} m³
                                                    </span>
                                                </div>
                                                <small class="text-muted">Gasto del mes: {{ number_format($reg->consumo_mes, 0) }} m³</small>
                                            </div>

                                            <div class="lectura-acciones">
                                                <button type="button"
                                                        class="btn btn-outline-secondary"
                                                        title="Modificar lectura de {{ $nombresMeses[$reg->mes - 1] }}"
                                                        onclick="document.getElementById('selectMes_{{ $toma->id_toma }}').value='{{ $reg->mes }}'; detectarLecturaExistente({{ $toma->id_toma }}, '{{ $reg->mes }}');">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="{{ route('calculos_medidor.eliminar_lectura', ['toma_id' => $toma->id_toma]) }}"
                                                      method="POST"
                                                      class="m-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="anio" value="{{ request('anio', date('Y')) }}">
                                                    <input type="hidden" name="mes" value="{{ $reg->mes }}">
                                                    <button type="submit"
                                                            class="btn btn-outline-danger"
                                                            title="Eliminar lectura de {{ $nombresMeses[$reg->mes - 1] }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="border rounded-3 bg-light text-center text-muted py-4 px-2 small">
                                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                    Todavía no hay lecturas registradas para este año.
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('calculos_medidor.guardar_lectura', ['toma_id' => $toma->id_toma]) }}" method="POST">
                                @csrf
                                <div class="formulario-lectura">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <h6 class="titulo-seccion text-primary">
                                            <i class="bi bi-sliders me-1"></i>Gestionar lectura
                                        </h6>
                                        <span class="badge bg-secondary d-none" id="badgeModoModal{{ $toma->id_toma }}"></span>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-4">
                                            <label class="form-label">Año</label>
                                            <input type="number"
                                                   name="anio"
                                                   class="form-control form-control-sm bg-light fw-bold"
                                                   value="{{ request('anio', date('Y')) }}"
                                                   readonly>
                                        </div>
                                        <div class="col-8">
                                            <label class="form-label" for="selectMes_{{ $toma->id_toma }}">
                                                Mes <span class="text-danger">*</span>
                                            </label>
                                            <select name="mes"
                                                    id="selectMes_{{ $toma->id_toma }}"
                                                    class="form-select form-select-sm fw-semibold"
                                                    required
                                                    onchange="detectarLecturaExistente({{ $toma->id_toma }}, this.value)">
                                                <option value="">Seleccionar mes...</option>
                                                @for($m = 1; $m <= 12; $m++)
                                                    @php $tieneValor = isset($mapaLecturasMes[$m]); @endphp
                                                    <option value="{{ $m }}"
                                                            data-lectura="{{ $tieneValor ? $mapaLecturasMes[$m] : '' }}">
                                                        {{ $nombresMeses[$m - 1] }}{{ $tieneValor ? ' · ('.$mapaLecturasMes[$m].' m³)' : ' · Sin registro' }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div id="avisoModificacion_{{ $toma->id_toma }}"
                                         class="alert alert-warning py-1.5 px-2 small mb-2 d-none">
                                        <i class="bi bi-pencil-square me-1"></i>
                                        Este mes ya tiene lectura. Puedes modificarla o eliminarla.
                                    </div>
                                    <label class="form-label" for="inputLectura_{{ $toma->id_toma }}">
                                        Lectura del medidor (m³) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number"
                                               name="lectura"
                                               id="inputLectura_{{ $toma->id_toma }}"
                                               class="form-control fw-bold text-primary"
                                               placeholder="Ej. 1250"
                                               required>
                                        <span class="input-group-text bg-light fw-semibold text-muted">m³</span>
                                        <button type="submit"
                                                id="btnGuardar_{{ $toma->id_toma }}"
                                                class="btn btn-primary fw-semibold px-3">
                                            <i class="bi bi-check-lg me-1"></i>Guardar
                                        </button>
                                        <button type="button"
                                                id="btnEliminar_{{ $toma->id_toma }}"
                                                class="btn btn-outline-danger fw-semibold d-none"
                                                title="Eliminar lectura del mes seleccionado"
                                                onclick="confirmarEliminarLectura({{ $toma->id_toma }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <form id="formEliminar_{{ $toma->id_toma }}"
                                  action="{{ route('calculos_medidor.eliminar_lectura', ['toma_id' => $toma->id_toma]) }}"
                                  method="POST"
                                  class="d-none">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="anio" value="{{ request('anio', date('Y')) }}">
                                <input type="hidden" name="mes" id="inputMesEliminar_{{ $toma->id_toma }}">
                            </form>
                            @if($excedenteModal > 0)
                                <section class="panel-excedente mt-3">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                        <span class="fw-bold text-danger small">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Excedente a cobrar
                                        </span>
                                        <span class="badge bg-danger">
                                            {{ number_format($excedenteModal, 0) }} m³ ({{ number_format($excedenteModal * 1000) }} Lts)
                                        </span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-5">
                                            <a href="{{ route('calculos_medidor.GenerarPdf', ['toma_id' => $toma->id_toma, 'anio' => request('anio', date('Y'))]) }}"
                                               target="_blank"
                                               rel="noopener"
                                               class="btn btn-sm btn-outline-danger bg-white w-100 fw-bold">
                                                <i class="bi bi-file-earmark-pdf me-1"></i>Ver PDF
                                            </a>
                                        </div>
                                        <div class="col-7">
                                            <form action="{{ route('calculos_medidor.facturar', ['toma_id' => $toma->id_toma, 'anio' => request('anio', date('Y'))]) }}"
                                                  method="POST"
                                                  class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success w-100 fw-bold">
                                                    <i class="bi bi-currency-dollar me-1"></i>Enviar a Caja
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </section>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
@push('scripts')
<script src="{{ asset('assets/js/calculos_medidores.js') }}?v={{ time() }}"></script>
@endpush