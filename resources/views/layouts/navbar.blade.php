
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
      
      <!-- Menú de Notificaciones -->
      <div class="dropdown">
        <button class="icon-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
          <span class="notification-dot"></span>
          <i class="bi bi-bell" aria-hidden="true"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end notification-menu">
          <div class="dropdown-header fw-bold text-body">Notificaciones del Sistema</div>
          <a class="dropdown-item" href="{{ url('/pipas') }}">
            <span class="notification-title">Nueva solicitud de pipa</span>
            <span class="notification-time">Hace 5 minutos</span>
          </a>
          <a class="dropdown-item" href="{{ url('/tomas') }}">
            <span class="notification-title">Alta de toma pendiente</span>
            <span class="notification-time">Hace 30 minutos</span>
          </a>
        </div>
      </div>

     <!-- Divisor vertical opcional para separar las notificaciones del perfil -->
      <div class="vr bg-secondary opacity-25 d-none d-sm-block" style="height: 24px;"></div>

      <!-- Perfil de Usuario (Solo lectura, sin apariencia de botón) -->
      <div class="d-flex align-items-center gap-2 pe-1">
        <!-- Imagen del Avatar (Usando avatar.png como solicitaste) -->
        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center border" style="width: 38px; height: 38px;">
           <img class="img-fluid rounded-circle p-1" src="{{ asset('assets/images/avatar.png') }}" alt="Avatar">
        </div>
        
        <!-- Nombre del Usuario (Oculto en celulares muy pequeños, visible en tablets/PC) -->
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


