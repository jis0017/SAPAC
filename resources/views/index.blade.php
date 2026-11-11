@extends('layouts.app')

@section('title', 'Panel Principal')

@section('content')
<div class="page-heading mb-4">
  <div class="page-heading-copy">
    <span class="page-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
    <div>
      <p class="eyebrow mb-1">Resumen Operativo</p>
      <h1 class="h3 mb-1">Panel de Agua Potable</h1>
      <p class="text-muted mb-0">Gestión de tomas, solicitudes de pipas y monitoreo de pozos en Acatic.</p>
    </div>
  </div>
</div>

<!-- TARJETAS DE INDICADORES (KPIs DINÁMICOS) -->
<section class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="card shadow-sm border-0 rounded-4 p-3 h-100">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3 class="h6 text-muted mb-1">Tomas Registradas</h3>
          <h2 class="display-6 fw-bold mb-0 text-primary">{{ number_format($totalTomas) }}</h2>
        </div>
        <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
          <i class="bi bi-house-door fs-3"></i>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-12 col-md-4">
    <div class="card shadow-sm border-0 rounded-4 p-3 h-100">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3 class="h6 text-muted mb-1">Pipas Pendientes</h3>
          <h2 class="display-6 fw-bold mb-0 text-warning">{{ $pipasPendientes }}</h2>
        </div>
        <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
          <i class="bi bi-truck fs-3"></i>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-12 col-md-4">
    <div class="card shadow-sm border-0 rounded-4 p-3 h-100">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3 class="h6 text-muted mb-1">Pozos Operativos</h3>
          <h2 class="display-6 fw-bold mb-0 text-success">{{ $pozosActivos }}</h2>
        </div>
        <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
          <i class="bi bi-droplet-half fs-3"></i>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ZONA DE GRÁFICOS -->
<section class="row g-3">
    <!-- Gráfico 1: Tipos de Tomas -->
    <div class="col-12 col-md-6">
        <div class="card shadow-sm border-0 rounded-4 p-4">
            <h5 class="fw-bold text-dark mb-4">Distribución del Padrón</h5>
            <div style="height: 250px; position: relative;">
                <canvas id="graficoTomas"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico 2: Pedidos de Pipas del Mes -->
    <div class="col-12 col-md-6">
        <div class="card shadow-sm border-0 rounded-4 p-4">
            <h5 class="fw-bold text-dark mb-4">Estado de Pipas (Mes Actual)</h5>
            <div style="height: 250px; position: relative;">
                <canvas id="graficoPipas"></canvas>
            </div>
        </div>
    </div>
</section>

<!-- Importar Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // Configuración Gráfico 1: Tipos de Tomas (Doughnut)
    const ctxTomas = document.getElementById('graficoTomas').getContext('2d');
    new Chart(ctxTomas, {
        type: 'doughnut',
        data: {
            labels: ['Cuota Fija', 'Servicio Medido'],
            datasets: [{
                data: [{{ $tomasFijas }}, {{ $tomasMedidas }}],
                backgroundColor: ['#0d6efd', '#0dcaf0'], // Azul primario y Cyan
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Configuración Gráfico 2: Estado de Pipas (Bar)
    const ctxPipas = document.getElementById('graficoPipas').getContext('2d');
    new Chart(ctxPipas, {
        type: 'bar',
        data: {
            labels: ['Entregadas', 'Pendientes', 'Canceladas'],
            datasets: [{
                label: 'Cantidad de Servicios',
                data: [{{ $pipasEntregadas }}, {{ $pipasPendientes }}, {{ $pipasCanceladas }}],
                backgroundColor: ['#198754', '#ffc107', '#dc3545'], // Verde, Amarillo, Rojo
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false } // Ocultamos la leyenda superior
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
});
</script>
@endsection