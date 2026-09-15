<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../conexion_modulos.php");

/*
=================================================
DOMPDF AUTOLOAD
=================================================
*/
require_once(
    __DIR__ . "/../financiero/comprobantes_pago/dompdf/autoload.inc.php"
);

use Dompdf\Dompdf;

/*
=================================================
FILTROS DE FECHA Y CATEGORÍA
=================================================
*/
$fecha_inicio = $_GET['fecha_inicio'] ?? date("Y-m-01");
$fecha_fin    = $_GET['fecha_fin'] ?? date("Y-m-d");
$categoria_id = $_GET['categoria_id'] ?? "";

/*
=================================================
CONSULTAR DATOS CON CATEGORÍA
=================================================
*/
$sql = "
SELECT 
    d.nombre AS deportista, 
    COALESCE(c.nombre, 'Sin Categoría') AS categoria, 
    a.estado, 
    a.fecha
FROM asistencia a
JOIN deportista d ON d.id = a.deportista_id
LEFT JOIN categoria c ON c.id = d.categoria_id
WHERE a.fecha BETWEEN :fecha_inicio AND :fecha_fin
";

$params = [
    ":fecha_inicio" => $fecha_inicio,
    ":fecha_fin"    => $fecha_fin
];

if (!empty($categoria_id)) {
    $sql .= " AND d.categoria_id = :cat";
    $params[":cat"] = $categoria_id;
}

$sql .= " ORDER BY a.fecha ASC, c.nombre ASC, d.nombre ASC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
=================================================
FORMATEAR FECHAS
=================================================
*/
$inicio_fmt = date("d/m/Y", strtotime($fecha_inicio));
$fin_fmt    = date("d/m/Y", strtotime($fecha_fin));

/*
=================================================
GENERAR TABLA DE DATOS HTML
=================================================
*/
$filas_html = '';
$numero = 1;

foreach ($asistencias as $row) {
    // Definir estilos dinámicos por estado
    $badge_class = 'badge-ausente';
    $estado_txt  = strtoupper($row['estado']);

    if (strtolower($row['estado']) === 'presente') {
        $badge_class = 'badge-presente';
    } elseif (strtolower($row['estado']) === 'tarde') {
        $badge_class = 'badge-tarde';
    }

    $fecha_row_fmt = date("d/m/Y", strtotime($row['fecha']));

    $filas_html .= '
    <tr>
        <td style="text-align: center;">' . $numero++ . '</td>
        <td>' . htmlspecialchars($row['deportista'], ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($row['categoria'], ENT_QUOTES, 'UTF-8') . '</td>
        <td style="text-align: center;">' . $fecha_row_fmt . '</td>
        <td style="text-align: center;">
            <span class="badge ' . $badge_class . '">' . htmlspecialchars($estado_txt, ENT_QUOTES, 'UTF-8') . '</span>
        </td>
    </tr>';
}

if (empty($asistencias)) {
    $filas_html = '
    <tr>
        <td colspan="5" style="text-align: center; padding: 15px;">
            No se encontraron registros de asistencia en el rango seleccionado.
        </td>
    </tr>';
}

/*
=================================================
PLANTILLA HTML DEL PDF (Optimizada para Móvil)
=================================================
*/
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #222222;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #0A4FA3;
            padding-bottom: 8px;
        }
        .titulo {
            font-size: 18px;
            font-weight: bold;
            color: #0A4FA3;
            margin-bottom: 4px;
        }
        .subtitulo {
            font-size: 11px;
            color: #555555;
            font-weight: bold;
        }
        .info {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .info td {
            padding: 5px;
            border: 1px solid #dddddd;
            background: #f9f9f9;
        }
        .info .label {
            font-weight: bold;
            width: 20%;
            background: #f0f0f0;
        }
        table.datos {
            width: 100%;
            border-collapse: collapse;
        }
        table.datos th {
            background: #1f2937;
            color: #ffffff;
            font-weight: bold;
            padding: 6px;
            border: 1px solid #1f2937;
            text-align: left;
        }
        table.datos td {
            padding: 6px;
            border: 1px solid #cccccc;
        }
        .badge {
            display: inline-block;
            padding: 3px 6px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 4px;
            color: #ffffff;
        }
        .badge-presente { background-color: #16a34a; }
        .badge-ausente  { background-color: #dc2626; }
        .badge-tarde    { background-color: #d97706; }
    </style>
</head>
<body>

    <div class="header">
        <div class="titulo">BELLAVISTA FC</div>
        <div class="subtitulo">REPORTE DE ASISTENCIA</div>
    </div>

    <table class="info">
        <tr>
            <td class="label">Rango Fecha:</td>
            <td>' . $inicio_fmt . ' al ' . $fin_fmt . '</td>
            <td class="label">Total Registros:</td>
            <td>' . count($asistencias) . '</td>
        </tr>
    </table>

    <table class="datos">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 35%;">Deportista</th>
                <th style="width: 25%;">Categoría</th>
                <th style="width: 18%; text-align: center;">Fecha</th>
                <th style="width: 17%; text-align: center;">Estado</th>
            </tr>
        </thead>
        <tbody>
            ' . $filas_html . '
        </tbody>
    </table>

</body>
</html>
';

/*
=================================================
GENERAR PDF CON DOMPDF
=================================================
*/
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdf_generado = $dompdf->output();

/*
=================================================
DESCARGA DEL PDF
=================================================
*/
while (ob_get_level() > 0) {
    ob_end_clean();
}

$nombre_pdf = 'Reporte_Asistencia_' . $fecha_inicio . '_al_' . $fecha_fin . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nombre_pdf . '"');
header('Content-Length: ' . strlen($pdf_generado));

echo $pdf_generado;
exit;