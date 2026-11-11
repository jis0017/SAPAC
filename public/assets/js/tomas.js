"use strict";

let modalHistoricoBS;
let modalEliminarBS;
let cachePagosGlobal = [];
let mostrandoTodo = false;

document.addEventListener("DOMContentLoaded", function() {
  // Inicialización de Modales
  const elModalHistorico = document.getElementById('modalHistoricoPagos');
  if (typeof bootstrap !== 'undefined' && elModalHistorico) {
    modalHistoricoBS = new bootstrap.Modal(elModalHistorico);
  }
  
  const elModalEliminar = document.getElementById('modalEliminarToma');
  if (typeof bootstrap !== 'undefined' && elModalEliminar) {
    modalEliminarBS = new bootstrap.Modal(elModalEliminar);
  }

  // Inicialización del Modal de Éxito Global (si existe)
  var elModalExito = document.getElementById('modalExitoAccion');
  if (typeof bootstrap !== 'undefined' && elModalExito) {
    var modalExito = new bootstrap.Modal(elModalExito);
    modalExito.show();
  }

  // Escuchar el cierre del modal para resetear los valores
  const modalNuevaTomaEl = document.getElementById('modalNuevaToma');
  if (modalNuevaTomaEl) {
    modalNuevaTomaEl.addEventListener('hidden.bs.modal', function () {
      document.getElementById('tituloModalToma').innerText = 'Alta de Contrato de Toma de Agua';
      document.getElementById('formTomaAgua').action = "/tomas";
      document.getElementById('methodFieldContainer').innerHTML = '';
      document.getElementById('btnGuardarModal').innerText = 'Guardar Contrato de Toma';
      document.getElementById('formTomaAgua').reset();
      document.getElementById('badgeTitularSeleccionadoModal').classList.add('d-none');
      document.getElementById('divMedidorModal').style.display = 'none';
      document.getElementById('no_medidor').removeAttribute('required');
    });
  }

  // Inicializar buscador asíncrono para el modal
  const buscadorInput = document.getElementById('buscadorUsuarioModal');
  if(buscadorInput) {
    buscadorInput.addEventListener('input', function() {
      let query = this.value.trim();
      let contenedor = document.getElementById('listaResultadosUsuariosModal');

      if(query.length < 2) {
        contenedor.classList.add('d-none');
        contenedor.innerHTML = '';
        return;
      }

      fetch(`/api/buscar-usuarios?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
          contenedor.innerHTML = '';
          if(data.length === 0) {
            contenedor.innerHTML = '<div class="list-group-item small text-muted">No se encontraron ciudadanos.</div>';
          } else {
            data.forEach(user => {
              let item = document.createElement('a');
              item.href = '#';
              item.className = 'list-group-item list-group-item-action small text-uppercase';
              let apellidos = user.apellidos ? user.apellidos : '';
              item.innerHTML = `<strong>${user.razon_social} ${apellidos}</strong> (Folio: ${user.no_ciudadano})`;
              item.onclick = function(e) {
                e.preventDefault();
                document.getElementById('usuario_id').value = user.id_usuario;
                document.getElementById('buscadorUsuarioModal').value = `${user.razon_social} ${apellidos}`;
                document.getElementById('textoTitularElegidoModal').innerText = `${user.razon_social} ${apellidos} (Folio: ${user.no_ciudadano})`;
                document.getElementById('badgeTitularSeleccionadoModal').classList.remove('d-none');
                contenedor.classList.add('d-none');
              };
              contenedor.appendChild(item);
            });
          }
          contenedor.classList.remove('d-none');
        });
    });
  }
});

function abrirModalEliminar(idToma, nombreTitular) {
  document.getElementById('nombreTitularEliminar').innerText = nombreTitular;
  document.getElementById('formEliminarToma').action = `/tomas/${idToma}`;
  modalEliminarBS.show();
}

function abrirModalEditar(idToma, usuarioId, nombreTitular, pozoId, localidadId, calle, numExt, numInt, tipoPredioId, claveCatastral, calculoFijo, noMedidor, municipio, estatus) {
  document.getElementById('tituloModalToma').innerText = 'Actualizar Contrato de Toma de Agua';
  document.getElementById('formTomaAgua').action = `/tomas/${idToma}`;
  document.getElementById('methodFieldContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
  document.getElementById('btnGuardarModal').innerText = 'Actualizar Contrato';

  document.getElementById('usuario_id').value = usuarioId;
  document.getElementById('buscadorUsuarioModal').value = nombreTitular;
  document.getElementById('textoTitularElegidoModal').innerText = nombreTitular;
  document.getElementById('badgeTitularSeleccionadoModal').classList.remove('d-none');

  document.getElementById('pozo_id').value = pozoId || '';
  document.getElementById('id_localidad').value = localidadId || '';
  document.getElementById('calle').value = calle || '';
  document.getElementById('numero_exterior').value = (numExt && numExt !== 'null') ? numExt : 'S/N';
  document.getElementById('numero_interior').value = (numInt && numInt !== 'null') ? numInt : '';
  document.getElementById('municipio').value = (municipio && municipio !== 'null') ? municipio : 'ACATIC';
  document.getElementById('id_tipo_predio').value = tipoPredioId || '';
  document.getElementById('clave_catastral').value = (claveCatastral && claveCatastral !== 'null') ? claveCatastral : '';
  document.getElementById('calculo_fijo').value = String(calculoFijo);
  document.getElementById('estatus').value = estatus || 'ACTIVO';
  
  // CORRECCIÓN CLAVE: '0' es Servicio Medido (requiere medidor), '1' es Cuota Fija (no requiere medidor)
  toggleMedidorModal(String(calculoFijo));
  if (String(calculoFijo) === '0') {
    document.getElementById('no_medidor').value = (noMedidor && noMedidor !== 'null') ? noMedidor : '';
    document.getElementById('no_medidor').setAttribute('required', 'required');
  } else {
    document.getElementById('no_medidor').value = '';
    document.getElementById('no_medidor').removeAttribute('required');
  }

  var modalToma = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNuevaToma'));
  modalToma.show();
}

function toggleMedidorModal(val) {
  const divMedidor = document.getElementById('divMedidorModal');
  const inputMedidor = document.getElementById('no_medidor');
  // '0' = Servicio Medido
  if (String(val) === '0') { 
    divMedidor.style.display = 'block';
    inputMedidor.setAttribute('required', 'required');
  } else {
    divMedidor.style.display = 'none';
    inputMedidor.removeAttribute('required');
    inputMedidor.value = '';
  }
}

function verHistoricoPagos(idToma, titular, direccion, medidor, tipoPropiedad) {
  document.getElementById('subtituloTomaModal').innerText = `Toma #TMA-${idToma.toString().padStart(4, '0')} · ${titular}`;
  document.getElementById('modTitular').innerText = titular;
  document.getElementById('modDireccion').innerText = direccion;
  document.getElementById('modTipoPropiedad').innerText = tipoPropiedad;
  document.getElementById('modMedidor').innerText = `Medidor: ${medidor}`;

  const textoSolvencia = document.getElementById('modTextoSolvencia');
  const iconOk = document.getElementById('iconSolvenciaOk');
  const iconAdeudo = document.getElementById('iconSolvenciaAdeudo');
  const tbody = document.getElementById('tbodyHistorialPagos');

  textoSolvencia.innerText = 'VERIFICANDO...';
  textoSolvencia.className = 'fw-bold mb-0 text-secondary';
  iconOk.classList.add('d-none');
  iconAdeudo.classList.add('d-none');
  tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Consultando...</td></tr>';
  
  modalHistoricoBS.show();

  fetch(`/tomas/${idToma}/historial-pagos`)
    .then(res => res.json())
    .then(data => {
      if(data.tiene_adeudo) {
          textoSolvencia.innerText = 'CON ADEUDO';
          textoSolvencia.className = 'fw-bold mb-0 text-danger';
          iconAdeudo.classList.remove('d-none');
      } else {
          textoSolvencia.innerText = 'AL CORRIENTE';
          textoSolvencia.className = 'fw-bold mb-0 text-success';
          iconOk.classList.remove('d-none');
      }

      cachePagosGlobal = data.pagos;
      mostrandoTodo = false;
      renderizarTablaPagos();
    });
}

function renderizarTablaPagos() {
  const tbody = document.getElementById('tbodyHistorialPagos');
  const badgeContador = document.getElementById('badgeContadorRegistros');
  const contenedorBtn = document.getElementById('contenedorBtnVerTodo');
  const btnAlternar = document.getElementById('btnAlternarHistorial');

  tbody.innerHTML = '';

  if(cachePagosGlobal.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-3">No hay registros.</td></tr>';
      if(badgeContador) badgeContador.innerText = '0 registros';
      if(contenedorBtn) contenedorBtn.classList.add('d-none');
      return;
  }

  let pagosAVisualizar = cachePagosGlobal;
  if (!mostrandoTodo && cachePagosGlobal.length > 5) {
      pagosAVisualizar = cachePagosGlobal.slice(0, 5);
      if(badgeContador) badgeContador.innerText = `Mostrando últimos 5 de ${cachePagosGlobal.length} registros`;
      if(contenedorBtn) contenedorBtn.classList.remove('d-none');
      if(btnAlternar) btnAlternar.innerHTML = `<i class="bi bi-arrow-down-circle me-1"></i> Ver historial completo (${cachePagosGlobal.length} pagos)`;
  } else if (cachePagosGlobal.length > 5) {
      if(badgeContador) badgeContador.innerText = `Mostrando todos los registros (${cachePagosGlobal.length})`;
      if(contenedorBtn) contenedorBtn.classList.remove('d-none');
      if(btnAlternar) btnAlternar.innerHTML = `<i class="bi bi-arrow-up-circle me-1"></i> Ver solo los últimos 5`;
  } else {
      if(badgeContador) badgeContador.innerText = `Total de registros: ${cachePagosGlobal.length}`;
      if(contenedorBtn) contenedorBtn.classList.add('d-none');
  }

  pagosAVisualizar.forEach(pago => {
      let estatusBadge = pago.estatus === 'PAGADA' ? 'success' : 'warning';
      const row = `
        <tr>
          <td class="fw-bold font-monospace">${pago.periodo}</td>
          <td>${pago.concepto}</td>
          <td>$${pago.monto_agua}</td>
          <td>$${pago.monto_saneamiento}</td>
          <td>$${pago.monto_infraestructura}</td>
          <td class="text-success">-$${pago.descuento}</td>
          <td class="fw-bold text-primary font-monospace">$${pago.total}</td>
          <td>${pago.fecha_pago}</td>
          <td><span class="badge bg-${estatusBadge}">${pago.estatus}</span></td>
        </tr>
      `;
      tbody.insertAdjacentHTML('beforeend', row);
  });
}

function alternarVistaHistorial() {
  mostrandoTodo = !mostrandoTodo;
  renderizarTablaPagos();
}