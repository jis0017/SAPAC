"use strict";

document.addEventListener("DOMContentLoaded", function() {
    
    // =======================================================
    // 1. LÓGICA DEL BOTÓN DEL MENÚ LATERAL (SIDEBAR TOGGLE)
    // =======================================================
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const body = document.body;

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Si estamos en pantalla de escritorio (PC), alterna la clase 'sidebar-mini'
            if (window.innerWidth >= 992) {
                body.classList.toggle('sidebar-mini');
            } 
            // Si estamos en móvil o tablet, alterna la clase 'sidebar-open'
            else {
                body.classList.toggle('sidebar-open');
            }
        });
    }

    // =======================================================
    // 2. CONFIGURACIÓN DEL TEMA (MODO CLARO)
    // =======================================================
    localStorage.setItem('sapac_theme', 'light');
    document.documentElement.setAttribute('data-theme', 'light');
    document.documentElement.setAttribute('data-bs-theme', 'light');

    // Ocultar el botón de cambio de tema si existe
    const themeToggleBtn = document.getElementById('themeToggle');
    if (themeToggleBtn) {
        themeToggleBtn.style.display = 'none';
    }

});