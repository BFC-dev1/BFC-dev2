<?php

/*
=================================================
EDITAR MATRÍCULA
MÓDULO FINANCIERO
=================================================
*/

session_start();


/*
=================================================
CONFIGURACIÓN Y PERMISOS
=================================================
*/

require_once "../../../includes/config.php";
require_once "../../../includes/verificar_roles.php";


/*
=================================================
VERIFICAR PERMISO
=================================================
*/

if (!tiene_permiso('matriculas')) {

    header("Location: " . $url_base . "/index.php");
    exit;

}


/*
=================================================
CONEXIÓN
=================================================
*/

require_once "../../../includes/conexion.php";

/** @var PDO $conexion */


/*
=================================================
AUDITORÍA
=================================================
*/

require_once "../../auditoria/funciones/registrar_auditoria.php";


/*
=================================================
VALIDAR ID
=================================================
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
=================================================
SI VIENE POR POST
=================================================
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['id'])
) {

    $id = filter_var(
        $_POST['id'],
        FILTER_VALIDATE_INT
    );

}


if (!$id) {

    header("Location: index.php");
    exit;

}


/*
=================================================
VARIABLES
=================================================
*/

$mensajeError = '';

$monto = '';
$fecha_matricula = '';
$estado = '';
$metodo_pago = '';
$observacion = '';

$matricula = null;


/*
=================================================
OBTENER MATRÍCULA
=================================================
*/

$stmt = $conexion->prepare("
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
");


$stmt->execute([
    ':id' => (int)$id
]);


$matricula = $stmt->fetch(PDO::FETCH_ASSOC);


/*
=================================================
VALIDAR QUE EXISTA
=================================================
*/

if (!$matricula) {

    header("Location: index.php");
    exit;

}


/*
=================================================
CARGAR VALORES ACTUALES
=================================================
*/

$monto = $matricula['monto'] ?? '';

$fecha_matricula =
    $matricula['fecha_matricula'] ?? '';

$estado =
    $matricula['estado'] ?? 'pendiente';

$metodo_pago =
    $matricula['metodo_pago'] ?? '';

$observacion =
    $matricula['observacion'] ?? '';


/*
=================================================
PROCESAR FORMULARIO
=================================================
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /*
        =============================================
        RECIBIR DATOS
        =============================================
        */

        $monto = trim(
            $_POST['monto'] ?? ''
        );

        $fecha_matricula = trim(
            $_POST['fecha_matricula'] ?? ''
        );

        $estado = trim(
            $_POST['estado'] ?? ''
        );

        $metodo_pago = trim(
            $_POST['metodo_pago'] ?? ''
        );

        $observacion = trim(
            $_POST['observacion'] ?? ''
        );


        /*
        =============================================
        NORMALIZAR MONTO
        =============================================

        Ejemplos:

        50.000
        50.000,00
        50000
        50000,00

        se convierten correctamente.
        =============================================
        */

        $montoNormalizado = str_replace(
            '.',
            '',
            $monto
        );

        $montoNormalizado = str_replace(
            ',',
            '.',
            $montoNormalizado
        );


        /*
        =============================================
        VALIDAR MONTO
        =============================================
        */

        if (
            $montoNormalizado === '' ||
            !is_numeric($montoNormalizado)
        ) {

            throw new Exception(
                "Debe ingresar un valor válido para la matrícula."
            );

        }


        $montoNumerico =
            (float)$montoNormalizado;


        if ($montoNumerico <= 0) {

            throw new Exception(
                "El valor de la matrícula debe ser mayor que cero."
            );

        }


        /*
        =============================================
        VALIDAR FECHA
        =============================================
        */

        if (empty($fecha_matricula)) {

            throw new Exception(
                "Debe seleccionar la fecha de matrícula."
            );

        }


        /*
        =============================================
        VALIDAR ESTADO
        =============================================
        */

        $estadosPermitidos = [
            'pendiente',
            'pagado',
            'anulado'
        ];


        if (
            !in_array(
                $estado,
                $estadosPermitidos,
                true
            )
        ) {

            throw new Exception(
                "El estado seleccionado no es válido."
            );

        }


        /*
        =============================================
        VALIDAR MÉTODO DE PAGO
        =============================================
        */

        $metodosPermitidos = [
            'Efectivo',
            'Transferencia',
            'Nequi',
            'Daviplata',
            'Tarjeta'
        ];


        if ($estado === 'pagado') {

            if (
                empty($metodo_pago) ||
                !in_array(
                    $metodo_pago,
                    $metodosPermitidos,
                    true
                )
            ) {

                throw new Exception(
                    "Debe seleccionar el método de pago."
                );

            }

        } else {

            $metodo_pago = null;

        }


        /*
        =============================================
        FECHA DE PAGO
        =============================================

        Si ya estaba pagada y continúa pagada,
        conservamos la fecha original.

        Si pasa de pendiente/anulada a pagada,
        registramos la fecha actual.

        Si deja de estar pagada,
        eliminamos fecha de pago.
        =============================================
        */

        $fecha_pago_anterior =
            $matricula['fecha_pago'] ?? null;


        $estado_anterior =
            $matricula['estado'] ?? '';


        $fecha_pago = null;


        if ($estado === 'pagado') {

            if (
                $estado_anterior === 'pagado' &&
                !empty($fecha_pago_anterior)
            ) {

                $fecha_pago =
                    $fecha_pago_anterior;

            } else {

                $fecha_pago =
                    date('Y-m-d H:i:s');

            }

        }


        /*
        =============================================
        AÑO
        =============================================
        */

        $anio = date(
            'Y',
            strtotime($fecha_matricula)
        );


        /*
        =============================================
        GUARDAR VALORES ANTERIORES
        PARA AUDITORÍA
        =============================================
        */

        $datosAntes = [

            'id_deportista' =>
                (int)$matricula['id_deportista'],

            'anio' =>
                $matricula['anio'],

            'monto' =>
                $matricula['monto'],

            'fecha_matricula' =>
                $matricula['fecha_matricula'],

            'estado' =>
                $matricula['estado'],

            'fecha_pago' =>
                $matricula['fecha_pago'],

            'metodo_pago' =>
                $matricula['metodo_pago'],

            'observacion' =>
                $matricula['observacion']

        ];


        /*
        =============================================
        ACTUALIZAR MATRÍCULA
        =============================================
        */

        $stmtUpdate = $conexion->prepare("

            UPDATE matriculas

            SET
                anio = :anio,
                monto = :monto,
                fecha_matricula = :fecha_matricula,
                estado = :estado,
                fecha_pago = :fecha_pago,
                metodo_pago = :metodo_pago,
                observacion = :observacion

            WHERE id = :id

            LIMIT 1

        ");


        $stmtUpdate->execute([

            ':anio' =>
                $anio,

            ':monto' =>
                $montoNumerico,

            ':fecha_matricula' =>
                $fecha_matricula,

            ':estado' =>
                $estado,

            ':fecha_pago' =>
                $fecha_pago,

            ':metodo_pago' =>
                $metodo_pago,

            ':observacion' =>
                !empty($observacion)
                    ? $observacion
                    : null,

            ':id' =>
                (int)$id

        ]);


        /*
        =============================================
        DATOS DESPUÉS
        =============================================
        */

        $datosDespues = [

            'id_deportista' =>
                (int)$matricula['id_deportista'],

            'anio' =>
                $anio,

            'monto' =>
                $montoNumerico,

            'fecha_matricula' =>
                $fecha_matricula,

            'estado' =>
                $estado,

            'fecha_pago' =>
                $fecha_pago,

            'metodo_pago' =>
                $metodo_pago,

            'observacion' =>
                $observacion

        ];


        /*
        =============================================
        CAMBIOS
        =============================================
        */

        $cambios = [];

        foreach ($datosAntes as $campo => $valorAntes) {

            $valorDespues =
                $datosDespues[$campo] ?? null;


            if (
                (string)$valorAntes !==
                (string)$valorDespues
            ) {

                $cambios[$campo] = [

                    'antes' =>
                        $valorAntes,

                    'despues' =>
                        $valorDespues

                ];

            }

        }


        /*
        =============================================
        REGISTRAR AUDITORÍA
        =============================================
        */

        if (!empty($cambios)) {

            registrarAuditoria(

                $conexion,

                "matriculas",

                $id,

                "EDITAR",

                $cambios,

                "Edición de matrícula del deportista: "
                . $matricula['deportista_nombre']

            );

        }


        /*
        =============================================
        REDIRECCIÓN
        =============================================
        */

        header(
            "Location: ver_matricula.php?id="
            . (int)$id
            . "&actualizado=1"
        );

        exit;


    } catch (Exception $e) {

        $mensajeError =
            $e->getMessage();

    }

}


/*
=================================================
FORMATEAR MONTO PARA MOSTRAR
=================================================
*/

$montoMostrar = '';


if ($monto !== '') {

    $montoMostrar = number_format(

        (float)$monto,

        2,

        ',',

        '.'

    );

}


/*
=================================================
IDENTIFICACIÓN DEL MÓDULO
=================================================
*/

$modulo_actual = 'Financiero';
$submodulo_actual = 'Matrículas';


/*
=================================================
HEADER
=================================================
*/

include(
    "../../../template/header_modulos.php"
);

?>


<div class="container-fluid py-4">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <h2 class="fw-bold mb-1">

                Editar Matrícula

            </h2>

            <p class="text-muted mb-0">

                Modificar la información de la matrícula
                de ingreso.

            </p>

        </div>


        <a
            href="ver_matricula.php?id=<?= (int)$id ?>"
            class="btn btn-outline-secondary"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            Volver

        </a>

    </div>



    <!-- =================================================
         MENSAJE DE ERROR
    ================================================== -->

    <?php if (!empty($mensajeError)): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <strong>Error:</strong>

            <?= htmlspecialchars($mensajeError) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>



    <!-- =================================================
         INFORMACIÓN DEL DEPORTISTA
    ================================================== -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-dark text-white">

            <strong>

                <i class="fa-solid fa-user me-2"></i>

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
                            $matricula['deportista_nombre']
                            ?? '-'
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
                            $matricula['deportista_documento']
                            ?? '-'
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
                            $matricula['categoria_nombre']
                            ?? '-'
                        ) ?>

                    </div>

                </div>



                <!-- NÚMERO MATRÍCULA -->

                <div class="col-md-4">

                    <div class="text-muted small">

                        Número de matrícula

                    </div>

                    <div class="fw-bold">

                        #<?= (int)$matricula['id'] ?>

                    </div>

                </div>



                <!-- AÑO ACTUAL -->

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


            </div>

        </div>

    </div>



    <!-- =================================================
         FORMULARIO
    ================================================== -->

    <div class="card shadow-sm border-0">

        <div class="card-body p-4">


            <form
                method="POST"
                action=""
                id="formEditarMatricula"
            >


                <!-- ID -->

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int)$id ?>"
                >



                <!-- =================================================
                     INFORMACIÓN DE MATRÍCULA
                ================================================== -->

                <h5 class="fw-bold mb-3">

                    <i
                        class="fa-solid fa-file-invoice-dollar me-2"
                    ></i>

                    Información de matrícula

                </h5>


                <div class="row">


                    <!-- VALOR -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="monto"
                            class="form-label fw-bold"
                        >

                            Valor de matrícula *

                        </label>


                        <input
                            type="text"
                            name="monto"
                            id="monto"
                            class="form-control"
                            inputmode="decimal"
                            autocomplete="off"
                            value="<?= htmlspecialchars(
                                $montoMostrar
                            ) ?>"
                            required
                        >


                        <div class="form-text">

                            Ejemplo: 55.000,00

                        </div>

                    </div>



                    <!-- FECHA -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="fecha_matricula"
                            class="form-label fw-bold"
                        >

                            Fecha de matrícula *

                        </label>


                        <input
                            type="date"
                            name="fecha_matricula"
                            id="fecha_matricula"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $fecha_matricula
                            ) ?>"
                            required
                        >

                    </div>



                    <!-- ESTADO -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="estado"
                            class="form-label fw-bold"
                        >

                            Estado *

                        </label>


                        <select
                            name="estado"
                            id="estado"
                            class="form-select"
                            required
                        >

                            <option
                                value="pendiente"
                                <?= $estado === 'pendiente'
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                Pendiente

                            </option>


                            <option
                                value="pagado"
                                <?= $estado === 'pagado'
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                Pagado

                            </option>


                            <option
                                value="anulado"
                                <?= $estado === 'anulado'
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                Anulado

                            </option>

                        </select>

                    </div>



                    <!-- MÉTODO DE PAGO -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="metodo_pago"
                            class="form-label fw-bold"
                        >

                            Método de pago

                        </label>


                        <select
                            name="metodo_pago"
                            id="metodo_pago"
                            class="form-select"
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



                    <!-- OBSERVACIÓN -->

                    <div class="col-md-12 mb-4">

                        <label
                            for="observacion"
                            class="form-label fw-bold"
                        >

                            Observación

                        </label>


                        <textarea
                            name="observacion"
                            id="observacion"
                            class="form-control"
                            rows="4"
                            placeholder="Observaciones relacionadas con la matrícula..."
                        ><?= htmlspecialchars(
                            $observacion
                        ) ?></textarea>

                    </div>


                </div>



                <!-- =================================================
                     BOTONES
                ================================================== -->

                <div class="d-flex gap-2 mt-3">


                    <a
                        href="ver_matricula.php?id=<?= (int)$id ?>"
                        class="btn btn-danger"
                    >

                        <i class="fa-solid fa-xmark me-1"></i>

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fa-solid fa-save me-1"></i>

                        Guardar cambios

                    </button>


                </div>


            </form>

        </div>

    </div>


</div>



<script>

/*
=================================================
FORMATEAR MONTO
=================================================
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const campoMonto =
            document.getElementById('monto');


        if (!campoMonto) {
            return;
        }


        campoMonto.addEventListener(
            'input',
            function () {

                let valor = this.value;


                /*
                -----------------------------------------
                PERMITIR NÚMEROS Y COMA
                -----------------------------------------
                */

                valor =
                    valor.replace(/[^\d,]/g, '');


                const partes =
                    valor.split(',');


                let enteros =
                    partes[0] || '';


                let decimales =
                    partes[1] || '';


                /*
                -----------------------------------------
                MÁXIMO 2 DECIMALES
                -----------------------------------------
                */

                decimales =
                    decimales.substring(0, 2);


                /*
                -----------------------------------------
                CEROS INICIALES
                -----------------------------------------
                */

                if (enteros.length > 1) {

                    enteros =
                        enteros.replace(
                            /^0+(?=\d)/,
                            ''
                        );

                }


                /*
                -----------------------------------------
                FORMATEAR MILES
                -----------------------------------------
                */

                enteros =
                    enteros.replace(
                        /\B(?=(\d{3})+(?!\d))/g,
                        '.'
                    );


                /*
                -----------------------------------------
                RECONSTRUIR
                -----------------------------------------
                */

                this.value =
                    decimales !== ''
                        ? enteros + ',' + decimales
                        : enteros;

            }

        );

    }

);



/*
=================================================
CONTROL DEL MÉTODO DE PAGO
=================================================
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const estado =
            document.getElementById('estado');

        const metodoPago =
            document.getElementById('metodo_pago');


        if (!estado || !metodoPago) {
            return;
        }


        function actualizarMetodoPago() {

            if (estado.value === 'pagado') {

                metodoPago.required = true;

            } else {

                metodoPago.required = false;

            }

        }


        estado.addEventListener(
            'change',
            actualizarMetodoPago
        );


        actualizarMetodoPago();

    }

);

</script>


<?php

/*
=================================================
FOOTER DEL MÓDULO
=================================================
*/

include(
    "../../../template/footer_modulos.php"
);

?>