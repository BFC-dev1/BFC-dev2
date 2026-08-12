<?php

/* ============================================================
   ELIMINAR EGRESO FINANCIERO
   Módulo: Financiero
   Sección: Egresos
   Tabla: egresos_financieros
   ============================================================ */


/* ============================================================
   1. VERIFICAR ROLES Y PERMISOS
   ============================================================ */

include("../../../includes/verificar_roles.php");


/* ============================================================
   2. CONEXIÓN A LA BASE DE DATOS
   ============================================================ */

include("../../../includes/config.php");
include("../../../modulos/conexion_modulos.php");


/* ============================================================
   3. OBTENER EL ID DEL EGRESO
   ============================================================ */

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;


/* ============================================================
   4. VALIDAR EL ID
   ============================================================ */

if ($id <= 0) {

    header("Location: index.php?error=id_invalido");
    exit;

}


/* ============================================================
   5. CONSULTAR EL EGRESO
   ============================================================ */

$sql = "SELECT
            id,
            fecha,
            categoria,
            concepto,
            monto,
            metodo_pago,
            observacion
        FROM egresos_financieros
        WHERE id = ?";

$stmt = $conexion->prepare($sql);


/* ============================================================
   6. VERIFICAR PREPARACIÓN DE LA CONSULTA
   ============================================================ */

if (!$stmt) {

    header("Location: index.php?error=consulta");
    exit;

}


/* ============================================================
   7. EJECUTAR CONSULTA CON PDO
   ============================================================ */

$stmt->execute([$id]);

$egreso = $stmt->fetch(PDO::FETCH_ASSOC);


/* ============================================================
   8. VERIFICAR QUE EL EGRESO EXISTA
   ============================================================ */

if (!$egreso) {

    header("Location: index.php?error=no_encontrado");
    exit;

}


/* ============================================================
   9. PROCESAR LA ELIMINACIÓN
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* --------------------------------------------------------
       Consulta DELETE
       -------------------------------------------------------- */

    $sqlEliminar = "DELETE
                    FROM egresos_financieros
                    WHERE id = ?";

    $stmtEliminar = $conexion->prepare($sqlEliminar);


    /* --------------------------------------------------------
       Verificar preparación
       -------------------------------------------------------- */

    if (!$stmtEliminar) {

        header("Location: index.php?error=eliminar");
        exit;

    }


    /* --------------------------------------------------------
       Ejecutar DELETE con PDO
       -------------------------------------------------------- */

    if ($stmtEliminar->execute([$id])) {

        header("Location: index.php?eliminado=1");
        exit;

    }


    /* --------------------------------------------------------
       Error al eliminar
       -------------------------------------------------------- */

    header("Location: index.php?error=eliminar");
    exit;

}


/* ============================================================
   10. HEADER DEL MÓDULO
   ============================================================ */

include("../../../template/header_modulos.php");

?>


<!-- ============================================================
     CONTENIDO PRINCIPAL
     ============================================================ -->

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <!-- =================================================
                 TARJETA DE CONFIRMACIÓN
                 ================================================= -->

            <div class="card shadow-sm border-0">

                <div class="card-header bg-danger text-white">

                    <h5 class="mb-0">

                        <i class="fa-solid fa-triangle-exclamation me-2"></i>

                        Eliminar egreso financiero

                    </h5>

                </div>


                <div class="card-body">

                    <!-- =========================================
                         MENSAJE DE CONFIRMACIÓN
                         ========================================= -->

                    <div class="alert alert-warning">

                        <strong>
                            ¿Está seguro de eliminar este egreso?
                        </strong>

                        <br>

                        Esta acción no se puede deshacer.

                    </div>


                    <!-- =========================================
                         INFORMACIÓN DEL EGRESO
                         ========================================= -->

                    <div class="mb-4">

                        <!-- FECHA -->

                        <div class="row mb-2">

                            <div class="col-sm-4 fw-bold">
                                Fecha:
                            </div>

                            <div class="col-sm-8">
                                <?= htmlspecialchars($egreso['fecha']) ?>
                            </div>

                        </div>


                        <!-- CATEGORÍA -->

                        <div class="row mb-2">

                            <div class="col-sm-4 fw-bold">
                                Categoría:
                            </div>

                            <div class="col-sm-8">
                                <?= htmlspecialchars($egreso['categoria']) ?>
                            </div>

                        </div>


                        <!-- CONCEPTO -->

                        <div class="row mb-2">

                            <div class="col-sm-4 fw-bold">
                                Concepto:
                            </div>

                            <div class="col-sm-8">
                                <?= htmlspecialchars($egreso['concepto']) ?>
                            </div>

                        </div>


                        <!-- MÉTODO DE PAGO -->

                        <div class="row mb-2">

                            <div class="col-sm-4 fw-bold">
                                Método de pago:
                            </div>

                            <div class="col-sm-8">
                                <?= htmlspecialchars($egreso['metodo_pago']) ?>
                            </div>

                        </div>


                        <!-- MONTO -->

                        <div class="row mb-2">

                            <div class="col-sm-4 fw-bold">
                                Monto:
                            </div>

                            <div class="col-sm-8 text-danger fw-bold">

                                $<?= number_format(
                                    (float) $egreso['monto'],
                                    2,
                                    '.',
                                    ','
                                ) ?>

                            </div>

                        </div>


                        <!-- OBSERVACIÓN -->

                        <div class="row mb-2">

                            <div class="col-sm-4 fw-bold">
                                Observación:
                            </div>

                            <div class="col-sm-8">

                                <?php if (!empty($egreso['observacion'])): ?>

                                    <?= htmlspecialchars($egreso['observacion']) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Sin observación
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>


                    <!-- =========================================
                         BOTONES DE ACCIÓN
                         ========================================= -->

                    <form method="POST">

                        <div class="d-flex justify-content-end gap-2">

                            <!-- CANCELAR -->

                            <a
                                href="index.php"
                                class="btn btn-secondary"
                            >

                                <i class="fa-solid fa-arrow-left me-1"></i>

                                Cancelar

                            </a>


                            <!-- ELIMINAR -->

                            <button
                                type="submit"
                                class="btn btn-danger"
                            >

                                <i class="fa-solid fa-trash me-1"></i>

                                Sí, eliminar egreso

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<?php

/* ============================================================
   11. FOOTER DEL MÓDULO
   ============================================================ */

include("../../../template/footer_modulos.php");

?>