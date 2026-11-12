"use strict";

let modalEliminarBS;
let modalEliminarRolBS;

document.addEventListener("DOMContentLoaded", function() {
  const elModalEliminar = document.getElementById('modalEliminarTrabajador');
  if (typeof bootstrap !== 'undefined' && elModalEliminar) {
    modalEliminarBS = new bootstrap.Modal(elModalEliminar);
  }

  const elModalEliminarRol = document.getElementById('modalEliminarRol');
  if (typeof bootstrap !== 'undefined' && elModalEliminarRol) {
    modalEliminarRolBS = new bootstrap.Modal(elModalEliminarRol);
  }
});

function abrirModalEliminarRol(id, nombreRol) {
  document.getElementById('nombreRolEliminar').innerText = nombreRol;
  document.getElementById('formEliminarRol').action = `/trabajadores/tipos/${id}`;
  modalEliminarRolBS.show();
}

function prepararModalNuevo() {
  document.getElementById('tituloModalTrabajador').innerText = 'Registrar Nuevo Trabajador';
  document.getElementById('formTrabajador').action = "/trabajadores"; // Ruta asumida del store
  document.getElementById('methodFieldContainer').innerHTML = '';
  document.getElementById('btnGuardarTrabajador').innerText = 'Guardar Trabajador';
  document.getElementById('formTrabajador').reset();
  document.getElementById('spanAsteriscoPass').style.display = 'inline';
  document.getElementById('password').setAttribute('required', 'required');
  document.getElementById('AyudaPassword').style.display = 'none';
}

function abrirModalEditar(id, nombre, usuario, tipoId, activo) {
  document.getElementById('tituloModalTrabajador').innerText = 'Actualizar Datos del Trabajador';
  document.getElementById('formTrabajador').action = `/trabajadores/${id}`;
  document.getElementById('methodFieldContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
  document.getElementById('btnGuardarTrabajador').innerText = 'Actualizar Trabajador';

  document.getElementById('nombre').value = nombre;
  document.getElementById('usuario_trabajador').value = usuario;
  document.getElementById('id_tipo_trabajador').value = tipoId;
  document.getElementById('activo').value = activo;

  document.getElementById('spanAsteriscoPass').style.display = 'none';
  document.getElementById('password').removeAttribute('required');
  document.getElementById('password').value = '';
  document.getElementById('AyudaPassword').style.display = 'block';

  var modalEl = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTrabajador'));
  modalEl.show();
}

function abrirModalEliminar(id, nombre) {
  document.getElementById('nombreTrabajadorEliminar').innerText = nombre;
  document.getElementById('formEliminarTrabajador').action = `/trabajadores/${id}`;
  modalEliminarBS.show();
}

function editarRol(id, nombreRol) {
  const form = document.getElementById('formTipoTrabajador');
  form.action = `/trabajadores/tipos/${id}`;
  document.getElementById('methodRolContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
  document.getElementById('inputNombreRol').value = nombreRol;
  document.getElementById('tituloFormRol').innerText = 'Actualizar Rol';
  document.getElementById('btnGuardarRol').innerText = 'Actualizar Rol';
}

function resetFormRol() {
  const form = document.getElementById('formTipoTrabajador');
  form.action = "/trabajadores/tipos"; // Ruta asumida del store de tipos
  document.getElementById('methodRolContainer').innerHTML = '';
  document.getElementById('inputNombreRol').value = '';
  document.getElementById('tituloFormRol').innerText = 'Registrar Nuevo Rol';
  document.getElementById('btnGuardarRol').innerText = 'Guardar Rol';
}