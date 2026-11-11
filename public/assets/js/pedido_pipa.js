let modalPedidoBS = null;
let modalEstadoBS = null;
let modalEliminarPedidoBS = null;
let tomasEncontradas = [];

document.addEventListener("DOMContentLoaded", function() {
  const elModalPedido = document.getElementById('modalPedidoPipa');
  const elModalEstado = document.getElementById('modalEstadoPipa');
  const elModalEliminar = document.getElementById('modalEliminarPedido');

  if (typeof bootstrap !== 'undefined') {
    if (elModalPedido) modalPedidoBS = new bootstrap.Modal(elModalPedido);
    if (elModalEstado) modalEstadoBS = new bootstrap.Modal(elModalEstado);
    if (elModalEliminar) modalEliminarPedidoBS = new bootstrap.Modal(elModalEliminar);
  }

  // El modal global de éxito (definido en Blade) se maneja en un script pequeño allá, 
  // o si lo trajimos aquí, asume que 'modalExitoGlobal' existe.
  const elModalExito = document.getElementById('modalExitoGlobal');
  if (typeof bootstrap !== 'undefined' && elModalExito) {
    new bootstrap.Modal(elModalExito).show();
  }
});

function prepararModalNuevoPedido() {
  const form = document.getElementById('formPedidoPipa');
  if (form) form.reset();

  // Reiniciar el formulario a estado "Nuevo"
  document.getElementById('modalPedidoHeader').innerText = 'Nueva Solicitud de Pipa';
  document.getElementById('pedidoMethod').value = 'POST';
  document.getElementById('pedido_id').value = '';
  document.getElementById('toma_id_seleccionada').value = '';
  document.getElementById('id_trabajador').value = '';

  const contenedor = document.getElementById('contenedorEstadoToma');
  if (contenedor) contenedor.style.display = 'none';

  const btnGuardar = document.getElementById('btnGuardarPedido');
  if (btnGuardar) btnGuardar.removeAttribute('disabled');
}

function abrirModalEditarPedido(data) {
  const form = document.getElementById('formPedidoPipa');
  form.action = `/pedidos-pipa/${data.id}`;

  document.getElementById('modalPedidoHeader').innerText = 'Editar Solicitud de Pipa';
  document.getElementById('pedidoMethod').value = 'PUT';
  document.getElementById('pedido_id').value = data.id;
  document.getElementById('toma_id_seleccionada').value = data.toma_id || '';

  document.getElementById('solicitante').value = data.solicitante || '';
  document.getElementById('telefono').value = data.telefono || '';
  document.getElementById('domicilio_entrega').value = data.domicilio || '';
  document.getElementById('capacidad_litros').value = data.capacidad || '10000';
  if (data.pozo_id) document.getElementById('pozo_id').value = data.pozo_id;
  if (data.chofer) document.getElementById('id_trabajador').value = data.chofer;
  if (data.fecha) document.getElementById('fecha_entrega').value = data.fecha;
  document.getElementById('observaciones').value = data.observaciones || '';

  const contenedor = document.getElementById('contenedorEstadoToma');
  if (contenedor) contenedor.style.display = 'none';

  const btnGuardar = document.getElementById('btnGuardarPedido');
  if (btnGuardar) btnGuardar.removeAttribute('disabled');

  if (modalPedidoBS) modalPedidoBS.show();
}

function abrirModalEstado(data) {
  const form = document.getElementById('formEstadoPipa');
  form.action = `/pedidos-pipa/${data.id}`;

  document.getElementById('txtSolicitante').innerText = data.solicitante || '';
  document.getElementById('chofer_asignado').value = data.chofer || '';
  if (data.pozo) document.getElementById('pozo_carga_id').value = data.pozo;
  document.getElementById('estado_pedido').value = data.estado || 'Pendiente';

  if (modalEstadoBS) modalEstadoBS.show();
}

function abrirModalEliminarPedido(id, solicitante) {
  document.getElementById('nombrePedidoEliminar').innerText = solicitante;
  document.getElementById('formEliminarPedido').action = `/pedidos-pipa/${id}`;
  if (modalEliminarPedidoBS) modalEliminarPedidoBS.show();
}

function handleEnterBusqueda(event) {
  if (event.key === 'Enter') {
    event.preventDefault();
    buscarTomaSolvencia();
  }
}

function buscarTomaSolvencia() {
  const query = document.getElementById('buscadorTomaInput').value.trim();
  const msgError = document.getElementById('mensajeBusquedaVacio');
  
  if (!query) {
    msgError.innerText = 'Ingresa el número de toma o nombre a buscar.';
    msgError.style.display = 'block';
    return;
  }
  msgError.style.display = 'none';

  const btnBuscarTexto = document.getElementById('btnBuscarTexto');
  const btnBuscarSpinner = document.getElementById('btnBuscarSpinner');
  const btnBuscar = document.getElementById('btnBuscarToma');

  if (btnBuscarTexto) btnBuscarTexto.classList.add('d-none');
  if (btnBuscarSpinner) btnBuscarSpinner.classList.remove('d-none');
  if (btnBuscar) btnBuscar.setAttribute('disabled', 'true');

  fetch(`/api/tomas/verificar-solvencia?query=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
      const contenedor = document.getElementById('contenedorEstadoToma');
      
      if (!data.encontrado || !data.resultados || data.resultados.length === 0) {
        msgError.innerText = 'No se encontró ninguna toma con esos datos.';
        msgError.style.display = 'block';
        contenedor.style.display = 'none';
        return;
      }

      tomasEncontradas = data.resultados;
      contenedor.style.display = 'block';

      if (tomasEncontradas.length === 1) {
          renderizarTomaSeleccionada(0);
      } else {
          let listHtml = `
            <div class="mb-2 small fw-bold text-primary">
               <i class="bi bi-list-ul"></i> Se encontraron múltiples tomas. Selecciona el domicilio correcto:
            </div>
            <div class="list-group shadow-sm border mb-3">`;
            
          tomasEncontradas.forEach((t, i) => {
              // Ajuste: Mostrar solo "ADEUDO" sin la cantidad
              let badge = t.al_corriente 
                  ? `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2">AL CORRIENTE</span>` 
                  : `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2">CON ADEUDO</span>`;
              
              listHtml += `
              <button type="button" class="list-group-item list-group-item-action p-3 border-0 border-bottom" onclick="renderizarTomaSeleccionada(${i})">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                      <h6 class="mb-0 fw-bold text-dark">${t.ciudadano}</h6>
                      ${badge}
                  </div>
                  <small class="text-muted d-block"><i class="bi bi-geo-alt me-1"></i> Toma #${t.toma_id} - ${t.direccion_completa}</small>
              </button>`;
          });
          listHtml += `</div>`;
          contenedor.innerHTML = listHtml;
          document.getElementById('btnGuardarPedido').setAttribute('disabled', 'true');
      }
    })
    .catch(error => {
      msgError.innerText = 'Error al conectar con el servidor.';
      msgError.style.display = 'block';
    })
    .finally(() => {
      if (btnBuscarTexto) btnBuscarTexto.classList.remove('d-none');
      if (btnBuscarSpinner) btnBuscarSpinner.classList.add('d-none');
      if (btnBuscar) btnBuscar.removeAttribute('disabled');
    });
}

function renderizarTomaSeleccionada(index) {
    const t = tomasEncontradas[index];
    
    document.getElementById('toma_id_seleccionada').value = t.toma_id;
    document.getElementById('solicitante').value = t.ciudadano;
    document.getElementById('domicilio_entrega').value = t.direccion_completa;
    if (t.telefono) document.getElementById('telefono').value = t.telefono.substring(0, 10);

    const htmlCard = `
        <div class="card border p-3 rounded-3 shadow-sm bg-body">
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
            <div>
              <h6 class="fw-bold mb-0 text-body">${t.ciudadano}</h6>
              <small class="text-muted">Toma #${t.toma_id} · ${t.tipo_propiedad}</small>
            </div>
            <div>
                ${t.al_corriente 
                    ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2"><i class="bi bi-check-circle me-1"></i> AL CORRIENTE</span>'
                    : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2"><i class="bi bi-exclamation-octagon me-1"></i> CON ADEUDO</span>'
                }
            </div>
          </div>
          <div class="small text-muted mb-2">
            <i class="bi bi-geo-alt me-1"></i><span>${t.direccion_completa}</span>
          </div>
          ${!t.al_corriente ? `
          <div class="alert alert-danger p-2 mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <i class="bi bi-exclamation-octagon-fill me-1"></i>
              <strong>Pagos Pendientes</strong> <!-- Texto ajustado sin monto -->
            </div>
            <a href="/pagos" target="_blank" class="btn btn-sm btn-danger fw-semibold">
              <i class="bi bi-cash-coin me-1"></i> Ir a Caja a Cobrar
            </a>
          </div>` : ''}
        </div>
        <div class="text-end mt-2">
            <button type="button" class="btn btn-link btn-sm text-muted text-decoration-none" onclick="buscarTomaSolvencia()"><i class="bi bi-arrow-left-circle me-1"></i>Volver a la lista</button>
        </div>
    `;
    
    document.getElementById('contenedorEstadoToma').innerHTML = htmlCard;

    const btnGuardar = document.getElementById('btnGuardarPedido');
    if (t.al_corriente) {
        btnGuardar.removeAttribute('disabled');
    } else {
        btnGuardar.setAttribute('disabled', 'true');
    }
}