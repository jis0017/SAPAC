<aside class="admin-sidebar d-flex flex-column" id="adminSidebar" aria-label="Main navigation">
  <!-- Cabecera del Menú -->
  <div class="sidebar-header">
    <a class="brand-mark" href="{{ url('/dashboard') }}" aria-label="Acatic Agua dashboard">
      <span class="brand-icon">
        <img src="{{ asset('assets/images/agua-potable.png') }}" alt="Logo Agua Potable Acatic" style="width: 28px; height: 28px; object-fit: contain;">
      </span> 
      <span class="brand-copy">
        <span class="brand-title">SAPAC</span>
        <span class="brand-subtitle">Panel Web</span>
      </span>
    </a>
  </div>

  <!-- Contenedor de Navegación con Scroll Invisible -->
  <nav class="sidebar-nav flex-grow-1 overflow-y-auto" style="scrollbar-width: none; -ms-overflow-style: none;">
    <!-- Oculta scrollbar en navegadores WebKit (Chrome, Safari, Edge) -->
    <style>
      .sidebar-nav::-webkit-scrollbar {
        display: none;
      }
    </style>
    
    <!-- Módulo General (Visible para Todos) -->
    <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ url('/dashboard') }}">
      <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
      <span class="nav-text">Dashboard</span>
    </a>

    <!-- Módulos Administrativos (Ingeniero y Secretaria) -->
    @if(Auth::user()->tieneRol('Ingeniero') || Auth::user()->tieneRol('Secretaria'))
      <a class="nav-link {{ request()->is('usuarios*') ? 'active' : '' }}" href="{{ url('/usuarios') }}">
        <span class="nav-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
        <span class="nav-text">Usuarios y Padrón</span>
      </a>

      <a class="nav-link {{ request()->routeIs('tomas.*') ? 'active' : '' }}" href="{{ route('tomas.index') }}">
        <span class="nav-icon"><i class="bi bi-droplet" aria-hidden="true"></i></span>
        <span class="nav-text">Tomas de Agua</span>
      </a>

      <a class="nav-link {{ request()->is('pagos*') ? 'active' : '' }}" href="{{ url('/pagos') }}">
        <span class="nav-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
        <span class="nav-text">Pagos - Facturación</span>
      </a>

      <a class="nav-link {{ request()->is('pipas*') || request()->routeIs('pedidos.pipas') ? 'active' : '' }}" href="{{ route('pedidos.pipas') }}">
        <span class="nav-icon"><i class="bi bi-truck" aria-hidden="true"></i></span>
        <span class="nav-text">Pedidos de Pipas</span>
      </a>
    @endif

    <!-- Módulo de Configuración (Exclusivo Ingeniero) -->
    @if(Auth::user()->tieneRol('Ingeniero'))
      <a class="nav-link {{ request()->is('configuracion*') ? 'active' : '' }}" href="{{ url('/configuracion') }}">
        <span class="nav-icon"><i class="bi bi-sliders" aria-hidden="true"></i></span>
        <span class="nav-text">Tarifas Configuración</span>
      </a>

      <a class="nav-link {{ request()->is('lecturas-medidor*') ? 'active' : '' }}" href="{{ route('calculos_medidor.index') }}">
        <span class="nav-icon"><i class="bi bi-calculator" aria-hidden="true"></i></span>
        <span class="nav-text">Cálculos de Medidores</span>
      </a>

      <a class="nav-link {{ request()->is('trabajadores*') ? 'active' : '' }}" href="{{ route('trabajadores.trabajadores') }}">
        <span class="nav-icon"><i class="bi bi-person-workspace" aria-hidden="true"></i></span>
        <span class="nav-text">Trabajadores</span>
      </a>
    @endif

    <!-- Módulo de Campo (Ingeniero y Pipero) -->
    @if(Auth::user()->tieneRol('Ingeniero') || Auth::user()->tieneRol('Pipero'))
      <a class="nav-link {{ request()->is('pozos*') ? 'active' : '' }}" href="{{ route('pozos.pozos') }}">
        <span class="nav-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
        <span class="nav-text">Mapa de Pozos</span>
      </a>
    @endif
  </nav>

  <!-- Sección Fija Inferior: Botón de Cerrar Sesión Seguro y Limpio -->
  <div class="mt-auto p-3 border-top" style="border-color: var(--admin-sidebar-border) !important;">
    <form action="{{ route('logout') }}" method="POST" class="m-0">
      @csrf
      <button type="submit" class="nav-link w-100 d-flex align-items-center gap-3 bg-transparent border-0 text-start text-danger" style="padding: 0.78rem 0.9rem; border-radius: 8px;">
        <!-- Nuevo ícono de logout usando la imagen que compartiste -->
        <span class="nav-icon bg-danger bg-opacity-10 shadow-sm d-flex align-items-center justify-content-center">
            <img src="{{ asset('assets/images/cerrar-sesion.png') }}" alt="Salir" style="width: 16px; height: 16px; filter: invert(34%) sepia(86%) saturate(2203%) hue-rotate(334deg) brightness(97%) contrast(92%);">
        </span>
        <span class="nav-text fw-bold">Cerrar Sesión</span>
      </button>
    </form>
  </div>
</aside>