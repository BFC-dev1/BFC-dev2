<?php

/*
=========================================================
MÓDULO FINANCIERO - EGRESOS
SISTEMA: BELLAVISTA FC
ARCHIVO: guardar_egreso.php
=========================================================
*/

require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");

if (!tiene_permiso('egresos')) {
    header("Location: " . $url_base . "/index.php");
    exit;
}

include("../../../modulos/auditoria/funciones/registrar_auditoria.php");
include("../../../modulos/conexion_modulos.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$fecha = trim($_POST['fecha'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$concepto = trim($_POST['concepto'] ?? '');
$monto_raw = trim($_POST['monto'] ?? '');
$monto = str_replace(['.', ','], '', $monto_raw);
$metodo_pago = trim($_POST['metodo_pago'] ?? '');
$observacion = trim($_POST['observacion'] ?? '');
$asistencias_ids = $_POST['asistencias_ids'] ?? [];

$categorias_permitidas = [
    'Pago de deportistas',
    'Pago de entrenadores',
    'Pago de administración',
    'Compra de balones',
    'Compra de implementos deportivos',
    'Compra de uniformes',
    'Transporte',
    'Alimentación',
    'Mantenimiento',
    'Servicios públicos',
    'Arriendo',
    'Publicidad y comunicaciones',
    'Inscripciones y competencias',
    'Gastos médicos',
    'Papelería y suministros',
    'Otros gastos'
];

$metodos_pago_permitidos = [
    'Efectivo',
    'Transferencia bancaria',
    'Nequi',
    'Daviplata',
    'PSE',
    'Tarjeta débito',
    'Tarjeta crédito',
    'Cheque'
];

if (empty($fecha) || empty($categoria) || empty($concepto) || empty($monto) || empty($metodo_pago)) {
    header("Location: nuevo_egreso.php?error=campos");
    exit;
}

$fecha_objeto = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$fecha_objeto || $fecha_objeto->format('Y-m-d') !== $fecha) {
    header("Location: nuevo_egreso.php?error=fecha");
    exit;
}

function normalizarTexto($texto) {
    $originales  = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ';
    $modificadas = 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYDsaaaaaaaceeeeiiiidnoooooouuuuuyhy';
    $texto = utf8_decode($texto);
    $texto = strtr($texto, utf8_decode($originales), $modificadas);
    return strtolower(utf8_encode($texto));
}

$categoria_valida = false;
foreach ($categorias_permitidas as $cat_permitida) {
    if (normalizarTexto($categoria) === normalizarTexto($cat_permitida)) {
        $categoria_valida = true;
        break;
    }
}

if (!$categoria_valida) {
    header("Location: nuevo_egreso.php?error=categoria");
    exit;
}

if (!in_array($metodo_pago, $metodos_pago_permitidos, true)) {
    header("Location: nuevo_egreso.php?error=metodo");
    exit;
}

if (mb_strlen($concepto) > 255) {
    header("Location: nuevo_egreso.php?error=concepto");
    exit;
}

if (!is_numeric($monto) || (float)$monto <= 0) {
    header("Location: nuevo_egreso.php?error=monto");
    exit;
}

$monto = number_format((float)$monto, 2, '.', '');
$usuario_id = $_SESSION['id'] ?? null;

try {
    $conexion->beginTransaction();

    $stmt = $conexion->prepare("
        INSERT INTO egresos_financieros
        (
            fecha,
            categoria,
            concepto,
            monto,
            metodo_pago,
            observacion,
            usuario_id
        )
        VALUES
        (
            :fecha,
            :categoria,
            :concepto,
            :monto,
            :metodo_pago,
            :observacion,
            :usuario_id
        )
    ");

    $stmt->execute([
        ':fecha' => $fecha,
        ':categoria' => $categoria,
        ':concepto' => $concepto,
        ':monto' => $monto,
        ':metodo_pago' => $metodo_pago,
        ':observacion' => !empty($observacion) ? $observacion : null,
        ':usuario_id' => $usuario_id
    ]);

    $egreso_id = $conexion->lastInsertId();

    if (normalizarTexto($categoria) === normalizarTexto('Pago de entrenadores') && !empty($asistencias_ids)) {
        $stmt_update_asistencia = $conexion->prepare("
            UPDATE asistencia_entrenador 
            SET pagado = 1, egreso_id = ? 
            WHERE id = ?
        ");

        foreach ($asistencias_ids as $asistencia_id) {
            $stmt_update_asistencia->execute([$egreso_id, $asistencia_id]);
        }
    }

    $cambios = [
        'fecha' => ['antes' => null, 'despues' => $fecha],
        'categoria' => ['antes' => null, 'despues' => $categoria],
        'concepto' => ['antes' => null, 'despues' => $concepto],
        'monto' => ['antes' => null, 'despues' => $monto],
        'metodo_pago' => ['antes' => null, 'despues' => $metodo_pago],
        'observacion' => ['antes' => null, 'despues' => $observacion],
        'usuario_id' => ['antes' => null, 'despues' => $usuario_id]
    ];

    registrarAuditoria(
        $conexion,
        'egresos_financieros',
        $egreso_id,
        'CREAR',
        $cambios,
        'Registro de egreso financiero: ' . $concepto
    );

    $conexion->commit();

    header("Location: index.php?registrado=1");
    exit;

} catch (Exception $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    
    // Mostramos el mensaje exacto para detectar cualquier posible detalle
    echo "Error detallado en base de datos: " . $e->getMessage();
    exit;
}
?>