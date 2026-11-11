<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo Excedente</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 13px; 
            color: #000; 
            margin: 40px; 
        }
        .header { 
            text-align: center; 
            font-weight: bold; 
            margin-bottom: 30px; 
            line-height: 1.6; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 30px; 
        }
        th, td { 
            border: 1px solid #000; 
            padding: 8px 12px; 
            text-align: left; 
        }
        .bg-light { 
            background-color: #f4f4f4; 
        }
        .notice-box { 
            border: 1px solid #000; 
            font-size: 12px; 
            margin-bottom: 50px; 
            width: 70%;
        }
        .notice-title { 
            font-weight: bold; 
            padding: 5px 10px; 
            border-bottom: 1px solid #000; 
        }
        .notice-body { 
            padding: 8px 10px; 
            line-height: 1.4;
        }
        .footer { 
            text-align: center; 
            margin-top: 60px; 
            font-size: 13px; 
            line-height: 1.6; 
        }
        .seal-space { 
            height: 120px; 
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            margin: 0 auto;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <div class="header">
        DIRECCIÓN MUNICIPAL DE AGUA POTABLE, ALCANTARILLADO DE<br>
        ACATIC, JALISCO.<br><br>
        EXCEDENTE DEL MES DE {{ $mes_nombre }} DEL {{ $anio }}
    </div>

    <table>
        <tr>
            <td width="40%" class="bg-light">NOMBRE</td>
            <td class="bg-light">{{ $nombre }}</td>
        </tr>
        <tr>
            <td>LECTURA ANTERIOR</td>
            <td>{{ $lectura_anterior }}</td>
        </tr>
        <tr>
            <td>LECTURA ACTUAL</td>
            <td>{{ $lectura_actual }}</td>
        </tr>
        <tr>
            <td>CONSUMO</td>
            <td>{{ $consumo }} M³</td>
        </tr>
        <tr>
            <td>CUOTA FIJA</td>
            <td>${{ number_format($cuota_fija, 2) }}</td>
        </tr>
        <tr>
            <td>SANEAMIENTO</td>
            <td>${{ number_format($saneamiento, 2) }} (25. %)</td>
        </tr>
        <tr>
            <td>OTROS USOS</td>
            <td>${{ number_format($otros_usos, 2) }}</td>
        </tr>
        <tr>
            <td class="bg-light" style="font-weight: bold;">TOTAL</td>
            <td class="bg-light" style="font-weight: bold;">${{ number_format($total, 2) }}</td>
        </tr>
    </table>

    <div class="notice-box">
        <div class="notice-title">COBROS DE EXCEDENTES DE TEQUILILLA</div>
        <div class="notice-body">
            Se le notifica que tiene un plazo de 10 días para pasar a las oficinas de<br>
            Agua Potable a cubrir su excedente.
        </div>
    </div>

    <div class="footer">
        ATENTAMENTE<br><br>
        ACATIC, {{ date('d') }} DE {{ strtoupper(date('F')) }} DEL {{ date('Y') }}<br><br>
        AGUA POTABLE Y ALCANTARILLADO DE ACATIC, JALISCO
        
        <div class="seal-space"></div>
        
        DIRECCIÓN<br>
        MUNICIPAL
    </div>

</body>
</html>