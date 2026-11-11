<!DOCTYPE html>
<html lang="es" data-theme="light" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="description" content="Sistema de Administración de Agua Potable y Saneamiento - H. Ayuntamiento de Acatic">
  <title>@yield('title', 'Agua Potable Acatic') | adminHMD</title>

  <!-- SCRIPT DE BLOQUEO DE MODO OSCURO (Debe ir antes de cualquier CSS) -->
  <script>
    (function() {
      // Forzar de manera preventiva el almacenamiento local a modo claro
      try {
        localStorage.setItem('sapac_theme', 'light');
        localStorage.removeItem('theme');
        localStorage.removeItem('dark-mode');
      } catch (e) {}

      // Asignar los atributos obligatorios a la raíz del documento
      document.documentElement.setAttribute('data-theme', 'light');
      document.documentElement.setAttribute('data-bs-theme', 'light');
    })();
  </script>

  <!-- Estilos CSS de la plantilla adminHMD -->
  <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

  <!-- REGLAS CSS CONTUNDENTES PARA EVITAR CUALQUIER FONDO OSCURO -->
  <style>
    html, body, [data-theme="dark"], [data-bs-theme="dark"] {
      background-color: #f5f7fb !important;
      color: #1f2937 !important;
    }
    .admin-navbar, .metric-card, .panel, .auth-card, .error-card, .dropdown-menu, .modal-content, .card {
      background-color: #ffffff !important;
      color: #1f2937 !important;
    }
  </style>

  <!-- Espacio para CSS adicional desde otras vistas -->
  @stack('styles')
</head>

<body>
  <div class="admin-shell">
    <div class="sidebar-backdrop" data-sidebar-close></div>

    <!-- Menú Lateral (Sidebar) -->
    @include('layouts.sidebar')

    <div class="admin-main">
      <!-- Barra de Navegación Superior (Navbar) -->
      @include('layouts.navbar')

      <!-- Área de Contenido Principal -->
      <main class="dashboard-content">
        <div class="container-fluid px-3 px-lg-4 py-4">
          @yield('content')
        </div>
      </main>

      <!-- Pie de Página (Footer) -->
      <footer class="admin-footer">
        <div class="container-fluid px-3 px-lg-4 d-flex justify-content-between align-items-center">
          <span>Copyright &copy; {{ date('Y') }} <strong>H. Ayuntamiento de Acatic</strong>. Todos los derechos reservados.</span>
          <span class="text-muted small">Dirección de Agua Potable y Saneamiento</span>
        </div>
      </footer>
    </div>
  </div>

  <!-- Scripts JavaScript de la plantilla adminHMD -->
  <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>
  <!-- Cargar JS principal de la plantilla -->
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('assets/js/navbar.js') }}"></script> 

<!-- Cargar JS del buscador global -->
<script src="{{ asset('assets/js/buscador_global.js') }}"></script>

  <!-- SCRIPT AUXILIAR: Neutralizar el botón de cambio de tema si interfiere -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      document.documentElement.setAttribute('data-theme', 'light');
      document.documentElement.setAttribute('data-bs-theme', 'light');
      
      const themeToggleBtn = document.getElementById('themeToggle');
      if (themeToggleBtn) {
        // Ocultar el botón para evitar que el usuario vuelva a activar el modo oscuro por error
        themeToggleBtn.style.display = 'none';
      }
    });
  </script>

  <!-- Espacio para Scripts JS adicionales desde otras vistas -->
  @stack('scripts')
</body>
</html>