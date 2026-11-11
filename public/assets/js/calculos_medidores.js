document.addEventListener("DOMContentLoaded", function() {
    // Escucha el modal de éxito si existe para abrirlo automáticamente
    let modalExito = document.getElementById('modalExitoAccion');
    if (modalExito) {
        new bootstrap.Modal(modalExito).show();
    }
});

function filtrarTomas() {
    let input = document.getElementById("buscadorModal").value.toLowerCase();
    let select = document.getElementById("selectTomaGlobal");
    let options = select.getElementsByTagName("option");
    
    for (let i = 1; i < options.length; i++) { 
        let txtValue = options[i].textContent || options[i].innerText;
        if (txtValue.toLowerCase().indexOf(input) > -1) {
            options[i].hidden = false; // Muestra la opción
            options[i].style.display = ""; 
        } else {
            options[i].hidden = true;  // Oculta la opción de forma segura
            options[i].style.display = "none";
        }
    }
}

function actualizarRutaFormulario() {
    let select = document.getElementById('selectTomaGlobal');
    let form = document.getElementById('formNuevaLecturaGlobal');
    let hidden = document.getElementById('toma_id_hidden');
    let id = select.value;
    
    if(id) {
        hidden.value = id;
        // Toma la URL base desde el atributo "data-action-base" del formulario HTML
        let baseUrl = form.getAttribute('data-action-base'); 
        form.action = baseUrl + "/" + id;
    } else {
        hidden.value = "";
        form.action = "";
    }
}

function validarEnvioModalGlobal() {
    let hiddenId = document.getElementById('toma_id_hidden').value;
    if(!hiddenId) {
        alert("Seleccione un usuario de la lista de resultados.");
        return false;
    }
    return true;
}





  