<nav class="navbar admin-navbar navbar-expand bg-white">
  <div class="container-fluid px-3 px-lg-4">
    <!-- Botón para colapsar/abrir el menú lateral -->
    <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="adminSidebar" aria-expanded="true" aria-label="Toggle sidebar">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- Buscador global inteligente -->
    <form class="d-none d-md-flex ms-3 flex-grow-1 position-relative" role="search" onsubmit="event.preventDefault();">
      <div class="input-group w-100">
        <span class="input-group-text bg-light border-end-0 text-muted">
          <i class="bi bi-search"></i>
        </span>
        <input class="form-control search-input border-start-0 bg-light" type="search" id="inputBuscadorGlobal" placeholder="Buscar ciudadano, folio de toma, pedido de pipa..." aria-label="Search" autocomplete="off">
      </div>
      <!-- CONTENEDOR DE RESULTADOS -->
      <div id="searchResults" class="dropdown-menu shadow-sm border-0 w-100 mt-1" style="display: none; max-height: 350px; overflow-y: auto; position: absolute; top: 100%; left: 0; z-index: 1050;"></div>
    </form>

    <div class="navbar-actions ms-auto">
      
      <!-- Menú de Notificaciones Dinámico -->
      <div class="dropdown">
        <button class="icon-button position-relative" type="button" id="btnCampanaNotificaciones" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications" onclick="marcarNotificacionesVistas()">
          <span class="notification-dot d-none" id="dotNotificacionPipa"></span>
          <i class="bi bi-bell" aria-hidden="true"></i>
        </button>

        <div class="dropdown-menu dropdown-menu-end notification-menu shadow border-0" style="min-width: 330px; max-width: 360px;">
          <div class="dropdown-header fw-bold text-body d-flex justify-content-between align-items-center border-bottom pb-2">
            <span>Notificaciones de Pipas</span>
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none p-0 fw-semibold" style="font-size: 0.75rem;" onclick="limpiarTodasNotificaciones(event)">
                <i class="bi bi-trash3 me-1"></i>Limpiar
              </button>
              <span class="text-muted opacity-50">|</span>
              <a href="{{ route('pedidos.pipas') }}" class="text-primary text-decoration-none small fw-semibold" style="font-size: 0.75rem;">Ver todas</a>
            </div>
          </div>

          <div id="contenedorNotificacionesPipas" style="max-height: 320px; overflow-y: auto;">
            <div class="text-center text-muted py-3 small">Cargando avisos...</div>
          </div>
        </div>
      </div>

      <!-- Divisor vertical -->
      <div class="vr bg-secondary opacity-25 d-none d-sm-block" style="height: 24px;"></div>

      <!-- Perfil de Usuario -->
      <div class="d-flex align-items-center gap-2 pe-1">
        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center border" style="width: 38px; height: 38px;">
           <img class="img-fluid rounded-circle p-1" src="{{ asset('assets/images/avatar.png') }}" alt="Avatar">
        </div>
        
        <div class="d-none d-sm-flex flex-column justify-content-center">
           <span class="fw-bold text-dark lh-1" style="font-size: 0.9rem;">
              {{ Auth::user()->nombre ?? 'Usuario SAPAC' }}
           </span>
           <small class="text-muted lh-1 mt-1" style="font-size: 0.75rem;">
              {{ Auth::user()->tipoTrabajador->tipo_trabajador ?? 'Trabajador' }}
           </small>
        </div>
      </div>
      
    </div>
  </div>
</nav>

<script src="{{ asset('assets/js/notificaciones.js') }}?v={{ time() }}" defer></script>