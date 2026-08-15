<?php

/*
=========================================================
MÓDULO FINANCIERO - NOVEDADES
SISTEMA: BELLAVISTA FC
ARCHIVO: ver_novedad.php

FUNCIÓN:
Mostrar el detalle completo de una novedad financiera.

FRAMEWORK:
- verificar_roles.php
- config.php
- conexion_modulos.php
- header_modulos.php
- footer_modulos.php
=========================================================
*/


/*
=========================================================
1. CONFIGURACIÓN Y PERMISOS
=========================================================
*/

require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");


/*
---------------------------------------------------------
PERMISO DEL MÓDULO
---------------------------------------------------------
*/

if (!tiene_permiso('egresos')) {

    header(
        "Location: " .
        $url_base .
        "/index.php"
    );

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
3. OBTENER ID
=========================================================
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
---------------------------------------------------------
VALIDAR ID
---------------------------------------------------------
*/

if (!$id || $id <= 0) {

    header(
        "Location: index.php?error=1"
    );

    exit;
}


/*
=========================================================
4. CONSULTAR NOVEDAD
=========================================================
*/

$sql = "

SELECT

    n.id,
    n.fecha,
    n.id_deportista,
    n.tipo,
    n.concepto,
    n.monto,
    n.estado,

    d.nombre AS nombre_deportista

FROM novedades_financieras n

LEFT JOIN deportista d
    ON d.id = n.id_deportista

WHERE n.id = :id

LIMIT 1

";


$stmt = $conexion->prepare($sql);

$stmt->execute([
    ':id' => $id
]);


$novedad = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/*
=========================================================
5. VALIDAR EXISTENCIA
=========================================================
*/

if (!$novedad) {

    header(
        "Location: index.php?error=1"
    );

    exit;
}


/*
=========================================================
6. CONFIGURACIÓN DEL HEADER
=========================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Novedades';


/*
=========================================================
7. HEADER DEL MÓDULO
=========================================================
*/

include(
    "../../../template/header_modulos.php"
);

?>


<!-- =====================================================
     BOTONES SUPERIORES
     ===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div class="d-flex gap-2">

        <!-- VOLVER -->
        <a
            href="index.php"
            class="btn btn-outline-dark"
        >
            <i class="fa-solid fa-arrow-left me-1"></i>

            Volver
        </a>


        <?php if (tiene_permiso('egresos')): ?>

            <!-- EDITAR -->
            <a
                href="editar_novedad.php?id=<?= (int) $novedad['id'] ?>"
                class="btn btn-primary"
            >
                <i class="fa-solid fa-pen me-1"></i>

                Editar
            </a>


            <!-- ELIMINAR -->
            <a
                href="eliminar_novedad.php?id=<?= (int) $novedad['id'] ?>"
                class="btn btn-outline-danger"
                onclick="return confirm(
                    '¿Estás seguro de eliminar esta novedad?'
                );"
            >
                <i class="fa-solid fa-trash me-1"></i>

                Eliminar
            </a>

        <?php endif; ?>

    </div>

</div>


<!-- =====================================================
     TÍTULO
     ===================================================== -->

<div class="mb-4">

    <h2 class="fw-bold">

        <i class="fa-solid fa-note-sticky text-primary"></i>

        Detalle de Novedad

    </h2>

    <p class="text-muted mb-0">

        Información completa de la novedad financiera.

    </p>

</div>


<!-- =====================================================
     TARJETA PRINCIPAL
     ===================================================== -->

<div class="card border-0 shadow-sm">

    <div class="card-header bg-dark text-white">

        <h5 class="mb-0">

            <i class="fa-solid fa-circle-info me-2"></i>

            Información de la novedad

        </h5>

    </div>


    <div class="card-body">

        <div class="row g-4">


            <!-- =================================================
                 ID
                 ================================================= -->

            <div class="col-md-4">

                <label class="form-label fw-bold text-muted">

                    ID

                </label>

                <div class="form-control bg-light">

                    <?= (int) $novedad['id'] ?>

                </div>

            </div>


            <!-- =================================================
                 FECHA
                 ================================================= -->

            <div class="col-md-4">

                <label class="form-label fw-bold text-muted">

                    Fecha

                </label>

                <div class="form-control bg-light">

                    <?php

                    if (!empty($novedad['fecha'])) {

                        echo date(
                            'd/m/Y',
                            strtotime(
                                $novedad['fecha']
                            )
                        );

                    } else {

                        echo '-';

                    }

                    ?>

                </div>

            </div>


            <!-- =================================================
                 TIPO
                 ================================================= -->

            <div class="col-md-4">

                <label class="form-label fw-bold text-muted">

                    Tipo de novedad

                </label>

                <div>

                    <span class="badge bg-secondary fs-6">

                        <?= htmlspecialchars(
                            $novedad['tipo']
                        ) ?>

                    </span>

                </div>

            </div>


            <!-- =================================================
                 DEPORTISTA
                 ================================================= -->

            <div class="col-md-6">

                <label class="form-label fw-bold text-muted">

                    Deportista

                </label>

                <div class="form-control bg-light">

                    <?php if (
                        !empty(
                            $novedad['nombre_deportista']
                        )
                    ): ?>

                        <?= htmlspecialchars(
                            $novedad['nombre_deportista']
                        ) ?>

                    <?php else: ?>

                        <span class="text-muted">

                            Novedad general

                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 DOCUMENTO
                 ================================================= -->

            <div class="col-md-6">

                <label class="form-label fw-bold text-muted">

                    Documento del deportista

                </label>

                <div class="form-control bg-light">

                    <?php if (
                        !empty(
                            $novedad['documento_deportista']
                        )
                    ): ?>

                        <?= htmlspecialchars(
                            $novedad['documento_deportista']
                        ) ?>

                    <?php else: ?>

                        <span class="text-muted">

                            No aplica

                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 TÍTULO
                 ================================================= -->

            <div class="col-md-8">

                <label class="form-label fw-bold text-muted">

                    Título

                </label>

                <div class="form-control bg-light">

                    <?= htmlspecialchars(
                        $novedad['concepto']
                    ) ?>

                </div>

            </div>


            <!-- =================================================
                 MONTO
                 ================================================= -->

            <div class="col-md-4">

                <label class="form-label fw-bold text-muted">

                    Monto

                </label>

                <div class="form-control bg-light fw-bold text-primary">

                    <?php if (
                        $novedad['monto'] !== null &&
                        $novedad['monto'] !== ''
                    ): ?>

                        $

                        <?= number_format(
                            (float) $novedad['monto'],
                            2
                        ) ?>

                    <?php else: ?>

                        <span class="text-muted">

                            Sin valor

                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 ESTADO
                 ================================================= -->

            <div class="col-md-4">

                <label class="form-label fw-bold text-muted">

                    Estado

                </label>

                <div>

                    <?php

                    $claseEstado = match (
                        $novedad['estado']
                    ) {

                        'Activa'
                            => 'bg-success',

                        'Aplicada'
                            => 'bg-primary',

                        'Finalizada'
                            => 'bg-secondary',

                        'Cancelada'
                            => 'bg-danger',

                        default
                            => 'bg-dark'

                    };

                    ?>

                    <span
                        class="badge <?= $claseEstado ?> fs-6"
                    >

                        <?= htmlspecialchars(
                            $novedad['estado']
                        ) ?>

                    </span>

                </div>

            </div>


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