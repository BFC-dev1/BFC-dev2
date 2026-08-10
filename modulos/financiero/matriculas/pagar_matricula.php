<?php

/*
=========================================================
MÓDULO FINANCIERO - PAGAR MATRÍCULA
=========================================================
*/


/*
=========================================================
CONFIGURACIÓN Y PERMISOS
=========================================================
*/

require_once "../../../includes/config.php";
require_once "../../../includes/verificar_roles.php";


/*
=========================================================
VERIFICAR PERMISOS
=========================================================
*/

if (!tiene_permiso('matriculas')) {

    header("Location: " . $url_base . "/index.php");
    exit;

}


/*
=========================================================
AUDITORÍA
=========================================================
*/

require_once "../../auditoria/funciones/registrar_auditoria.php";


/*
=========================================================
CONEXIÓN
=========================================================
*/

require_once "../../../modulos/conexion_modulos.php";


/*
=========================================================
VALIDAR ID
=========================================================
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {

    header("Location: index.php");
    exit;

}


/*
=========================================================
OBTENER MATRÍCULA
=========================================================
*/

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

        c.nombre AS categoria_nombre

    FROM matriculas m

    INNER JOIN deportista d
        ON d.id = m.id_deportista

    LEFT JOIN categoria c
        ON c.id = d.categoria_id

    WHERE m.id = :id

    LIMIT 1

";


$stmt = $conexion->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$matricula = $stmt->fetch(PDO::FETCH_ASSOC);


/*
=========================================================
VALIDAR EXISTENCIA
=========================================================
*/

if (!$matricula) {

    header("Location: index.php");
    exit;

}


/*
=========================================================
SI YA ESTÁ PAGADA
=========================================================
*/

if ($matricula['estado'] === 'pagado') {

    header(
        "Location: ver_matricula.php?id=" .
        (int)$id .
        "&ya_pagada=1"
    );

    exit;

}


/*
=========================================================
SI ESTÁ ANULADA
=========================================================
*/

if ($matricula['estado'] === 'anulado') {

    header(
        "Location: ver_matricula.php?id=" .
        (int)$id .
        "&anulada=1"
    );

    exit;

}


/*
=========================================================
VARIABLES
=========================================================
*/

$mensajeError = '';

$metodo_pago = '';


/*
=========================================================
PROCESAR PAGO
=========================================================
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /*
        ================================================
        MÉTODO DE PAGO
        ================================================
        */

        $metodo_pago = trim(
            $_POST['metodo_pago'] ?? ''
        );


        /*
        ================================================
        MÉTODOS PERMITIDOS
        ================================================
        */

        $metodosPermitidos = [

            'Efectivo',
            'Transferencia',
            'Nequi',
            'Daviplata',
            'Tarjeta'

        ];


        if (
            empty($metodo_pago) ||
            !in_array(
                $metodo_pago,
                $metodosPermitidos,
                true
            )
        ) {

            throw new Exception(
                "Debe seleccionar un método de pago."
            );

        }


        /*
        ================================================
        FECHA DE PAGO
        ================================================
        */

        $fecha_pago = date(
            'Y-m-d H:i:s'
        );


        /*
        ================================================
        ESTADO ANTERIOR
        ================================================
        */

        $estado_anterior =
            $matricula['estado'];


        $fecha_pago_anterior =
            $matricula['fecha_pago'];


        $metodo_pago_anterior =
            $matricula['metodo_pago'];


        /*
        ================================================
        ACTUALIZAR MATRÍCULA
        ================================================
        */

        $stmtUpdate = $conexion->prepare("

            UPDATE matriculas

            SET

                estado = 'pagado',

                fecha_pago = :fecha_pago,

                metodo_pago = :metodo_pago

            WHERE id = :id

              AND estado = 'pendiente'

        ");


        $stmtUpdate->execute([

            ':fecha_pago' =>
                $fecha_pago,

            ':metodo_pago' =>
                $metodo_pago,

            ':id' =>
                (int)$id

        ]);


        /*
        ================================================
        VERIFICAR ACTUALIZACIÓN
        ================================================
        */

        if ($stmtUpdate->rowCount() !== 1) {

            throw new Exception(
                "La matrícula no pudo actualizarse. "
                . "Es posible que ya haya sido pagada."
            );

        }


        /*
        ================================================
        PREPARAR AUDITORÍA
        ================================================
        */

        $cambios = [

            "estado" => [

                "antes" =>
                    $estado_anterior,

                "despues" =>
                    "pagado"

            ],

            "fecha_pago" => [

                "antes" =>
                    $fecha_pago_anterior,

                "despues" =>
                    $fecha_pago

            ],

            "metodo_pago" => [

                "antes" =>
                    $metodo_pago_anterior,

                "despues" =>
                    $metodo_pago

            ]

        ];


        /*
        ================================================
        REGISTRAR AUDITORÍA
        ================================================
        */

        registrarAuditoria(

            $conexion,

            "matriculas",

            (int)$id,

            "PAGAR",

            $cambios,

            "Pago de matrícula del deportista: "
            . $matricula['deportista_nombre']

        );


        /*
        ================================================
        REDIRECCIÓN
        ================================================
        */

        header(

            "Location: ver_matricula.php?id="
            . (int)$id
            . "&pagado=1"

        );

        exit;


    } catch (Exception $e) {

        $mensajeError =
            $e->getMessage();

    }

}


/*
=========================================================
FORMATEAR MONTO
=========================================================
*/

$monto = number_format(

    (float)(
        $matricula['monto'] ?? 0
    ),

    2,

    ',',

    '.'

);


/*
=========================================================
FORMATEAR FECHA MATRÍCULA
=========================================================
*/

$fecha_matricula = '-';

if (
    !empty(
        $matricula['fecha_matricula']
    )
) {

    $fecha_matricula = date(

        'd/m/Y',

        strtotime(
            $matricula['fecha_matricula']
        )

    );

}


/*
=========================================================
HEADER DEL MÓDULO
=========================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Matrículas';

include(
    "../../../template/header_modulos.php"
);

?>


<div class="container mt-5">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <h1 class="fw-bold mb-1">
                Pagar Matrícula
            </h1>

            <p class="text-muted mb-0">
                Registrar el pago de la matrícula de ingreso.
            </p>

        </div>


        <a
            href="ver_matricula.php?id=<?= (int)$id ?>"
            class="btn btn-outline-secondary"
        >

            ← Volver

        </a>

    </div>



    <?php if (!empty($mensajeError)): ?>

        <div
            class="alert alert-danger"
            role="alert"
        >

            <?= htmlspecialchars(
                $mensajeError
            ) ?>

        </div>

    <?php endif; ?>



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
                            $matricula[
                                'deportista_nombre'
                            ] ?? '-'
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
                            $matricula[
                                'deportista_documento'
                            ] ?? '-'
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
                            $matricula[
                                'categoria_nombre'
                            ] ?? '-'
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



                <!-- NÚMERO -->

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


            <form
                method="POST"
                action=""
            >


                <div class="row g-4">


                    <!-- VALOR -->

                    <div class="col-md-6">

                        <label
                            class="form-label fw-bold"
                        >

                            Valor de matrícula

                        </label>


                        <div
                            class="form-control bg-light fw-bold fs-5 text-primary"
                        >

                            $ <?= $monto ?>

                        </div>


                        <small class="text-muted">

                            Este valor no puede modificarse
                            desde el proceso de pago.

                        </small>

                    </div>



                    <!-- FECHA -->

                    <div class="col-md-6">

                        <label
                            class="form-label fw-bold"
                        >

                            Fecha de pago

                        </label>


                        <div
                            class="form-control bg-light"
                        >

                            <?= date('d/m/Y H:i') ?>

                        </div>


                        <small class="text-muted">

                            Se registrará automáticamente
                            la fecha y hora actual.

                        </small>

                    </div>



                    <!-- MÉTODO DE PAGO -->

                    <div class="col-md-6">

                        <label
                            for="metodo_pago"
                            class="form-label fw-bold"
                        >

                            Método de pago *

                        </label>


                        <select
                            name="metodo_pago"
                            id="metodo_pago"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Seleccionar método...
                            </option>


                            <option
                                value="Efectivo"
                                <?= $metodo_pago === 'Efectivo'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Efectivo
                            </option>


                            <option
                                value="Transferencia"
                                <?= $metodo_pago === 'Transferencia'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Transferencia
                            </option>


                            <option
                                value="Nequi"
                                <?= $metodo_pago === 'Nequi'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Nequi
                            </option>


                            <option
                                value="Daviplata"
                                <?= $metodo_pago === 'Daviplata'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Daviplata
                            </option>


                            <option
                                value="Tarjeta"
                                <?= $metodo_pago === 'Tarjeta'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Tarjeta
                            </option>

                        </select>

                    </div>



                    <!-- ESTADO -->

                    <div class="col-md-6">

                        <label
                            class="form-label fw-bold"
                        >

                            Estado

                        </label>


                        <div class="form-control bg-light">

                            <span
                                class="badge text-bg-warning"
                            >

                                Pendiente

                            </span>

                            →

                            <span
                                class="badge text-bg-success"
                            >

                                Pagado

                            </span>

                        </div>

                    </div>


                </div>



                <!-- =================================================
                     BOTONES
                ================================================== -->

                <div class="mt-4">


                    <a
                        href="ver_matricula.php?id=<?= (int)$id ?>"
                        class="btn btn-outline-secondary me-2"
                    >

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn btn-success"
                        onclick="
                            return confirm(
                                '¿Está seguro de registrar el pago de esta matrícula?'
                            );
                        "
                    >

                        <i
                            class="fa-solid fa-check me-1"
                        ></i>

                        Confirmar pago

                    </button>


                </div>


            </form>

        </div>

    </div>


</div>


<?php

/*
=========================================================
FOOTER DEL MÓDULO
=========================================================
*/

include(
    "../../../template/footer_modulos.php"
);

?>