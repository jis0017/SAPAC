
  let ultimaMarcaNotificacion = null;
  let notificacionesActuales = [];

  function obtenerOcultas() {
    try {
      return JSON.parse(localStorage.getItem('sapac_notificaciones_ocultas') || '[]');
    } catch (e) {
      return [];
    }
  }

  function cargarNotificacionesPipas() {
    const contenedor = document.getElementById('contenedorNotificacionesPipas');
    const dot = document.getElementById('dotNotificacionPipa');
    if (!contenedor) return;

    fetch('/api/notificaciones-pipas')
      .then(res => res.json())
      .then(data => {
        if (!data.success || !data.notificaciones) return;

        const ocultas = obtenerOcultas();
        // Filtrar las que el usuario ya borró de la campana
        notificacionesActuales = data.notificaciones.filter(n => !ocultas.includes(n.marca_id));

        if (notificacionesActuales.length === 0) {
          contenedor.innerHTML = '<div class="text-center text-muted py-4 small"><i class="bi bi-bell-slash d-block fs-4 mb-1 opacity-50"></i>No hay notificaciones pendientes.</div>';
          if (dot) dot.classList.add('d-none');
          return;
        }

        ultimaMarcaNotificacion = notificacionesActuales[0].marca_id;
        const marcaGuardada = localStorage.getItem('sapac_ultima_notif_vista');

        if (dot) {
          if (marcaGuardada !== ultimaMarcaNotificacion) {
            dot.classList.remove('d-none');
          } else {
            dot.classList.add('d-none');
          }
        }

        let html = '';
        notificacionesActuales.forEach(n => {
          const esEntregado = (n.estado === 'Entregado');
          const icono = esEntregado 
            ? '<i class="bi bi-check-circle-fill text-success me-1"></i>' 
            : '<i class="bi bi-x-circle-fill text-danger me-1"></i>';
          const claseTexto = esEntregado ? 'text-success' : 'text-danger';

          html += `
            <div class="dropdown-item py-2 px-3 border-bottom text-wrap position-relative d-flex justify-content-between align-items-start gap-2">
              <a href="${n.url}" class="text-decoration-none flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="notification-title fw-bold small ${claseTexto}">${icono}${n.titulo}</span>
                  <span class="notification-time text-muted ms-2" style="font-size: 0.68rem;">${n.tiempo}</span>
                </div>
                <div class="small text-dark text-truncate" style="max-width: 250px;">${n.detalle}</div>
                <small class="text-muted d-block" style="font-size: 0.7rem;">
                  <i class="bi bi-truck me-1"></i>${n.pipero}
                </small>
              </a>
              <button type="button" class="btn btn-sm text-muted p-0 ms-1 lh-1" title="Descartar aviso" onclick="descartarNotificacion('${n.marca_id}', event)">
                <i class="bi bi-x-lg" style="font-size: 0.75rem;"></i>
              </button>
            </div>
          `;
        });

        contenedor.innerHTML = html;
      })
      .catch(() => {});
  }

  function descartarNotificacion(marcaId, event) {
    event.stopPropagation();
    event.preventDefault();
    let ocultas = obtenerOcultas();
    if (!ocultas.includes(marcaId)) {
      ocultas.push(marcaId);
      localStorage.setItem('sapac_notificaciones_ocultas', JSON.stringify(ocultas.slice(-50)));
    }
    cargarNotificacionesPipas();
  }

  function limpiarTodasNotificaciones(event) {
    event.stopPropagation();
    event.preventDefault();
    let ocultas = obtenerOcultas();
    notificacionesActuales.forEach(n => {
      if (!ocultas.includes(n.marca_id)) {
        ocultas.push(n.marca_id);
      }
    });
    localStorage.setItem('sapac_notificaciones_ocultas', JSON.stringify(ocultas.slice(-50)));
    marcarNotificacionesVistas();
    cargarNotificacionesPipas();
  }

  function marcarNotificacionesVistas() {
    if (ultimaMarcaNotificacion) {
      localStorage.setItem('sapac_ultima_notif_vista', ultimaMarcaNotificacion);
    }
    const dot = document.getElementById('dotNotificacionPipa');
    if (dot) dot.classList.add('d-none');
  }

  document.addEventListener("DOMContentLoaded", function() {
    cargarNotificacionesPipas();
    setInterval(cargarNotificacionesPipas, 15000);
  });

