<?php

/*
=========================================================
VER EGRESO FINANCIERO
=========================================================

Muestra el detalle completo de un egreso registrado.

Tabla utilizada:

    egresos_financieros

Columnas utilizadas:

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
 * El permiso principal del módulo es:
 *
 *     egresos
 *
 * Si el usuario no tiene permiso,
 * regresamos al inicio del sistema.
 */

if (!tiene_permiso('egresos')) {

    header("Location: " . $url_base . "/index.php");
    exit;
}


/*
=========================================================
2. CONEXIÓN A BASE DE DATOS
=========================================================
*/

include("../../../modulos/conexion_modulos.php");


/*
=========================================================
3. OBTENER ID DEL EGRESO
=========================================================
*/

/*
 * El ID llega mediante:
 *
 *     ver_egreso.php?id=1
 *
 * Lo convertimos a entero para evitar
 * valores inválidos.
 */

$id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


/*
 * Si no recibimos un ID válido,
 * regresamos al listado de egresos.
 */

if ($id <= 0) {

    header("Location: index.php");
    exit;
}


/*
=========================================================
4. CONSULTAR EGRESO
=========================================================
*/

$sql = "
    SELECT
        e.*
    FROM egresos_financieros e
    WHERE e.id = :id
    LIMIT 1
";


$stmt = $conexion->prepare($sql);

$stmt->execute([
    ':id' => $id
]);


/*
 * Obtener el registro.
 */

$egreso = $stmt->fetch(PDO::FETCH_ASSOC);


/*
=========================================================
5. VALIDAR QUE EL EGRESO EXISTA
=========================================================
*/

if (!$egreso) {

    header("Location: index.php?error=no_encontrado");
    exit;
}


/*
=========================================================
6. DATOS DEL HEADER
=========================================================
*/

$modulo_actual = 'Financiero';
$submodulo_actual = 'Egresos';


/*
=========================================================
7. CARGAR HEADER DEL MÓDULO
=========================================================
*/

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
                href="index.php"
                class="btn btn-outline-dark"
            >
                ← Volver a Egresos
            </a>

        </div>


        <div class="d-flex gap-2">


            <!-- =========================================
                 EDITAR EGRESO
                 ========================================= -->

            <a
                href="editar_egreso.php?id=<?= (int) $egreso['id'] ?>"
                class="btn btn-primary"
            >

                <i class="fa-solid fa-pen me-1"></i>

                Editar

            </a>


            <!-- =========================================
                 ELIMINAR EGRESO
                 ========================================= -->

            <a
                href="eliminar_egreso.php?id=<?= (int) $egreso['id'] ?>"
                class="btn btn-outline-danger"
                onclick="return confirm(
                    '¿Está seguro de eliminar este egreso? Esta acción no se puede deshacer.'
                );"
            >

                <i class="fa-solid fa-trash me-1"></i>

                Eliminar

            </a>

        </div>

    </div>



    <!-- =================================================
         TÍTULO
         ================================================= -->

    <div class="mb-4">

        <h2 class="fw-bold mb-1">

            Detalle del Egreso

        </h2>

        <p class="text-muted mb-0">

            Información completa del movimiento financiero.

        </p>

    </div>



    <!-- =================================================
         TARJETA PRINCIPAL
         ================================================= -->

    <div class="card shadow-sm border-0">


        <!-- =============================================
             CABECERA DE LA TARJETA
             ============================================= -->

        <div class="card-header bg-dark text-white">

            <div
                class="d-flex justify-content-between align-items-center"
            >

                <div>

                    <i class="fa-solid fa-receipt me-2"></i>

                    Egreso #<?= (int) $egreso['id'] ?>

                </div>


                <!-- =====================================
                     MONTO PRINCIPAL
                     ===================================== -->

                <div class="fw-bold">

                    $
                    <?= number_format(
                        (float) $egreso['monto'],
                        2,
                        '.',
                        ','
                    ) ?>

                </div>

            </div>

        </div>



        <!-- =============================================
             CUERPO
             ============================================= -->

        <div class="card-body">

            <div class="row g-4">


                <!-- =====================================
                     FECHA
                     ===================================== -->

                <div class="col-md-6">

                    <label class="form-label text-muted fw-bold">

                        Fecha

                    </label>

                    <div class="border rounded p-3 bg-light">

                        <i class="fa-regular fa-calendar me-2"></i>

                        <?= date(
                            'd/m/Y',
                            strtotime($egreso['fecha'])
                        ) ?>

                    </div>

                </div>



                <!-- =====================================
                     CATEGORÍA
                     ===================================== -->

                <div class="col-md-6">

                    <label class="form-label text-muted fw-bold">

                        Categoría

                    </label>

                    <div class="border rounded p-3 bg-light">

                        <span class="badge bg-secondary">

                            <?= htmlspecialchars(
                                $egreso['categoria']
                            ) ?>

                        </span>

                    </div>

                </div>



                <!-- =====================================
                     CONCEPTO
                     ===================================== -->

                <div class="col-md-6">

                    <label class="form-label text-muted fw-bold">

                        Concepto

                    </label>

                    <div class="border rounded p-3 bg-light">

                        <?= htmlspecialchars(
                            $egreso['concepto']
                        ) ?>

                    </div>

                </div>



                <!-- =====================================
                     MÉTODO DE PAGO
                     ===================================== -->

                <div class="col-md-6">

                    <label class="form-label text-muted fw-bold">

                        Método de pago

                    </label>

                    <div class="border rounded p-3 bg-light">

                        <?php

                        /*
                         * Mostramos el método de pago
                         * con una etiqueta visual.
                         */

                        $metodo = strtolower(
                            trim($egreso['metodo_pago'] ?? '')
                        );

                        ?>


                        <?php if ($metodo === 'efectivo'): ?>

                            <span class="badge bg-success">

                                <i class="fa-solid fa-money-bill me-1"></i>

                                <?= htmlspecialchars(
                                    $egreso['metodo_pago']
                                ) ?>

                            </span>


                        <?php elseif ($metodo === 'transferencia'): ?>

                            <span class="badge bg-primary">

                                <i class="fa-solid fa-building-columns me-1"></i>

                                <?= htmlspecialchars(
                                    $egreso['metodo_pago']
                                ) ?>

                            </span>


                        <?php elseif ($metodo === 'tarjeta'): ?>

                            <span class="badge bg-info text-dark">

                                <i class="fa-solid fa-credit-card me-1"></i>

                                <?= htmlspecialchars(
                                    $egreso['metodo_pago']
                                ) ?>

                            </span>


                        <?php else: ?>

                            <span class="badge bg-secondary">

                                <?= htmlspecialchars(
                                    $egreso['metodo_pago']
                                ) ?>

                            </span>

                        <?php endif; ?>

                    </div>

                </div>



                <!-- =====================================
                     MONTO
                     ===================================== -->

                <div class="col-md-6">

                    <label class="form-label text-muted fw-bold">

                        Monto

                    </label>

                    <div
                        class="border rounded p-3 bg-light fw-bold text-danger fs-5"
                    >

                        $

                        <?= number_format(
                            (float) $egreso['monto'],
                            2,
                            '.',
                            ','
                        ) ?>

                    </div>

                </div>



                <!-- =====================================
                     USUARIO
                     ===================================== -->

                <div class="col-md-6">

                    <label class="form-label text-muted fw-bold">

                        Usuario que registró

                    </label>

                    <div class="border rounded p-3 bg-light">

                        <?php if (!empty($egreso['usuario_id'])): ?>

                            Usuario #<?= (int) $egreso['usuario_id'] ?>

                        <?php else: ?>

                            <span class="text-muted">

                                No disponible

                            </span>

                        <?php endif; ?>

                    </div>

                </div>



                <!-- =====================================
                     OBSERVACIÓN
                     ===================================== -->

                <div class="col-12">

                    <label class="form-label text-muted fw-bold">

                        Observación

                    </label>

                    <div class="border rounded p-3 bg-light">

                        <?php if (
                            !empty($egreso['observacion'])
                        ): ?>

                            <?= nl2br(
                                htmlspecialchars(
                                    $egreso['observacion']
                                )
                            ) ?>

                        <?php else: ?>

                            <span class="text-muted">

                                Sin observación.

                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>



        <!-- =============================================
             PIE DE TARJETA
             ============================================= -->

        <div class="card-footer bg-light">

            <div class="row">

                <!-- =====================================
                     FECHA DE CREACIÓN
                     ===================================== -->

                <div class="col-md-6">

                    <small class="text-muted">

                        <strong>

                            Registrado:

                        </strong>

                        <?php if (!empty($egreso['created_at'])): ?>

                            <?= date(
                                'd/m/Y H:i',
                                strtotime($egreso['created_at'])
                            ) ?>

                        <?php else: ?>

                            No disponible

                        <?php endif; ?>

                    </small>

                </div>



                <!-- =====================================
                     ÚLTIMA ACTUALIZACIÓN
                     ===================================== -->

                <div class="col-md-6 text-md-end">

                    <small class="text-muted">

                        <strong>

                            Última actualización:

                        </strong>

                        <?php if (!empty($egreso['updated_at'])): ?>

                            <?= date(
                                'd/m/Y H:i',
                                strtotime($egreso['updated_at'])
                            ) ?>

                        <?php else: ?>

                            No disponible

                        <?php endif; ?>

                    </small>

                </div>

            </div>

        </div>

    </div>


</div>