<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo Oficial - {{ $factura->no_factura }}</title>
    <style>
        /* Papel continuo media carta: 21.59cm ancho x 13.97cm alto */
        @page { margin: 0cm; }
        body { 
            font-family: "Courier New", Courier, monospace;
            font-size: 9.5pt; 
            color: #000; 
            margin: 0; 
            padding: 0;
            position: relative;
            text-transform: uppercase;
        }

        /* ============================================================== */
        /* 1. FECHA SUPERIOR (DÍA, MES, AÑO)                              */
        /* ============================================================== */
        .pos-dia  { position: absolute; top: 3.15cm; left: 4.2cm; font-weight: bold; width: 1.5cm; text-align: center; }
        .pos-mes  { position: absolute; top: 3.15cm; left: 6.1cm; font-weight: bold; width: 3.0cm; text-align: center; }
        .pos-anio { position: absolute; top: 3.15cm; left: 9.4cm; font-weight: bold; width: 1.8cm; text-align: center; }

        /* ============================================================== */
        /* 2. DATOS DE UBICACIÓN Y CUENTA (BLOQUE IZQUIERDO SUPERIOR)     */
        /* ============================================================== */
        .pos-localidad { position: absolute; top: 4.05cm; left: 4.6cm; width: 6.8cm; font-size: 9pt; }
        .pos-municipio { position: absolute; top: 4.55cm; left: 4.6cm; width: 6.8cm; font-size: 9pt; }
        .pos-cuenta    { position: absolute; top: 5.08cm; left: 4.6cm; width: 6.8cm; font-weight: bold; font-size: 10.5pt; }

        /* ============================================================== */
        /* 3. DATOS DEL CONTRIBUYENTE (BLOQUE IZQUIERDO MEDIO)            */
        /* ============================================================== */
        .pos-nombre    { position: absolute; top: 5.85cm; left: 4.6cm; width: 6.8cm; font-size: 9pt; font-weight: bold; }
        .pos-meses     { position: absolute; top: 6.38cm; left: 4.6cm; width: 6.8cm; font-size: 8.5pt; }
        .pos-domicilio { position: absolute; top: 6.90cm; left: 4.6cm; width: 6.8cm; font-size: 8.5pt; }

        /* ============================================================== */
        /* 4. MEDIDOR Y CUOTA MENSUAL (BLOQUE INFERIOR IZQUIERDO)         */
        /* ============================================================== */
        .pos-cuota-mensual    { position: absolute; top: 9.25cm;  left: 6.9cm; width: 4.3cm; text-align: center; font-weight: bold; }
        .pos-lectura-mensual  { position: absolute; top: 9.77cm;  left: 6.9cm; width: 4.3cm; text-align: center; }
        .pos-lectura-anterior { position: absolute; top: 10.29cm; left: 6.9cm; width: 4.3cm; text-align: center; }
        .pos-consumo          { position: absolute; top: 10.81cm; left: 6.9cm; width: 4.3cm; text-align: center; font-weight: bold; }

        /* ============================================================== */
        /* 5. POR CONCEPTO DE: (DESGLOSE LADO DERECHO)                    */
        /* Columna Concepto: left 12.3cm | Columna Importe: left 17.6cm   */
        /* ============================================================== */
        .contenedor-conceptos {
            position: absolute;
            top: 3.15cm;
            left: 12.3cm;
            width: 8.1cm;
        }
        .fila-concepto {
            width: 100%;
            height: 0.50cm;
            line-height: 0.50cm;
            font-size: 8.5pt;
            clear: both;
        }
        .col-concepto-texto {
            float: left;
            width: 5.2cm;
            overflow: hidden;
            white-space: nowrap;
        }
        .col-concepto-monto {
            float: right;
            width: 2.7cm;
            text-align: right;
            font-weight: bold;
        }

        /* ============================================================== */
        /* 6. SUB-TOTAL, I.V.A., TOTAL Y OBSERVACIONES (INFERIOR DERECHO) */
        /* ============================================================== */
        .pos-subtotal      { position: absolute; top: 9.77cm;  left: 17.6cm; width: 2.8cm; text-align: right; font-size: 9.5pt; }
        .pos-iva           { position: absolute; top: 10.29cm; left: 17.6cm; width: 2.8cm; text-align: right; font-size: 9.5pt; }
        .pos-total         { position: absolute; top: 10.81cm; left: 17.6cm; width: 2.8cm; text-align: right; font-size: 11pt; font-weight: bold; }

        .pos-observaciones { position: absolute; top: 11.55cm; left: 13.5cm; width: 6.9cm; font-size: 8pt; line-height: 1.1; }
    </style>
</head>
<body>
    @php
        $nombreCompleto = trim(($toma->usuario->razon_social ?? '') . ' ' . ($toma->usuario->apellidos ?? ''));
        if (empty($nombreCompleto)) {
            $nombreCompleto = 'PÚBLICO EN GENERAL';
        }

        $domicilioCompleto = trim($toma->calle . ' #' . $toma->numero_exterior . ($toma->numero_interior ? ' INT. ' . $toma->numero_interior : ''));

        // Formato de fechas del periodo pagado
        if (!empty($factura->fecha_lectura_anterior) && !empty($factura->fecha_lectura_actual)) {
            $fAnt = \Carbon\Carbon::parse($factura->fecha_lectura_anterior)->format('m/Y');
            $fAct = \Carbon\Carbon::parse($factura->fecha_lectura_actual)->format('m/Y');
            $textoMeses = ($fAnt === $fAct) ? $fAnt . ' (' . $factura->periodo_mes . ')' : "DEL {$fAnt} AL {$fAct} ({$factura->periodo_mes})";
        } else {
            $textoMeses = $factura->periodo_mes . ' ' . $factura->periodo_anio;
        }

        // Cálculo de cuota mensual informativa
        $cuotaMensualMostrar = isset($cuotaPeriodo) && $cuotaPeriodo ? $cuotaPeriodo->cuota_mensual : ($factura->cuota_base_agua / 12);
        $descuentoTotal = ($factura->descuento_social ?? 0) + ($factura->descuento_pronto_pago ?? 0);
    @endphp

    <!-- 1. FECHA -->
    <div class="pos-dia">{{ $fecha_pago->format('d') }}</div>
    <div class="pos-mes">{{ strtoupper($fecha_pago->translatedFormat('F')) }}</div>
    <div class="pos-anio">{{ $fecha_pago->format('Y') }}</div>

    <!-- 2. LOCALIDAD, MUNICIPIO Y CUENTA -->
    <div class="pos-localidad">{{ substr($localidadNombre ?? 'ACATIC (CABECERA)', 0, 32) }}</div>
    <div class="pos-municipio">ACATIC, JALISCO</div>
    <div class="pos-cuenta">{{ $toma->id_toma }}</div>

    <!-- 3. CONTRIBUYENTE, MESES Y DOMICILIO -->
    <div class="pos-nombre">{{ substr($nombreCompleto, 0, 36) }}</div>
    <div class="pos-meses">{{ substr($textoMeses, 0, 38) }}</div>
    <div class="pos-domicilio">{{ substr($domicilioCompleto, 0, 38) }}</div>

    <!-- 4. CUOTA MENSUAL Y LECTURAS -->
    <div class="pos-cuota-mensual">${{ number_format($cuotaMensualMostrar, 2) }}</div>
    <div class="pos-lectura-mensual">{{ $toma->calculo_fijo == 0 ? ($factura->lectura_actual ?? 0) : 'CUOTA FIJA' }}</div>
    <div class="pos-lectura-anterior">{{ $toma->calculo_fijo == 0 ? ($factura->lectura_anterior ?? 0) : '-' }}</div>
    <div class="pos-consumo">{{ $toma->calculo_fijo == 0 ? ($factura->consumo ?? 0) . ' M3' : '-' }}</div>

    <!-- 5. DESGLOSE DE CONCEPTOS E IMPORTES (POR CONCEPTO DE:) -->
    <div class="contenedor-conceptos">
        @if($factura->es_solo_contrato == 1)
            <div class="fila-concepto">
                <span class="col-concepto-texto">CONTRATO NUEVA TOMA</span>
                <span class="col-concepto-monto">${{ number_format($factura->cuota_base_agua, 2) }}</span>
            </div>
        @elseif($factura->cuota_base_agua > 0)
            <div class="fila-concepto">
                <span class="col-concepto-texto">SERVICIO DE AGUA ({{ $factura->periodo_mes }})</span>
                <span class="col-concepto-monto">${{ number_format($factura->cuota_base_agua, 2) }}</span>
            </div>
        @endif

        @if($factura->saneamiento > 0)
            <div class="fila-concepto">
                <span class="col-concepto-texto">SANEAMIENTO</span>
                <span class="col-concepto-monto">${{ number_format($factura->saneamiento, 2) }}</span>
            </div>
        @endif

        @if($factura->infraestructura > 0)
            <div class="fila-concepto">
                <span class="col-concepto-texto">INFRAESTRUCTURA</span>
                <span class="col-concepto-monto">${{ number_format($factura->infraestructura, 2) }}</span>
            </div>
        @endif

        @if($factura->reconexion > 0)
            <div class="fila-concepto">
                <span class="col-concepto-texto">RECONEXIÓN DE SERVICIO</span>
                <span class="col-concepto-monto">${{ number_format($factura->reconexion, 2) }}</span>
            </div>
        @endif

        @if($factura->rezagos > 0)
            <div class="fila-concepto">
                <span class="col-concepto-texto">REZAGOS ACUMULADOS</span>
                <span class="col-concepto-monto">${{ number_format($factura->rezagos, 2) }}</span>
            </div>
        @endif

        @if($factura->recargos > 0)
            <div class="fila-concepto">
                <span class="col-concepto-texto">RECARGOS</span>
                <span class="col-concepto-monto">${{ number_format($factura->recargos, 2) }}</span>
            </div>
        @endif

        @if($factura->sancion > 0)
            <div class="fila-concepto">
                <span class="col-concepto-texto">SANCIÓN / MULTA</span>
                <span class="col-concepto-monto">${{ number_format($factura->sancion, 2) }}</span>
            </div>
        @endif

        @if($descuentoTotal > 0)
            <div class="fila-concepto">
                <span class="col-concepto-texto">DESCUENTO ({{ $factura->motivo_condonacion ?? 'LEY' }})</span>
                <span class="col-concepto-monto">-${{ number_format($descuentoTotal, 2) }}</span>
            </div>
        @endif

        @if($factura->bonificacion > 0)
            <div class="fila-concepto">
                <span class="col-concepto-texto">CONDONACIÓN AUTORIZADA</span>
                <span class="col-concepto-monto">-${{ number_format($factura->bonificacion, 2) }}</span>
            </div>
        @endif
    </div>

    <!-- 6. SUB-TOTAL, I.V.A., TOTAL Y OBSERVACIONES -->
    <div class="pos-subtotal">${{ number_format($factura->subtotal ?? $factura->total_factura, 2) }}</div>
    <div class="pos-iva">${{ number_format($factura->iva ?? 0, 2) }}</div>
    <div class="pos-total">${{ number_format($factura->total_factura, 2) }}</div>

    <div class="pos-observaciones">
        {{ $factura->motivo_condonacion ?? '' }}
    </div>

</body>
</html>