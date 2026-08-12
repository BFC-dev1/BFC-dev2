<?php
/* ============================================================
   ELIMINAR MATRÍCULA
   Módulo: Financiero / Matrículas
   ============================================================ */


/* ============================================================
   CONFIGURACIÓN GENERAL
   ------------------------------------------------------------
   IMPORTANTE:
   config.php debe cargarse ANTES de header_modulos.php
   porque allí se define $url_base.
   ============================================================ */

require_once "../../../includes/config.php";


/* ============================================================
   CONEXIÓN A LA BASE DE DATOS
   ============================================================ */

require_once "../../../includes/conexion.php";


/* ============================================================
   VALIDAR ID RECIBIDO
   ============================================================ */

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {

    header("Location: index.php?error=id_invalido");
    exit;
}


/* ============================================================
   OBTENER LA MATRÍCULA
   ============================================================ */

try {

    $sql = "SELECT *
            FROM matriculas
            WHERE id = :id
            LIMIT 1";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':id' => $id
    ]);

    $matricula = $stmt->fetch(PDO::FETCH_ASSOC);


    /* ========================================================
       VERIFICAR QUE EXISTA
       ======================================================== */

    if (!$matricula) {

        header("Location: index.php?error=no_encontrada");
        exit;
    }

} catch (PDOException $e) {

    die(
        "Error al consultar la matrícula: "
        . htmlspecialchars($e->getMessage())
    );
}


/* ============================================================
   PROCESAR ELIMINACIÓN
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $sql = "DELETE FROM matriculas
                WHERE id = :id";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);


        /* ====================================================
           VERIFICAR ELIMINACIÓN
           ==================================================== */

        if ($stmt->rowCount() > 0) {

            header("Location: index.php?eliminado=1");
            exit;

        } else {

            header("Location: index.php?error=no_eliminada");
            exit;
        }

    } catch (PDOException $e) {

        die(
            "No se pudo eliminar la matrícula. "
            . "Es posible que tenga registros relacionados.<br><br>"
            . htmlspecialchars($e->getMessage())
        );
    }
}


/* ============================================================
   HEADER DEL MÓDULO
   ------------------------------------------------------------
   config.php ya fue cargado arriba.
   ============================================================ */

include("../../../template/header_modulos.php");

?>


<!-- ============================================================
     CONTENIDO PRINCIPAL
     ============================================================ -->

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="card shadow-sm border-0">

                <!-- =================================================
                     ENCABEZADO
                     ================================================= -->

                <div class="card-header bg-danger text-white">

                    <h5 class="mb-0">
                        <i class="fa-solid fa-trash me-2"></i>
                        Eliminar matrícula
                    </h5>

                </div>


                <!-- =================================================
                     CUERPO
                     ================================================= -->

                <div class="card-body text-center">

                    <div class="mb-4">

                        <i
                            class="fa-solid fa-triangle-exclamation text-danger"
                            style="font-size: 50px;"
                        ></i>

                    </div>


                    <h4 class="mb-3">
                        ¿Está seguro de eliminar esta matrícula?
                    </h4>


                    <p class="text-muted mb-4">

                        Esta acción eliminará permanentemente
                        la matrícula seleccionada.

                        <br>

                        <strong>
                            Esta acción no se puede deshacer.
                        </strong>

                    </p>


                    <!-- =================================================
                         INFORMACIÓN BÁSICA
                         ================================================= -->

                    <div class="alert alert-light border text-start">

                        <strong>
                            ID de matrícula:
                        </strong>

                        <?= htmlspecialchars($matricula['id']) ?>

                    </div>


                    <!-- =================================================
                         FORMULARIO DE CONFIRMACIÓN
                         ================================================= -->

                    <form method="POST">

                        <div class="d-flex justify-content-center gap-2">

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
                                onclick="return confirm('¿Está completamente seguro de eliminar esta matrícula?');"
                            >

                                <i class="fa-solid fa-trash me-1"></i>

                                Sí, eliminar matrícula

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
   FOOTER DEL MÓDULO
   ============================================================ */

include("../../../template/footer_modulos.php");
?>