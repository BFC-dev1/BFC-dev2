<?php
/*
====================================================================
 MÓDULO FINANCIERO - RESUMEN
 SISTEMA: BELLAVISTA FC
 ARCHIVO: /modulos/financiero/resumen/index.php

 Dashboard ejecutivo. Solo consulta:
 - cuotas_mensuales
 - matriculas
 - uniformes
 - egresos_financieros
====================================================================
*/

require_once("../../../includes/config.php");
require_once("../../../includes/verificar_roles.php");

/** @var string $url_base */
/** @noinspection PhpUndefinedFunctionInspection */
if (!tiene_permiso('financiero')) {
    header("Location: " . $url_base . "/modulos/dashboard/index.php");
    exit;
}

include("../../../modulos/auditoria/funciones/registrar_auditoria.php");
include("../../../modulos/conexion_modulos.php");

/** @var PDO $conexion */

/* ================================================================
   FILTROS
   ================================================================ */
$mes_actual  = (int) date('m');
$anio_actual = (int) date('Y');

$filtro_mes  = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT);
$filtro_anio = filter_input(INPUT_GET, 'anio', FILTER_VALIDATE_INT);

if ($filtro_mes === false || $filtro_mes === null || $filtro_mes < 1 || $filtro_mes > 12) {
    $filtro_mes = $mes_actual;
}

if ($filtro_anio === false || $filtro_anio === null || $filtro_anio < 2000 || $filtro_anio > 2100) {
    $filtro_anio = $anio_actual;
}

$nombres_meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$nombre_mes = $nombres_meses[$filtro_mes];
$base_url   = rtrim($url_base, '/');

/* ================================================================
   MENSUALIDADES
   ================================================================ */
/* ================================================================
   MENSUALIDADES
   ================================================================ */

$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM cuotas_mensuales
    WHERE estado = 'pagado'
      AND mes = ?
      AND anio = ?
");

$stmt->execute([$filtro_mes, $filtro_anio]);

$mensualidades_operaciones = (int) $stmt->fetchColumn();


$stmt = $conexion->prepare("
    SELECT COALESCE(SUM(monto), 0)
    FROM cuotas_mensuales
    WHERE estado = 'pagado'
      AND mes = ?
      AND anio = ?
");

$stmt->execute([$filtro_mes, $filtro_anio]);

$total_mensualidades = (float) $stmt->fetchColumn();


$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM cuotas_mensuales
    WHERE estado NOT IN ('pagado', 'cancelado')
      AND mes = ?
      AND anio = ?
");

$stmt->execute([$filtro_mes, $filtro_anio]);

$mensualidades_pendientes = (int) $stmt->fetchColumn();

/* ================================================================
   MATRÍCULAS
   El período financiero corresponde a la fecha de matrícula,
   NO a la fecha en que se registró físicamente el pago.
   ================================================================ */

$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM matriculas
    WHERE estado = 'pagado'
      AND MONTH(fecha_matricula) = ?
      AND YEAR(fecha_matricula) = ?
");

$stmt->execute([
    $filtro_mes,
    $filtro_anio
]);

$matriculas_operaciones =
    (int) $stmt->fetchColumn();


$stmt = $conexion->prepare("
    SELECT COALESCE(SUM(monto), 0)
    FROM matriculas
    WHERE estado = 'pagado'
      AND MONTH(fecha_matricula) = ?
      AND YEAR(fecha_matricula) = ?
");

$stmt->execute([
    $filtro_mes,
    $filtro_anio
]);

$total_matriculas =
    (float) $stmt->fetchColumn();


$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM matriculas
    WHERE estado = 'pendiente'
      AND anio = ?
");

$stmt->execute([
    $filtro_anio
]);

$matriculas_pendientes =
    (int) $stmt->fetchColumn();

/* ================================================================
   UNIFORMES
   La tabla no tiene fecha_pago. Para el período se usa fecha_pedido.
   Solo los registros estado_pago='pagado' generan ingreso.
   ================================================================ */
$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM uniformes
    WHERE MONTH(fecha_pedido) = ?
      AND YEAR(fecha_pedido) = ?
");
$stmt->execute([$filtro_mes, $filtro_anio]);
$uniformes_operaciones = (int) $stmt->fetchColumn();

$stmt = $conexion->prepare("
    SELECT COALESCE(SUM(valor * cantidad), 0)
    FROM uniformes
    WHERE estado_pago = 'pagado'
      AND MONTH(fecha_pedido) = ?
      AND YEAR(fecha_pedido) = ?
");
$stmt->execute([$filtro_mes, $filtro_anio]);
$total_uniformes = (float) $stmt->fetchColumn();

$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM uniformes
    WHERE estado_pago = 'pagado'
      AND MONTH(fecha_pedido) = ?
      AND YEAR(fecha_pedido) = ?
");
$stmt->execute([$filtro_mes, $filtro_anio]);
$uniformes_pagados = (int) $stmt->fetchColumn();

$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM uniformes
    WHERE estado_entrega = 'pendiente'
      AND MONTH(fecha_pedido) = ?
      AND YEAR(fecha_pedido) = ?
");
$stmt->execute([$filtro_mes, $filtro_anio]);
$uniformes_pendientes = (int) $stmt->fetchColumn();

/* ================================================================
   EGRESOS
   ================================================================ */
$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM egresos_financieros
    WHERE MONTH(fecha) = ?
      AND YEAR(fecha) = ?
");
$stmt->execute([$filtro_mes, $filtro_anio]);
$egresos_operaciones = (int) $stmt->fetchColumn();

$stmt = $conexion->prepare("
    SELECT COALESCE(SUM(monto), 0)
    FROM egresos_financieros
    WHERE MONTH(fecha) = ?
      AND YEAR(fecha) = ?
");
$stmt->execute([$filtro_mes, $filtro_anio]);
$total_egresos = (float) $stmt->fetchColumn();

$stmt = $conexion->prepare("
    SELECT COALESCE(MAX(monto), 0)
    FROM egresos_financieros
    WHERE MONTH(fecha) = ?
      AND YEAR(fecha) = ?
");
$stmt->execute([$filtro_mes, $filtro_anio]);
$mayor_egreso = (float) $stmt->fetchColumn();

/* ================================================================
   INDICADORES
   ================================================================ */
$total_ingresos = $total_mensualidades + $total_matriculas + $total_uniformes;
$total_operaciones = $mensualidades_operaciones + $matriculas_operaciones + $uniformes_operaciones + $egresos_operaciones;

/* Balance SIEMPRE se recalcula con el período seleccionado. */
$balance = $total_ingresos - $total_egresos;

if ($balance > 0) {
    $balance_clase = 'success';
    $balance_texto = 'Balance positivo';
    $balance_icono = 'fa-arrow-trend-up';
} elseif ($balance < 0) {
    $balance_clase = 'danger';
    $balance_texto = 'Balance negativo';
    $balance_icono = 'fa-arrow-trend-down';
} else {
    $balance_clase = 'secondary';
    $balance_texto = 'Balance en equilibrio';
    $balance_icono = 'fa-scale-balanced';
}

$porcentaje_mensualidades = $total_ingresos > 0 ? ($total_mensualidades / $total_ingresos) * 100 : 0;
$porcentaje_matriculas    = $total_ingresos > 0 ? ($total_matriculas / $total_ingresos) * 100 : 0;
$porcentaje_uniformes     = $total_ingresos > 0 ? ($total_uniformes / $total_ingresos) * 100 : 0;

/* ================================================================
   HEADER DEL MÓDULO
   ================================================================ */
$modulo_actual = 'Financiero';
$submodulo_actual = 'Resumen Financiero';
include("../../../template/header_modulos.php");
?>

<div class="container-fluid py-4">
    <div class="container">
<!-- ============================================================
     BOTÓN VOLVER AL DASHBOARD
     ============================================================ -->

<div class="mb-3">

    <a
        href="<?= htmlspecialchars($base_url) ?>/modulos/dashboard/index.php"
        class="btn btn-outline-secondary"
    >
        <i class="fa-solid fa-arrow-left me-1"></i>
        Volver al Dashboard
    </a>

</div>


<!-- ============================================================
     ENCABEZADO DEL RESUMEN FINANCIERO
     ============================================================ -->

<div class="mb-4">

    <h2 class="fw-bold mb-1">
        <i class="fa-solid fa-chart-pie text-primary me-2"></i>
        Resumen Financiero
    </h2>

    <p class="text-muted mb-0">
        Vista ejecutiva de la situación financiera de Bellavista FC.
    </p>

</div>

        <!-- FILTROS -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Mes</label>
                        <select name="mes" class="form-select">
                            <?php foreach ($nombres_meses as $numero_mes => $nombre_mes_opcion): ?>
                                <option value="<?= $numero_mes ?>" <?= $filtro_mes === $numero_mes ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nombre_mes_opcion) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Año</label>
                        <select name="anio" class="form-select">
                            <?php for ($anio = $anio_actual + 1; $anio >= $anio_actual - 2; $anio--): ?>
                                <option value="<?= $anio ?>" <?= $filtro_anio === $anio ? 'selected' : '' ?>>
                                    <?= $anio ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="fa-solid fa-filter me-1"></i>
                            Filtrar
                        </button>
                    </div>

                    <div class="col-md-auto">
                        <a href="<?= htmlspecialchars($base_url) ?>/modulos/financiero/resumen/index.php" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-rotate-left me-1"></i>
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- PERÍODO -->
        <div class="alert alert-light border mb-4">
            <i class="fa-solid fa-calendar-days text-primary me-2"></i>
            Mostrando información financiera de:
            <strong><?= htmlspecialchars($nombre_mes) ?> <?= $filtro_anio ?></strong>
        </div>

        <!-- 4 INDICADORES PRINCIPALES -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted fw-bold text-uppercase">Total ingresos</small>
                                <h3 class="fw-bold text-success mt-2 mb-1">$<?= number_format($total_ingresos, 2, ',', '.') ?></h3>
                                <small class="text-muted">Ingresos del período</small>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="fa-solid fa-arrow-trend-up text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted fw-bold text-uppercase">Total egresos</small>
                                <h3 class="fw-bold text-danger mt-2 mb-1">$<?= number_format($total_egresos, 2, ',', '.') ?></h3>
                                <small class="text-muted">Gastos del período</small>
                            </div>
                            <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                                <i class="fa-solid fa-arrow-trend-down text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-<?= $balance_clase ?> border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted fw-bold text-uppercase">Balance</small>
                                <h3 class="fw-bold text-<?= $balance_clase ?> mt-2 mb-1">
                                    <?= $balance < 0 ? '-$' . number_format(abs($balance), 2, ',', '.') : '$' . number_format($balance, 2, ',', '.') ?>
                                </h3>
                                <small class="text-muted"><?= htmlspecialchars($balance_texto) ?></small>
                            </div>
                            <div class="bg-<?= $balance_clase ?> bg-opacity-10 rounded-3 p-3">
                                <i class="fa-solid <?= $balance_icono ?> text-<?= $balance_clase ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted fw-bold text-uppercase">Operaciones</small>
                                <h3 class="fw-bold text-warning mt-2 mb-1"><?= number_format($total_operaciones, 0, ',', '.') ?></h3>
                                <small class="text-muted">Registros del período</small>
                            </div>
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="fa-solid fa-chart-column text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MÓDULOS: TARJETAS ÚNICAS Y COMPLETAMENTE CLICKEABLES -->
        <div class="row g-3 mb-4">

            <div class="col-xl-3 col-md-6">
                <a href="<?= htmlspecialchars($base_url) ?>/modulos/financiero/mensualidades/index.php" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm h-100 resumen-modulo-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted fw-bold text-uppercase">Mensualidades</small>
                                <i class="fa-solid fa-wallet text-success fs-4"></i>
                            </div>
                            <h4 class="fw-bold mb-1">$<?= number_format($total_mensualidades, 2, ',', '.') ?></h4>
                            <div class="small text-muted"><?= $mensualidades_operaciones ?> pagos realizados</div>
                            <div class="small text-warning mt-2"><?= $mensualidades_pendientes ?> pendientes</div>
                            <div class="mt-3 small text-primary fw-semibold">Ver módulo <i class="fa-solid fa-arrow-right ms-1"></i></div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6">
                <a href="<?= htmlspecialchars($base_url) ?>/modulos/financiero/matriculas/index.php" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm h-100 resumen-modulo-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted fw-bold text-uppercase">Matrículas</small>
                                <i class="fa-solid fa-file-signature text-primary fs-4"></i>
                            </div>
                            <h4 class="fw-bold mb-1">$<?= number_format($total_matriculas, 2, ',', '.') ?></h4>
                            <div class="small text-muted"><?= $matriculas_operaciones ?> matrículas pagadas</div>
                            <div class="small text-warning mt-2"><?= $matriculas_pendientes ?> pendientes</div>
                            <div class="mt-3 small text-primary fw-semibold">Ver módulo <i class="fa-solid fa-arrow-right ms-1"></i></div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6">
                <a href="<?= htmlspecialchars($base_url) ?>/modulos/financiero/uniformes/index.php" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm h-100 resumen-modulo-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted fw-bold text-uppercase">Uniformes</small>
                                <i class="fa-solid fa-shirt text-info fs-4"></i>
                            </div>
                            <h4 class="fw-bold mb-1">$<?= number_format($total_uniformes, 2, ',', '.') ?></h4>
                            <div class="small text-muted"><?= $uniformes_pagados ?> pagos realizados</div>
                            <div class="small text-warning mt-2"><?= $uniformes_pendientes ?> pendientes de entrega</div>
                            <div class="mt-3 small text-primary fw-semibold">Ver módulo <i class="fa-solid fa-arrow-right ms-1"></i></div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6">
                <a href="<?= htmlspecialchars($base_url) ?>/modulos/financiero/egresos/index.php" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm h-100 resumen-modulo-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted fw-bold text-uppercase">Egresos</small>
                                <i class="fa-solid fa-money-bill-transfer text-danger fs-4"></i>
                            </div>
                            <h4 class="fw-bold text-danger mb-1">$<?= number_format($total_egresos, 2, ',', '.') ?></h4>
                            <div class="small text-muted"><?= $egresos_operaciones ?> egresos registrados</div>
                            <div class="small text-danger mt-2">Mayor: $<?= number_format($mayor_egreso, 2, ',', '.') ?></div>
                            <div class="mt-3 small text-primary fw-semibold">Ver módulo <i class="fa-solid fa-arrow-right ms-1"></i></div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- DISTRIBUCIÓN + SITUACIÓN FINANCIERA -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1">Distribución de ingresos</h5>
                        <small class="text-muted">Participación de cada módulo en los ingresos del período.</small>

                        <div class="mt-4 mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold">Mensualidades</span>
                                <span class="text-muted"><?= number_format($porcentaje_mensualidades, 1, ',', '.') ?>%</span>
                            </div>
                            <div class="progress rounded-pill" style="height:10px">
                                <div class="progress-bar bg-success" style="width:<?= min(100, $porcentaje_mensualidades) ?>%"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold">Matrículas</span>
                                <span class="text-muted"><?= number_format($porcentaje_matriculas, 1, ',', '.') ?>%</span>
                            </div>
                            <div class="progress rounded-pill" style="height:10px">
                                <div class="progress-bar bg-primary" style="width:<?= min(100, $porcentaje_matriculas) ?>%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold">Uniformes</span>
                                <span class="text-muted"><?= number_format($porcentaje_uniformes, 1, ',', '.') ?>%</span>
                            </div>
                            <div class="progress rounded-pill" style="height:10px">
                                <div class="progress-bar bg-info" style="width:<?= min(100, $porcentaje_uniformes) ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4">Situación financiera</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Ingresos</span>
                            <strong class="text-success">$<?= number_format($total_ingresos, 2, ',', '.') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Egresos</span>
                            <strong class="text-danger">$<?= number_format($total_egresos, 2, ',', '.') ?></strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Balance</span>
                            <strong class="fs-5 text-<?= $balance_clase ?>">
                                <?= $balance < 0 ? '-$' . number_format(abs($balance), 2, ',', '.') : '$' . number_format($balance, 2, ',', '.') ?>
                            </strong>
                        </div>
                        <div class="mt-4 p-3 rounded bg-<?= $balance_clase ?> bg-opacity-10">
                            <div class="small text-<?= $balance_clase ?> fw-semibold">
                                <i class="fa-solid <?= $balance_icono ?> me-1"></i>
                                <?= htmlspecialchars($balance_texto) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- INDICADORES OPERATIVOS -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">Mensualidades pendientes</small>
                        <h3 class="fw-bold text-warning mt-2 mb-0"><?= number_format($mensualidades_pendientes, 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">Matrículas pendientes</small>
                        <h3 class="fw-bold text-warning mt-2 mb-0"><?= number_format($matriculas_pendientes, 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">Uniformes pendientes</small>
                        <h3 class="fw-bold text-info mt-2 mb-0"><?= number_format($uniformes_pendientes, 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">Total operaciones</small>
                        <h3 class="fw-bold text-primary mt-2 mb-0"><?= number_format($total_operaciones, 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-muted small text-center py-3">
            Resumen financiero correspondiente a
            <strong><?= htmlspecialchars($nombre_mes) ?> <?= $filtro_anio ?></strong>
            · Bellavista FC
        </div>
    </div>
</div>

<style>
.resumen-modulo-card {
    transition: transform .15s ease, box-shadow .15s ease;
}
.resumen-modulo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.10) !important;
}
</style>

<?php
/* ================================================================
   FOOTER DEL MÓDULO
   ================================================================ */
include("../../../template/footer_modulos.php");
?>
