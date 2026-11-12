<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Corte - SAPAC Acatic</title>
    <style>
        @page {
            size: letter {{ $tipoReporte === 'desglosado' ? 'landscape' : 'portrait' }};
            margin: 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #111;
            margin: {{ !empty($modoImpresionDirecta) ? '60px 25px 25px 25px' : '0' }};
            background-color: #fff;
        }
        /* Barra de herramientas estilo Report Preview para reportes masivos */
        .barra-preview {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 48px;
            background-color: #0f172a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.25);
            z-index: 9999;
        }
        .btn-imprimir-pdf {
            background-color: #2563eb;
            color: #fff;
            border: none;
            padding: 7px 16px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
        }
        .btn-imprimir-pdf:hover {
            background-color: #1d4ed8;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0 !important;
            }
        }
        .header-container {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 8px;
        }
        .institucion {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }
        .titulo-reporte {
            font-size: 13px;
            font-weight: bold;
            margin: 3px 0;
        }
        .operador {
            font-size: 11px;
            font-weight: bold;
            margin: 2px 0;
        }
        .periodo {
            font-size: 10px;
            margin-top: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 14px;
        }
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 3.5px 4px;
            text-align: left;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        thead th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            color: #0f172a;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .fila-total th, .fila-total td {
            background-color: #e2e8f0;
            font-weight: bold;
            font-size: 10px;
            border-top: 2px solid #0f172a;
        }
        .resumen-grid {
            width: 100%;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .resumen-grid td {
            vertical-align: top;
            border: none;
            padding: 0;
            white-space: normal;
        }
        .caja-resumen {
            border: 1px solid #94a3b8;
            padding: 8px;
            background-color: #f8fafc;
        }
        .caja-resumen h4 {
            margin: 0 0 6px 0;
            font-size: 10.5px;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }
        .firmas {
            margin-top: 35px;
            width: 100%;
            text-align: center;
            page-break-inside: avoid;
        }
        .firmas td {
            border: none;
            width: 50%;
            padding-top: 30px;
        }
        .linea-firma {
            border-top: 1px solid #000;
            width: 210px;
            margin: 0 auto 4px auto;
        }
    </style>
</head>
<body>

    @if(!empty($modoImpresionDirecta))
        <div class="barra-preview no-print">
            <div>
                <strong>VISTA PREVIA DE REPORTE MASIVO ({{ number_format($totales['cantidad_recibos']) }} recibos encontrados)</strong>
                <span style="font-size: 11px; color: #cbd5e1; margin-left: 10px;">
                    Selecciona "Guardar como PDF" en el destino de impresión para descargar el archivo.
                </span>
            </div>
            <div>
                <button type="button" class="btn-imprimir-pdf" onclick="window.print()">
                    Guardar como PDF / Imprimir
                </button>
            </div>
        </div>
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() { window.print(); }, 400);
            });
        </script>
    @endif

    <div class="header-container">
        <div class="institucion">SISTEMA DE AGUA POTABLE, ALCANTARILLADO Y SANEAMIENTO DE ACATIC, JALISCO (SAPAC)</div>
        <div class="titulo-reporte">Reporte de Corte por Rango de Fechas ({{ strtoupper($tipoReporte) }})</div>
        <div class="operador">{{ $nombreOperador }}</div>
        <div class="periodo">
            {{ $periodoTexto }} &nbsp;|&nbsp; Forma de Pago: <strong>{{ $formaFiltro }}</strong> &nbsp;|&nbsp; Impreso: {{ $fechaImpresion }}
        </div>
    </div>

    @if($tipoReporte === 'desglosado')
        <table>
            <thead>
                <tr>
                    <th class="text-center" width="7%">Fecha</th>
                    <th class="text-center" width="7%">Recibo</th>
                    <th class="text-center" width="5%">Toma</th>
                    <th width="24%">Contribuyente / Domicilio</th>
                    <th class="text-center" width="9%">Periodo</th>
                    <th class="text-end" width="8%">Agua</th>
                    <th class="text-end" width="8%">Saneam.</th>
                    <th class="text-end" width="7%">Infraest.</th>
                    <th class="text-end" width="7%">Rec/Rez</th>
                    <th class="text-end" width="7%">Descto.</th>
                    <th class="text-center" width="4%">Pago</th>
                    <th class="text-end" width="7%">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registros as $r)
                    <tr>
                        <td class="text-center">{{ substr($r->fecha_pago, 8, 2) }}/{{ substr($r->fecha_pago, 5, 2) }}/{{ substr($r->fecha_pago, 0, 4) }}</td>
                        <td class="text-center fw-bold">{{ $r->no_factura }}</td>
                        <td class="text-center">#{{ $r->toma_id }}</td>
                        <td>{{ trim(($r->razon_social ?? '') . ' ' . ($r->apellidos ?? '')) ?: 'PÚBLICO EN GENERAL' }} ({{ $r->calle }} #{{ $r->numero_exterior }})</td>
                        <td class="text-center">{{ $r->periodo_anio }} ({{ $r->periodo_mes }})</td>
                        <td class="text-end">${{ number_format($r->cuota_base_agua, 2) }}</td>
                        <td class="text-end">${{ number_format($r->saneamiento, 2) }}</td>
                        <td class="text-end">${{ number_format($r->infraestructura, 2) }}</td>
                        <td class="text-end">${{ number_format($r->otros_cargos, 2) }}</td>
                        <td class="text-end">-${{ number_format($r->descuento_fila, 2) }}</td>
                        <td class="text-center">{{ substr($r->forma_pago, 0, 4) }}</td>
                        <td class="text-end fw-bold">${{ number_format($r->monto_pagado, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center" style="padding: 20px;">
                            No se encontraron cobros registrados en el rango de fechas seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($registros->isNotEmpty())
            <tfoot>
                <tr class="fila-total">
                    <td colspan="5" class="text-end">TOTALES GENERALES ({{ number_format($totales['cantidad_recibos']) }} RECIBOS):</td>
                    <td class="text-end">${{ number_format($totales['agua'], 2) }}</td>
                    <td class="text-end">${{ number_format($totales['saneamiento'], 2) }}</td>
                    <td class="text-end">${{ number_format($totales['infraestructura'], 2) }}</td>
                    <td class="text-end">${{ number_format($totales['reconexion'] + $totales['rezagos'] + $totales['recargos'] + $totales['sancion'], 2) }}</td>
                    <td class="text-end">-${{ number_format($totales['descuentos'], 2) }}</td>
                    <td class="text-center">--</td>
                    <td class="text-end">${{ number_format($totales['gran_total'], 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th class="text-center" width="12%">Fecha de Cobro</th>
                    <th class="text-center" width="10%">Recibos</th>
                    <th class="text-end" width="13%">Agua Base</th>
                    <th class="text-end" width="13%">Saneamiento</th>
                    <th class="text-end" width="13%">Infraestructura</th>
                    <th class="text-end" width="13%">Rez/Rec/Recon.</th>
                    <th class="text-end" width="12%">Descuentos</th>
                    <th class="text-end" width="14%">Total del Día</th>
                </tr>
            </thead>
            <tbody>
                @forelse($concentradoPorDia as $dia)
                    <tr>
                        <td class="text-center fw-bold">{{ $dia['fecha'] }}</td>
                        <td class="text-center">{{ number_format($dia['recibos']) }}</td>
                        <td class="text-end">${{ number_format($dia['agua'], 2) }}</td>
                        <td class="text-end">${{ number_format($dia['saneamiento'], 2) }}</td>
                        <td class="text-end">${{ number_format($dia['infraestructura'], 2) }}</td>
                        <td class="text-end">${{ number_format($dia['accesorios'], 2) }}</td>
                        <td class="text-end">-${{ number_format($dia['descuentos'], 2) }}</td>
                        <td class="text-end fw-bold">${{ number_format($dia['total_dia'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 20px;">
                            No existen movimientos registrados en este periodo.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($concentradoPorDia->isNotEmpty())
            <tfoot>
                <tr class="fila-total">
                    <td class="text-end">TOTAL DEL PERIODO:</td>
                    <td class="text-center">{{ number_format($totales['cantidad_recibos']) }}</td>
                    <td class="text-end">${{ number_format($totales['agua'], 2) }}</td>
                    <td class="text-end">${{ number_format($totales['saneamiento'], 2) }}</td>
                    <td class="text-end">${{ number_format($totales['infraestructura'], 2) }}</td>
                    <td class="text-end">${{ number_format($totales['accesorios'], 2) }}</td>
                    <td class="text-end">${{ number_format($totales['reconexion'] + $totales['rezagos'] + $totales['recargos'] + $totales['sancion'], 2) }}</td>
                    <td class="text-end">-${{ number_format($totales['descuentos'], 2) }}</td>
                    <td class="text-end">${{ number_format($totales['gran_total'], 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    @endif

    <!-- CUADROS DE ARQUEO Y DESGLOSE POR FORMA DE PAGO Y RUBROS -->
    <table class="resumen-grid">
        <tr>
            <td width="48%" style="padding-right: 10px;">
                <div class="caja-resumen">
                    <h4>Desglose por Rubro Contable</h4>
                    <table>
                        <tr><td width="65%">Cuota Base / Agua Potable:</td><td width="35%" class="text-end fw-bold">${{ number_format($totales['agua'], 2) }}</td></tr>
                        <tr><td>Saneamiento (25%):</td><td class="text-end fw-bold">${{ number_format($totales['saneamiento'], 2) }}</td></tr>
                        <tr><td>Infraestructura (5%):</td><td class="text-end fw-bold">${{ number_format($totales['infraestructura'], 2) }}</td></tr>
                        <tr><td>Reconexiones:</td><td class="text-end fw-bold">${{ number_format($totales['reconexion'], 2) }}</td></tr>
                        <tr><td>Rezagos / Recargos / Multas:</td><td class="text-end fw-bold">${{ number_format($totales['rezagos'] + $totales['recargos'] + $totales['sancion'], 2) }}</td></tr>
                        <tr><td>Descuentos y Condonaciones:</td><td class="text-end fw-bold">-${{ number_format($totales['descuentos'], 2) }}</td></tr>
                    </table>
                </div>
            </td>
            <td width="48%" style="padding-left: 10px;">
                <div class="caja-resumen">
                    <h4>Arqueo por Forma de Pago</h4>
                    <table>
                        <tr><td width="65%">Total en Efectivo:</td><td width="35%" class="text-end fw-bold">${{ number_format($totales['efectivo'], 2) }}</td></tr>
                        <tr><td>Total en Tarjeta Débito / Crédito:</td><td class="text-end fw-bold">${{ number_format($totales['tarjeta'], 2) }}</td></tr>
                        <tr><td>Total en Transferencia Bancaria:</td><td class="text-end fw-bold">${{ number_format($totales['transferencia'], 2) }}</td></tr>
                        <tr><td>Recibos Válidos Procesados:</td><td class="text-end fw-bold">{{ number_format($totales['cantidad_recibos']) }}</td></tr>
                        <tr class="fila-total">
                            <td>GRAN TOTAL RECAUDADO:</td>
                            <td class="text-end">${{ number_format($totales['gran_total'], 2) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea-firma"></div>
                <strong>{{ $nombreOperador }}</strong><br>
                Elaboró / Entrega Corte de Caja
            </td>
            <td>
                <div class="linea-firma"></div>
                <strong>HACIENDA MUNICIPAL / DIRECCIÓN SAPAC</strong><br>
                Recibe y Valida
            </td>
        </tr>
    </table>

</body>
</html>