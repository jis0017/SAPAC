let modalEliminarBS;

document.addEventListener("DOMContentLoaded", function() {
  const elModalEliminar = document.getElementById('modalConfirmarEliminar');
  if (typeof bootstrap !== 'undefined' && elModalEliminar) {
    modalEliminarBS = new bootstrap.Modal(elModalEliminar);
  }

  // Si existe el modal de éxito (palomita verde global), lo mostramos
  var elModalExito = document.getElementById('modalExitoAccion');
  if (typeof bootstrap !== 'undefined' && elModalExito) {
    var modalExito = new bootstrap.Modal(elModalExito);
    modalExito.show();
  }
});




function verTomasUsuario(idUsuario, nombreTitular) {
  document.getElementById('modalTitularTomas').textContent = 'Titular: ' + nombreTitular;
  const tbody = document.getElementById('tbodyTomasUsuario');
  tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando tomas asociadas...</td></tr>';

  const modal = new bootstrap.Modal(document.getElementById('modalTomasUsuario'));
  modal.show();

  fetch(`/usuarios/${idUsuario}/tomas`)
    .then(response => response.json())
    .then(data => {
      tbody.innerHTML = '';
      if (!data.success || data.tomas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Este ciudadano no cuenta con tomas de agua asociadas.</td></tr>';
        return;
      }

      data.tomas.forEach(toma => {
        const esquema = toma.calculo_fijo == 1 ? '<span class="badge bg-secondary bg-opacity-10 text-secondary">Cuota Fija</span>' : `<span class="badge bg-info bg-opacity-10 text-info font-monospace">Medidor: ${toma.no_medidor}</span>`;
        
        const row = `
          <tr>
            <td class="ps-3 font-monospace fw-bold">#TMA-${toma.id_toma.toString().padStart(4, '0')}</td>
            <td>
              <div class="fw-semibold text-body">${toma.calle} #${toma.numero_exterior ?? 'S/N'}</div>
              <small class="text-muted">Municipio: ${toma.municipio ?? 'ACATIC'}</small>
            </td>
            <td>${esquema}</td>
            <td class="text-end pe-3">
              <a href="/tomas?buscar=${toma.id_toma}" class="btn btn-sm btn-outline-primary" title="Ver en Tomas de Agua">
                <i class="bi bi-box-arrow-up-right me-1"></i> Ver Pagos
              </a>
            </td>
          </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
      });
    })
    .catch(error => {
      console.error('Error:', error);
      tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-danger">Ocurrió un error al cargar las tomas.</td></tr>';
    });
}

function abrirModalEditar(id, folio, razonSocial, apellidos, rfc, telefono) {
  document.getElementById('formEditarUsuario').action = '/usuarios/' + id;
  document.getElementById('edit_folio').value = folio;
  document.getElementById('edit_razon_social').value = razonSocial;
  document.getElementById('edit_apellidos').value = apellidos || '';
  document.getElementById('edit_rfc').value = rfc || '';
  document.getElementById('edit_telefono').value = telefono || '';

  var modalEditar = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
  modalEditar.show();
}

function confirmarEliminar(id, folio, nombre) {
  document.getElementById('formEliminarUsuario').action = '/usuarios/' + id;
  document.getElementById('eliminar_no_ciudadano').textContent = '#' + folio;
  document.getElementById('eliminar_nombre').textContent = nombre;
  modalEliminarBS.show();
}