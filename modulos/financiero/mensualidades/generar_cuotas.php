<?php

/*
===========================================================
GENERAR CUOTAS MENSUALES
MÓDULO FINANCIERO - BELLAVISTA FC
===========================================================
*/


/*
===========================================================
1. INICIAR SESIÓN
===========================================================
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
===========================================================
2. CARGAR CONFIGURACIÓN Y CONEXIÓN
===========================================================
*/

require_once(__DIR__ . "/../../../includes/config.php");
require_once(__DIR__ . "/../../../includes/conexion.php");


/*
===========================================================
3. VALIDAR MÉTODO DE SOLICITUD
===========================================================

Este archivo solamente puede ejecutarse mediante POST.

Si alguien intenta entrar directamente:

    generar_cuotas.php

será enviado nuevamente al módulo de mensualidades.

===========================================================
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: " . $url_base . "/modulos/financiero/mensualidades/index.php");
    exit;
}


/*
===========================================================
4. RECIBIR MES Y AÑO
===========================================================

Si no se reciben valores, utilizamos el mes y año actuales.

===========================================================
*/

$mes = isset($_POST['mes'])
    ? (int) $_POST['mes']
    : (int) date('m');

$anio = isset($_POST['anio'])
    ? (int) $_POST['anio']
    : (int) date('Y');


/*
===========================================================
5. VALIDAR MES
===========================================================
*/

if ($mes < 1 || $mes > 12) {

header(
    "Location: " . $url_base . "/modulos/financiero/mensualidades/index.php?error=" .
    urlencode("El mes seleccionado no es válido.")
);

    exit;
}


/*
===========================================================
6. VALIDAR AÑO
===========================================================
*/

if ($anio < 2020 || $anio > 2100) {

    header(
        "Location: mensualidades/index.php?error=" .
        urlencode("El año seleccionado no es válido.")
    );

    exit;
}


/*
===========================================================
7. VALOR DE LA MENSUALIDAD
===========================================================
*/

$monto_predeterminado = 50000.00;


/*
===========================================================
8. FECHA DE VENCIMIENTO
===========================================================

Todas las cuotas generadas vencen el día 15
del mes seleccionado.

Ejemplo:

Mes: 08
Año: 2026

Resultado:

2026-08-15

===========================================================
*/

$fecha_vencimiento = sprintf(
    '%04d-%02d-15',
    $anio,
    $mes
);


/*
===========================================================
9. BUSCAR DEPORTISTAS ACTIVOS
===========================================================
*/

$stmt = $conexion->query("
    SELECT id
    FROM deportista
    WHERE estado = 'activo'
");

$deportistas = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
===========================================================
10. PREPARAR INSERCIÓN
===========================================================

INSERT IGNORE permite que MySQL ignore la inserción
cuando encuentra una cuota que ya existe.

La restricción:

    unique_cuota_periodo

protege:

    id_deportista + mes + anio

===========================================================
*/

$insert_stmt = $conexion->prepare("
    INSERT IGNORE INTO cuotas_mensuales
    (
        id_deportista,
        mes,
        anio,
        monto,
        fecha_vencimiento,
        estado
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        'pendiente'
    )
");


/*
===========================================================
11. CONTADOR
===========================================================
*/

$insertados = 0;


/*
===========================================================
12. GENERAR CUOTAS
===========================================================
*/

foreach ($deportistas as $deportista) {

    $insert_stmt->execute([
        $deportista['id'],
        $mes,
        $anio,
        $monto_predeterminado,
        $fecha_vencimiento
    ]);

    /*
    rowCount() será mayor que cero cuando MySQL
    realmente haya insertado una nueva cuota.
    */

    if ($insert_stmt->rowCount() > 0) {

        $insertados++;
    }
}


/*
===========================================================
13. MENSAJE FINAL
===========================================================
*/

$mensaje = "Se generaron $insertados nuevas cuotas " .
           "para el periodo " .
           sprintf('%02d/%04d', $mes, $anio);


/*
===========================================================
14. REGRESAR A MENSUALIDADES
===========================================================
*/

header(
    "Location: " . $url_base . "/modulos/financiero/mensualidades/index.php?msg=" .
    urlencode($mensaje)
);

exit;