@extends('layouts.app')

@section('title', 'Mapa Satelital de Pozos - SAPAC')

@section('content')
<!-- CSS de Leaflet Obligatorio -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
  #mapaPozos {
    height: 560px !important;
    width: 100% !important;
    display: block !important;
    border-radius: 0.5rem;
    background-color: #e5e5e5;
  }
  .leaflet-control-attribution {
    display: none !important;
  }
</style>

<div class="container-fluid px-0">

  <!-- Encabezado unificado -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div class="d-flex align-items-center">
      <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px;">
        <i class="bi bi-geo-alt-fill fs-3" aria-hidden="true"></i>
      </div>
      <div>
        <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Infraestructura Hidráulica</span>
        <h1 class="h4 fw-bold text-body mb-0">Ubicación y Monitoreo de Pozos</h1>
      </div>
    </div>
    
    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-outline-secondary btn-sm px-3 py-2 d-flex align-items-center gap-2 shadow-sm" onclick="centrarMapaAcatic()">
        <i class="bi bi-crosshair"></i>
        <span>Centrar en Acatic</span>
      </button>
      <button class="btn btn-primary btn-sm px-3 py-2 d-flex align-items-center gap-2 shadow-sm" onclick="abrirModalCrearPozo()">
        <i class="bi bi-plus-circle"></i>
        <span>Registrar Nuevo Pozo</span>
      </button>
    </div>
  </div>

  <div class="row g-3">
    
    <!-- Contenedor del Mapa -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-2">
          <div id="mapaPozos"></div>
        </div>
      </div>
    </div>

    <!-- Lista Lateral de Pozos con Acciones -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 pt-3 px-3 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-geo-alt text-primary me-2"></i>Pozos Registrados</h5>
          <span class="badge bg-light text-dark border" id="totalPozosBadge">0 Pozos</span>
        </div>
        <div class="card-body p-2" style="max-height: 505px; overflow-y: auto;">
          <div class="list-group list-group-flush" id="listaPozosUI">
            <!-- Dinámico desde JS -->
          </div>
        </div>
      </div>
    </div>

  </div>

</div>

<!-- MODAL ÚNICO: Registrar / Editar Pozo -->
<div class="modal fade" id="modalPozo" tabindex="-1" aria-labelledby="modalPozoTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      
      <div class="modal-header border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold fs-6" id="modalPozoTitle">
          <i class="bi bi-geo-alt-fill text-primary me-2"></i><span id="modalPozoHeader">Nuevo Pozo de Agua</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formPozo" action="{{ url('/pozos') }}" method="POST">
        @csrf
        <input type="hidden" name="_method" id="pozoMethod" value="POST">

        <div class="modal-body p-4 row g-3">

          <!-- Nombre del Pozo -->
          <div class="col-md-12">
            <label for="nombre_pozo" class="form-label small fw-semibold text-body mb-1">Nombre del Pozo / Suministro <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm text-uppercase" id="nombre_pozo" name="nombre_pozo" placeholder="Ej. POZO LA LOMA" required>
          </div>

          <!-- Coordenada Latitud -->
          <div class="col-md-6">
            <label for="latitud" class="form-label small fw-semibold text-body mb-1">Latitud <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm font-monospace" id="latitud" name="latitud" placeholder="20.779100" required>
          </div>

          <!-- Coordenada Longitud -->
          <div class="col-md-6">
            <label for="longitud" class="form-label small fw-semibold text-body mb-1">Longitud <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm font-monospace" id="longitud" name="longitud" placeholder="-102.909400" required>
          </div>

          <!-- Altitud -->
          <div class="col-md-6">
            <label for="altitud" class="form-label small fw-semibold text-body mb-1">Altitud (msnm)</label>
            <input type="text" class="form-control form-control-sm font-monospace" id="altitud" name="altitud" placeholder="Ej. 1680.00">
          </div>

          <!-- Estado Operativo -->
          <div class="col-md-6">
            <label for="estado" class="form-label small fw-semibold text-body mb-1">Estado Operativo <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="estado" name="estado" required>
              <option value="ACTIVO">ACTIVO</option>
              <option value="INACTIVO">INACTIVO / MANTENIMIENTO</option>
            </select>
          </div>

          <div class="col-12">
            <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-info-circle me-1"></i>Haz clic directamente en el mapa para capturar las coordenadas de ubicación automáticamente.</small>
          </div>

        </div>

        <div class="modal-footer border-top py-2 px-4 bg-light bg-opacity-25">
          <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary px-3" id="btnGuardarPozo">Guardar Pozo</button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- =========================================================================
     MODAL DE NOTIFICACIÓN DE ÉXITO (Palomita Verde)
     ========================================================================= -->
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

<!-- JS de Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  // Coordenadas exactas de la cabecera municipal de Acatic, Jalisco
  const centroAcatic = [20.7791, -102.9094];
  let map;
  let markers = {};
  let modalBS;

  let pozosData = @json($pozos).map(p => ({
    id: p.id_pozo,
    nombre: p.nombre_pozo,
    lat: parseFloat(p.latitud),
    lng: parseFloat(p.longitud),
    altitud: p.altitud ? `${p.altitud} msnm` : 'N/D',
    estado: p.estado
  }));

  document.addEventListener("DOMContentLoaded", function() {
    modalBS = new bootstrap.Modal(document.getElementById('modalPozo'));

    // Inicializar el mapa centrado automáticamente en la cabecera de Acatic
    map = L.map('mapaPozos').setView(centroAcatic, 14);

    // Capa Satelital Híbrida de Google Maps
    L.tileLayer('http://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
      maxZoom: 20,
      subdomains: ['mt0','mt1','mt2','mt3']
    }).addTo(map);

    renderizarPozos();

    // Clic en el mapa para capturar coordenadas al registrar o editar
    map.on('click', function(e) {
      document.getElementById('latitud').value = e.latlng.lat.toFixed(6);
      document.getElementById('longitud').value = e.latlng.lng.toFixed(6);
    });

    setTimeout(() => {
      map.invalidateSize();
    }, 400);
  });

  function renderizarPozos() {
    const listaContainer = document.getElementById('listaPozosUI');
    listaContainer.innerHTML = '';

    Object.keys(markers).forEach(k => map.removeLayer(markers[k]));
    markers = {};

    if(pozosData.length === 0) {
      listaContainer.innerHTML = '<div class="text-center text-muted py-4 small">No hay pozos registrados en la base de datos.</div>';
      document.getElementById('totalPozosBadge').innerText = '0 Pozos';
      return;
    }

    pozosData.forEach(p => {
      const colorBadge = (p.estado.toUpperCase() === 'ACTIVO') ? '#198754' : '#dc3545';
      const marker = L.marker([p.lat, p.lng]).addTo(map);
      
      marker.bindPopup(`
        <div style="font-family: sans-serif; padding: 2px;">
          <h6 style="font-weight: bold; margin-bottom: 3px; color: #0d6efd;">${p.nombre}</h6>
          <p style="margin: 0; font-size: 12px; color: #495057;">Altitud: <b>${p.altitud}</b></p>
          <hr style="margin: 5px 0;">
          <span style="background-color: ${colorBadge}; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold;">
            ${p.estado}
          </span>
        </div>
      `);
      markers[p.id] = marker;

      const badgeClass = (p.estado.toUpperCase() === 'ACTIVO') ? 'bg-success' : 'bg-danger';
      const itemHTML = `
        <div class="list-group-item p-2.5 rounded mb-2 border shadow-sm">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="fw-bold text-dark" style="cursor:pointer; font-size: 0.88rem;" onclick="enfocarPozo(${p.lat}, ${p.lng}, ${p.id})">${p.nombre}</span>
            <span class="badge ${badgeClass}" style="font-size: 0.7rem;">${p.estado}</span>
          </div>
          <small class="text-muted d-block mb-1" style="font-size: 0.75rem;"><i class="bi bi-mountain me-1"></i>Altitud: ${p.altitud}</small>
          
          <div class="d-flex justify-content-between align-items-center pt-1 border-top">
            <small class="text-secondary font-monospace" style="font-size: 0.72rem;">${p.lat.toFixed(4)}, ${p.lng.toFixed(4)}</small>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-primary px-2 py-0.5" style="font-size: 0.75rem;" title="Ver en Mapa" onclick="enfocarPozo(${p.lat}, ${p.lng}, ${p.id})">
                <i class="bi bi-eye"></i> Ubicar
              </button>
              <button class="btn btn-outline-secondary px-2 py-0.5" style="font-size: 0.75rem;" title="Editar Pozo" onclick='abrirModalEditarPozo(${JSON.stringify(p)})'>
                <i class="bi bi-pencil"></i>
              </button>
            </div>
          </div>
        </div>
      `;
      listaContainer.innerHTML += itemHTML;
    });

    document.getElementById('totalPozosBadge').innerText = `${pozosData.length} Pozos`;
  }

  function abrirModalCrearPozo() {
    document.getElementById('formPozo').reset();
    document.getElementById('formPozo').action = "{{ url('/pozos') }}";
    document.getElementById('modalPozoHeader').innerText = 'Nuevo Pozo de Agua';
    document.getElementById('pozoMethod').value = 'POST';
    modalBS.show();
  }

  function abrirModalEditarPozo(pozo) {
    document.getElementById('formPozo').action = `{{ url('/pozos') }}/${pozo.id}`;
    document.getElementById('modalPozoHeader').innerText = 'Editar Pozo de Agua';
    document.getElementById('pozoMethod').value = 'PUT';

    document.getElementById('nombre_pozo').value = pozo.nombre;
    document.getElementById('latitud').value = pozo.lat;
    document.getElementById('longitud').value = pozo.lng;
    document.getElementById('altitud').value = parseFloat(pozo.altitud) || '';
    document.getElementById('estado').value = pozo.estado.toUpperCase();

    modalBS.show();
  }

  function enfocarPozo(lat, lng, id) {
    if (map) {
      map.flyTo([lat, lng], 16, { duration: 1.2 });
      if (markers[id]) {
        markers[id].openPopup();
      }
    }
  }

  function centrarMapaAcatic() {
    if (map) {
      map.flyTo(centroAcatic, 14, { duration: 1.0 });
    }
  }
</script>
@endsection