"use strict";

// Coordenadas exactas de la cabecera municipal de Acatic, Jalisco
const centroAcatic = [20.7791, -102.9094];
let map;
let markers = {};
let modalBS;
let pozosData = [];

document.addEventListener("DOMContentLoaded", function() {
    modalBS = new bootstrap.Modal(document.getElementById('modalPozo'));

    // Procesar los datos que inyectamos desde Blade
    if (window.pozosDataInicial) {
        pozosData = window.pozosDataInicial.map(p => ({
            id: p.id_pozo,
            nombre: p.nombre_pozo,
            lat: parseFloat(p.latitud),
            lng: parseFloat(p.longitud),
            altitud: p.altitud ? `${p.altitud} msnm` : 'N/D',
            estado: p.estado
        }));
    }

    // Inicializar el mapa centrado automáticamente en la cabecera de Acatic
    map = L.map('mapaPozos').setView(centroAcatic, 14);

    // Capa Satelital Híbrida de Google Maps
    L.tileLayer('http://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
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

    if (pozosData.length === 0) {
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
    document.getElementById('formPozo').action = "/pozos"; // Ruta directa
    document.getElementById('modalPozoHeader').innerText = 'Nuevo Pozo de Agua';
    document.getElementById('pozoMethod').value = 'POST';
    modalBS.show();
}

function abrirModalEditarPozo(pozo) {
    document.getElementById('formPozo').action = `/pozos/${pozo.id}`; // Ruta directa
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