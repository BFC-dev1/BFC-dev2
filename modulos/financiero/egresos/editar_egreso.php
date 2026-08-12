<?php

/*
=========================================================
EDITAR EGRESO FINANCIERO
=========================================================

Permite modificar un egreso existente.

Tabla:

    egresos_financieros

Campos:

    id
    fecha
    categoria
    concepto
    monto
    metodo_pago
    observacion
    usuario_id
    created_at
    updated_at
*/


/*
=========================================================
1. VERIFICAR PERMISOS
=========================================================
*/

require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");


/*
 * El permiso utilizado por el módulo es:
 *
 *     egresos
 */

if (!tiene_permiso('egresos')) {

    header("Location: " . $url_base . "/index.php");
    exit;
}


/*
=========================================================
2. CONEXIÓN
=========================================================
*/

include("../../../modulos/conexion_modulos.php");


/*
=========================================================
3. AUDITORÍA
=========================================================
*/

include("../../../modulos/auditoria/funciones/registrar_auditoria.php");


/*
=========================================================
4. OBTENER ID
=========================================================
*/

$id = isset($_GET['id'])
    ? (int) $_GET['id']
    : (int) ($_POST['id'] ?? 0);


/*
 * Validar ID.
 */

if ($id <= 0) {

    header("Location: index.php");
    exit;
}


/*
=========================================================
5. VARIABLES DEL FORMULARIO
=========================================================
*/

$mensajeError = "";


/*
 * Valores iniciales.
 */

$fecha = "";
$categoria = "";
$concepto = "";
$monto = "";
$metodo_pago = "";
$observacion = "";


/*
=========================================================
6. PROCESAR FORMULARIO
=========================================================
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    -----------------------------------------------------
    RECIBIR DATOS
    -----------------------------------------------------
    */

    $fecha = trim($_POST['fecha'] ?? "");

    $categoria = trim(
        $_POST['categoria'] ?? ""
    );

    $concepto = trim(
        $_POST['concepto'] ?? ""
    );

    $monto = trim(
        $_POST['monto'] ?? ""
    );

    $metodo_pago = trim(
        $_POST['metodo_pago'] ?? ""
    );

    $observacion = trim(
        $_POST['observacion'] ?? ""
    );


    /*
    -----------------------------------------------------
    VALIDAR FECHA
    -----------------------------------------------------
    */

    if ($fecha === "") {

        $mensajeError = "La fecha es obligatoria.";
    }


    /*
    -----------------------------------------------------
    VALIDAR CATEGORÍA
    -----------------------------------------------------
    */

    elseif ($categoria === "") {

        $mensajeError = "La categoría es obligatoria.";
    }


    /*
    -----------------------------------------------------
    VALIDAR CONCEPTO
    -----------------------------------------------------
    */

    elseif ($concepto === "") {

        $mensajeError = "El concepto es obligatorio.";
    }


    /*
    -----------------------------------------------------
    VALIDAR MONTO
    -----------------------------------------------------
    */

    elseif ($monto === "") {

        $mensajeError = "El monto es obligatorio.";
    }

    elseif (!is_numeric($monto)) {

        $mensajeError = "El monto debe ser un valor numérico.";
    }

    elseif ((float) $monto <= 0) {

        $mensajeError = "El monto debe ser mayor que cero.";
    }


    /*
    -----------------------------------------------------
    VALIDAR MÉTODO DE PAGO
    -----------------------------------------------------
    */

    elseif ($metodo_pago === "") {

        $mensajeError = "Selecciona un método de pago.";
    }


    /*
    -----------------------------------------------------
    VALIDAR MÉTODO PERMITIDO
    -----------------------------------------------------
    */

    elseif (
        !in_array(
            $metodo_pago,
            [
                'Efectivo',
                'Transferencia',
                'Tarjeta'
            ],
            true
        )
    ) {

        $mensajeError = "El método de pago seleccionado no es válido.";
    }


    /*
    =====================================================
    ACTUALIZAR REGISTRO
    =====================================================
    */

    if ($mensajeError === "") {


        /*
        -------------------------------------------------
        CONSULTAR DATOS ANTERIORES
        -------------------------------------------------
        */

        $stmtAnterior = $conexion->prepare("
            SELECT *
            FROM egresos_financieros
            WHERE id = :id
            LIMIT 1
        ");

        $stmtAnterior->execute([
            ':id' => $id
        ]);

        $egresoAnterior = $stmtAnterior->fetch(
            PDO::FETCH_ASSOC
        );


        /*
        -------------------------------------------------
        VALIDAR QUE EXISTA
        -------------------------------------------------
        */

        if (!$egresoAnterior) {

            $mensajeError =
                "El egreso que intentas editar no existe.";
        }


        /*
        -------------------------------------------------
        EJECUTAR ACTUALIZACIÓN
        -------------------------------------------------
        */

        else {

            try {


                /*
                =========================================
                ACTUALIZAR EGRESO
                =========================================
                */

                $sql = "
                    UPDATE egresos_financieros
                    SET
                        fecha = :fecha,
                        categoria = :categoria,
                        concepto = :concepto,
                        monto = :monto,
                        metodo_pago = :metodo_pago,
                        observacion = :observacion
                    WHERE id = :id
                    LIMIT 1
                ";


                $stmt = $conexion->prepare($sql);


                $stmt->execute([

                    ':fecha' => $fecha,

                    ':categoria' => $categoria,

                    ':concepto' => $concepto,

                    ':monto' => (float) $monto,

                    ':metodo_pago' => $metodo_pago,

                    ':observacion' =>
                        $observacion !== ""
                            ? $observacion
                            : null,

                    ':id' => $id

                ]);


                /*
                =========================================
                AUDITORÍA
                =========================================
                */

                /*
                 * Intentamos registrar la modificación
                 * utilizando la función de auditoría
                 * existente.
                 *
                 * Si la función disponible en tu sistema
                 * utiliza otra firma, el registro principal
                 * ya habrá sido actualizado correctamente.
                 */

                if (function_exists('registrar_auditoria')) {

                    registrar_auditoria(
                        'egresos',
                        'editar',
                        'Se editó el egreso financiero ID ' . $id
                    );
                }


                /*
                =========================================
                REDIRECCIÓN
                =========================================
                */

                header(
                    "Location: ver_egreso.php?id=" .
                    $id .
                    "&actualizado=1"
                );

                exit;


            } catch (PDOException $e) {

                $mensajeError =
                    "No fue posible actualizar el egreso. " .
                    "Verifica la información e intenta nuevamente.";
            }
        }
    }


} else {


    /*
    =====================================================
    7. CARGAR DATOS EXISTENTES
    =====================================================
    */

    $stmt = $conexion->prepare("
        SELECT
            id,
            fecha,
            categoria,
            concepto,
            monto,
            metodo_pago,
            observacion
        FROM egresos_financieros
        WHERE id = :id
        LIMIT 1
    ");


    $stmt->execute([
        ':id' => $id
    ]);


    $egreso = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    /*
    -----------------------------------------------------
    VALIDAR EXISTENCIA
    -----------------------------------------------------
    */

    if (!$egreso) {

        header(
            "Location: index.php?error=no_encontrado"
        );

        exit;
    }


    /*
    -----------------------------------------------------
    CARGAR DATOS EN VARIABLES
    -----------------------------------------------------
    */

    $fecha = $egreso['fecha'];

    $categoria = $egreso['categoria'];

    $concepto = $egreso['concepto'];

    $monto = $egreso['monto'];

    $metodo_pago = $egreso['metodo_pago'];

    $observacion = $egreso['observacion'] ?? "";
}


/*
=========================================================
8. HEADER DEL MÓDULO
=========================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Egresos';


include("../../../template/header_modulos.php");

?>


<!-- =====================================================
     CONTENIDO PRINCIPAL
     ===================================================== -->

<div class="container-fluid">


    <!-- =================================================
         BOTONES SUPERIORES
         ================================================= -->

    <div class="d-flex justify-content-between align-items-center mb-4">


        <div>

            <a
                href="ver_egreso.php?id=<?= $id ?>"
                class="btn btn-outline-dark"
            >

                ← Volver al Egreso

            </a>

        </div>


        <div>

            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >

                Ver todos los egresos

            </a>

        </div>


    </div>



    <!-- =================================================
         TÍTULO
         ================================================= -->

    <div class="mb-4">

        <h2 class="fw-bold mb-1">

            Editar Egreso

        </h2>

        <p class="text-muted mb-0">

            Modifica la información del egreso financiero.

        </p>

    </div>



    <!-- =================================================
         MENSAJE DE ERROR
         ================================================= -->

    <?php if ($mensajeError !== ""): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <i class="fa-solid fa-triangle-exclamation me-2"></i>

            <?= htmlspecialchars($mensajeError) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>



    <!-- =================================================
         FORMULARIO
         ================================================= -->

    <div class="card border-0 shadow-sm">


        <!-- =============================================
             CABECERA
             ============================================= -->

        <div class="card-header bg-dark text-white">

            <div class="d-flex justify-content-between align-items-center">

                <span>

                    <i class="fa-solid fa-pen-to-square me-2"></i>

                    Editar Egreso #<?= $id ?>

                </span>


                <span class="fw-bold">

                    $<?= number_format(
                        (float) $monto,
                        2,
                        '.',
                        ','
                    ) ?>

                </span>

            </div>

        </div>



        <!-- =============================================
             CUERPO
             ============================================= -->

        <div class="card-body">


            <form
                method="POST"
                action="editar_egreso.php?id=<?= $id ?>"
            >


                <!-- =====================================
                     ID OCULTO
                     ===================================== -->

                <input
                    type="hidden"
                    name="id"
                    value="<?= $id ?>"
                >



                <div class="row g-4">


                    <!-- =================================
                         FECHA
                         ================================= -->

                    <div class="col-md-6">

                        <label
                            for="fecha"
                            class="form-label fw-bold"
                        >

                            Fecha

                            <span class="text-danger">
                                *
                            </span>

                        </label>

                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            class="form-control"
                            value="<?= htmlspecialchars($fecha) ?>"
                            required
                        >

                    </div>



                    <!-- =================================
                         CATEGORÍA
                         ================================= -->

                    <div class="col-md-6">

                        <label
                            for="categoria"
                            class="form-label fw-bold"
                        >

                            Categoría

                            <span class="text-danger">
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            id="categoria"
                            name="categoria"
                            class="form-control"
                            maxlength="100"
                            value="<?= htmlspecialchars($categoria) ?>"
                            placeholder="Ejemplo: Pago de deportistas"
                            required
                        >

                        <div class="form-text">

                            Ejemplos: Pago de deportistas,
                            compra de balones,
                            administración, transporte, etc.

                        </div>

                    </div>



                    <!-- =================================
                         CONCEPTO
                         ================================= -->

                    <div class="col-12">

                        <label
                            for="concepto"
                            class="form-label fw-bold"
                        >

                            Concepto

                            <span class="text-danger">
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            id="concepto"
                            name="concepto"
                            class="form-control"
                            maxlength="255"
                            value="<?= htmlspecialchars($concepto) ?>"
                            placeholder="Describe claramente el gasto realizado"
                            required
                        >

                    </div>



                    <!-- =================================
                         MONTO
                         ================================= -->

                    <div class="col-md-6">

                        <label
                            for="monto"
                            class="form-label fw-bold"
                        >

                            Monto

                            <span class="text-danger">
                                *
                            </span>

                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                $
                            </span>

                            <input
                                type="number"
                                id="monto"
                                name="monto"
                                class="form-control"
                                min="0.01"
                                step="0.01"
                                value="<?= htmlspecialchars($monto) ?>"
                                required
                            >

                        </div>

                    </div>



                    <!-- =================================
                         MÉTODO DE PAGO
                         ================================= -->

                    <div class="col-md-6">

                        <label
                            for="metodo_pago"
                            class="form-label fw-bold"
                        >

                            Método de pago

                            <span class="text-danger">
                                *
                            </span>

                        </label>

                        <select
                            id="metodo_pago"
                            name="metodo_pago"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Selecciona un método
                            </option>


                            <option
                                value="Efectivo"
                                <?= $metodo_pago === 'Efectivo'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Efectivo
                            </option>


                            <option
                                value="Transferencia"
                                <?= $metodo_pago === 'Transferencia'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Transferencia
                            </option>


                            <option
                                value="Tarjeta"
                                <?= $metodo_pago === 'Tarjeta'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Tarjeta
                            </option>

                        </select>

                    </div>



                    <!-- =================================
                         OBSERVACIÓN
                         ================================= -->

                    <div class="col-12">

                        <label
                            for="observacion"
                            class="form-label fw-bold"
                        >

                            Observación

                        </label>

                        <textarea
                            id="observacion"
                            name="observacion"
                            class="form-control"
                            rows="4"
                            maxlength="1000"
                            placeholder="Información adicional del egreso..."
                        ><?= htmlspecialchars($observacion) ?></textarea>

                    </div>


                </div>



                <!-- =====================================
                     BOTONES
                     ===================================== -->

                <div class="d-flex justify-content-end gap-2 mt-4">


                    <a
                        href="ver_egreso.php?id=<?= $id ?>"
                        class="btn btn-outline-secondary"
                    >

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary fw-bold"
                    >

                        <i class="fa-solid fa-floppy-disk me-1"></i>

                        Guardar cambios

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

include("../../../template/footer_modulos.php");

?>