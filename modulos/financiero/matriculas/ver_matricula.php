<?php

session_start();


// =====================================================
// CONFIGURACIÓN GENERAL
// =====================================================

require_once "../../../includes/config.php";


// =====================================================
// CONEXIÓN A BASE DE DATOS
// =====================================================

require_once "../../../includes/conexion.php";

/** @var PDO $conexion */


// =====================================================
// ERRORES - TEMPORAL
// =====================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// =====================================================
// VALIDAR ID DE LA MATRÍCULA
// =====================================================

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}


// =====================================================
// CONSULTAR MATRÍCULA
// =====================================================

$sql = "
    SELECT
        m.id,
        m.id_deportista,
        m.anio,
        m.monto,
        m.fecha_matricula,
        m.estado,
        m.fecha_pago,
        m.metodo_pago,
        m.observacion,
        m.id_usuario_registra,
        m.created_at,

        d.nombre AS deportista_nombre,
        d.documento AS deportista_documento,

        c.nombre AS categoria_nombre,

        u.nombre AS usuario_registra

    FROM matriculas m

    INNER JOIN deportista d
        ON d.id = m.id_deportista

    LEFT JOIN categoria c
        ON c.id = d.categoria_id

    LEFT JOIN usuario u
        ON u.id = m.id_usuario_registra

    WHERE m.id = :id

    LIMIT 1
";


$stmt = $conexion->prepare($sql);
$stmt->execute([
    ':id' => $id
]);

$matricula = $stmt->fetch(PDO::FETCH_ASSOC);


// =====================================================
// VALIDAR QUE EXISTA
// =====================================================

if (!$matricula) {

    header("Location: index.php");
    exit;

}


// =====================================================
// VARIABLES
// =====================================================

$estado = $matricula['estado'] ?? 'pendiente';


// =====================================================
// FORMATEAR MONTO
// =====================================================

$monto = number_format(
    (float)($matricula['monto'] ?? 0),
    2,
    ',',
    '.'
);


// =====================================================
// FORMATEAR FECHA DE MATRÍCULA
// =====================================================

$fecha_matricula = '';

if (!empty($matricula['fecha_matricula'])) {

    $fecha_matricula = date(
        'd/m/Y',
        strtotime($matricula['fecha_matricula'])
    );

}


// =====================================================
// FORMATEAR FECHA DE PAGO
// =====================================================

$fecha_pago = '-';

if (!empty($matricula['fecha_pago'])) {

    $fecha_pago = date(
        'd/m/Y H:i',
        strtotime($matricula['fecha_pago'])
    );

}


// =====================================================
// ESTADO VISUAL
// =====================================================

$estado_clase = 'secondary';
$estado_texto = ucfirst($estado);

if ($estado === 'pagado') {

    $estado_clase = 'success';
    $estado_texto = 'Pagado';

} elseif ($estado === 'pendiente') {

    $estado_clase = 'warning';
    $estado_texto = 'Pendiente';

} elseif ($estado === 'anulado') {

    $estado_clase = 'danger';
    $estado_texto = 'Anulado';

}


// =====================================================
// HEADER DEL MÓDULO
// =====================================================

$modulo_actual = 'Financiero';
$submodulo_actual = 'Matrículas';

include("../../../template/header_modulos.php");

?>

<div class="container mt-5">

    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="fw-bold mb-1">
                Detalle de Matrícula
            </h1>

            <p class="text-muted mb-0">
                Información de la matrícula de ingreso al club.
            </p>

        </div>


        <a
            href="index.php"
            class="btn btn-outline-secondary"
        >

            ← Volver a Matrículas

        </a>

    </div>


    <!-- =================================================
         INFORMACIÓN DEL DEPORTISTA
    ================================================== -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-dark text-white">

            <strong>
                Información del deportista
            </strong>

        </div>


        <div class="card-body">

            <div class="row g-4">


                <!-- NOMBRE -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Deportista
                    </div>

                    <div class="fw-bold fs-5">

                        <?= htmlspecialchars(
                            $matricula['deportista_nombre'] ?? 'Sin nombre'
                        ) ?>

                    </div>

                </div>


                <!-- DOCUMENTO -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Documento
                    </div>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $matricula['deportista_documento'] ?? '-'
                        ) ?>

                    </div>

                </div>


                <!-- CATEGORÍA -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Categoría
                    </div>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $matricula['categoria_nombre'] ?? '-'
                        ) ?>

                    </div>

                </div>


                <!-- AÑO -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Año
                    </div>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $matricula['anio'] ?? '-'
                        ) ?>

                    </div>

                </div>


                <!-- FECHA MATRÍCULA -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Fecha de matrícula
                    </div>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $fecha_matricula
                        ) ?>

                    </div>

                </div>


                <!-- ID MATRÍCULA -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Número de matrícula
                    </div>

                    <div class="fw-bold">

                        #<?= (int)$matricula['id'] ?>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- =================================================
         INFORMACIÓN DEL PAGO
    ================================================== -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-dark text-white">

            <strong>
                Información del pago
            </strong>

        </div>


        <div class="card-body">

            <div class="row g-4">


                <!-- MONTO -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Valor de matrícula
                    </div>

                    <div class="fw-bold fs-4 text-primary">

                        $ <?= $monto ?>

                    </div>

                </div>


                <!-- ESTADO -->

                <div class="col-md-4">

                    <div class="text-muted small mb-1">
                        Estado
                    </div>

                    <span
                        class="badge text-bg-<?= $estado_clase ?>"
                    >

                        <?= htmlspecialchars($estado_texto) ?>

                    </span>

                </div>


                <!-- MÉTODO -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Método de pago
                    </div>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $matricula['metodo_pago'] ?? '-'
                        ) ?>

                    </div>

                </div>


                <!-- FECHA PAGO -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Fecha de pago
                    </div>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $fecha_pago
                        ) ?>

                    </div>

                </div>


                <!-- USUARIO -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Registrado por
                    </div>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $matricula['usuario_registra'] ?? '-'
                        ) ?>

                    </div>

                </div>


                <!-- FECHA CREACIÓN -->

                <div class="col-md-4">

                    <div class="text-muted small">
                        Fecha de registro
                    </div>

                    <div class="fw-bold">

                        <?php

                        if (!empty($matricula['created_at'])) {

                            echo htmlspecialchars(
                                date(
                                    'd/m/Y H:i',
                                    strtotime($matricula['created_at'])
                                )
                            );

                        } else {

                            echo '-';

                        }

                        ?>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- =================================================
         OBSERVACIÓN
    ================================================== -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-dark text-white">

            <strong>
                Observación
            </strong>

        </div>


        <div class="card-body">

            <?php if (!empty($matricula['observacion'])): ?>

                <p class="mb-0">

                    <?= nl2br(
                        htmlspecialchars(
                            $matricula['observacion']
                        )
                    ) ?>

                </p>

            <?php else: ?>

                <span class="text-muted">
                    Sin observaciones.
                </span>

            <?php endif; ?>

        </div>

    </div>


<!-- =================================================
     ACCIONES
================================================== -->

<div class="d-flex gap-2 mb-5">

    <!-- VOLVER -->
    <a
        href="index.php"
        class="btn btn-outline-secondary"
    >
        <i class="fa-solid fa-arrow-left me-1"></i>
        Volver
    </a>


    <?php if (tiene_permiso('matriculas')): ?>

        <!-- EDITAR MATRÍCULA -->
        <a
            href="editar_matricula.php?id=<?= (int)$matricula['id'] ?>"
            class="btn btn-primary"
        >
            <i class="fa-solid fa-pen-to-square me-1"></i>
            Editar matrícula
        </a>


        <!-- PAGAR MATRÍCULA -->
        <?php if ($estado === 'pendiente'): ?>

            <a
                href="pagar_matricula.php?id=<?= (int)$matricula['id'] ?>"
                class="btn btn-success"
            >
                <i class="fa-solid fa-money-bill-wave me-1"></i>
                Pagar matrícula
            </a>

        <?php endif; ?>

    <?php endif; ?>


</div>


<?php

// =====================================================
// FOOTER DEL MÓDULO
// =====================================================

include("../../../template/footer_modulos.php");

?>