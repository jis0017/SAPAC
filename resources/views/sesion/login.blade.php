<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - SAPAC Acatic</title>
    
    <!-- Bootstrap CSS Local -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Iconos de Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Tus Estilos Personalizados (style.css) -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('assets/images/agua-potable.png') }}">
    
    <style>
        /* Ajustes específicos para igualar la plantilla de pantalla dividida */
        .split-left {
            background-color: #2563eb; /* Azul base */
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
        }
        
        .login-card-right {
            max-width: 450px;
            border-radius: 1.5rem; /* Bordes muy redondeados como en la imagen */
        }

        .form-control-custom {
            height: 3.2rem;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
            background-color: #ffffff;
        }

        .form-control-custom:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        
        /* Decoraciones sutiles (Círculos) para reemplazar los cohetes */
        .bg-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="min-vh-100 d-flex flex-column flex-md-row m-0 bg-light">

    <!-- SECCIÓN IZQUIERDA (Azul con logos y texto) -->
    <div class="col-12 col-md-6 col-lg-5 split-left text-white p-4 p-md-5 d-flex flex-column position-relative overflow-hidden min-vh-100">
        
       
        
        <div class="position-relative z-1 d-flex flex-column h-100">
            <!-- Logotipo Superior -->
            <div class="d-flex align-items-center gap-3 mb-5">
                <!-- Se le aplica un filtro blanco al icono del grifo para que resalte en el fondo azul -->
                <img src="{{ asset('assets/images/agua-potable.png') }}" alt="SAPAC" style="width: 45px; ;">
                <h4 class="fw-bold mb-0 text-white tracking-wide">SAPAC</h4>
            </div>

            <!-- Textos Centrales -->
            <div class="mt-auto mb-auto">
                <h1 class="display-4 fw-bold mb-3">Iniciar sesión en</h1>
                <h2 class="h3 fw-semibold mb-4 text-white opacity-75">Sistema de Agua Potable Acatic</h2>
                <p class="opacity-75 fs-6" style="max-width: 420px; line-height: 1.6;">
                    Bienvenido al Sistema de Agua Potable de Acatic.
                    Por favor, introduzca sus credenciales de trabajador para administrar el padrón, tomas de agua y pagos.
                </p>
            </div>

            <!-- Escudo en la parte inferior (Reemplaza los botones de perfiles que son inseguros) -->
            <div class="mt-5 d-flex align-items-center gap-3">
                <img src="{{ asset('assets/images/Acaticblason.png') }}" alt="Escudo de Acatic" class="rounded-3" style="width: 80px; object-fit: contain; box-shadow: 0 10px 20px rgba(0,0,0,0.2);">
                <div>
                    <h6 class="fw-bold mb-0">H. Ayuntamiento de Acatic</h6>
                    <small class="opacity-75">Gobierno Municipal</small>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DERECHA (Blanca con el formulario) -->
    <div class="col-12 col-md-6 col-lg-7 d-flex align-items-center justify-content-center p-4 p-md-5 min-vh-100">
        
        <div class="bg-white p-4 p-md-5 shadow-lg login-card-right w-100">
            
            <div class="mb-5">
                <p class="text-muted mb-1 small">Bienvenido a <span class="text-primary fw-bold">SAPAC</span></p>
                <h2 class="fw-bold text-dark display-6" style="letter-spacing: -1px;">Iniciar sesión</h2>
            </div>

            <!-- Alertas de Error (Contraseñas incorrectas, inactividad, etc.) -->
            @if(session('error'))
                <div class="alert alert-danger d-flex align-items-center mb-4 border-0 shadow-sm rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-3"></i>
                    <div class="small fw-medium">{{ session('error') }}</div>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label for="usuario_trabajador" class="form-label text-secondary small fw-bold">Introduce tu nombre de usuario</label>
                    <input type="text" class="form-control form-control-custom @error('usuario_trabajador') is-invalid @enderror" id="usuario_trabajador" name="usuario_trabajador" value="{{ old('usuario_trabajador') }}" placeholder="Usuario de acceso" required autofocus>
                    @error('usuario_trabajador')
                        <div class="invalid-feedback fw-semibold">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label text-secondary small fw-bold">Introduce tu contraseña</label>
                    <input type="password" class="form-control form-control-custom @error('password') is-invalid @enderror" id="password" name="password" placeholder="Contraseña" required>
                    @error('password')
                        <div class="invalid-feedback fw-semibold">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid mt-5">
                    <button type="submit" class="btn btn-primary fw-bold shadow-sm" style="height: 3.2rem; border-radius: 0.5rem; font-size: 1.05rem;">
                        Iniciar sesión
                    </button>
                </div>
            </form>
            
        </div>
        
    </div>

</body>
</html>