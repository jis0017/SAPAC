"use strict";

document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById('inputBuscadorGlobal');
    const searchResults = document.getElementById('searchResults');
    let timeoutId;

    if (!searchInput || !searchResults) {
        return; 
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(timeoutId);
        const query = this.value.trim();

        if (query.length < 2) {
            searchResults.style.display = 'none';
            searchResults.innerHTML = '';
            return;
        }

        searchResults.style.display = 'block';
        searchResults.innerHTML = '<div class="p-3 text-center text-muted small"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Buscando...</div>';

        timeoutId = setTimeout(() => {
            fetch(`/api/buscador-global?q=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    searchResults.innerHTML = ''; 
                    
                    if (!data || data.length === 0) {
                        searchResults.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-emoji-frown me-1"></i> No se encontraron resultados.</div>';
                        return;
                    }

                    data.forEach(item => {
                        const html = `
                            <a href="${item.url}" class="dropdown-item py-2 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="fs-4 ${item.color || 'text-primary'} me-3">
                                        <i class="bi ${item.icono || 'bi-folder'}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-body text-uppercase" style="font-size: 0.85rem;">${item.titulo}</div>
                                        <div class="text-muted small">${item.tipo} • ${item.subtitulo}</div>
                                    </div>
                                </div>
                            </a>
                        `;
                        searchResults.insertAdjacentHTML('beforeend', html);
                    });
                })
                .catch(error => {
                    console.error('Error en la búsqueda:', error);
                    searchResults.innerHTML = '<div class="p-3 text-center text-danger small">Hubo un error al buscar.</div>';
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    searchInput.addEventListener('focus', function () {
        if (this.value.trim().length >= 2 && searchResults.innerHTML.trim() !== '') {
            searchResults.style.display = 'block';
        }
    });
});