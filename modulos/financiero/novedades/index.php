<?php

/*
=========================================================
MÓDULO FINANCIERO - NOVEDADES
SISTEMA: BELLAVISTA FC
ARCHIVO: index.php

DESCRIPCIÓN:
Listado, filtros y gestión de novedades financieras.

ESTRUCTURA BASADA EN:
Módulo Financiero - Egresos

IMPORTANTE:
Este archivo utiliza los campos actuales de
la tabla novedades_financieras:

- id
- id_deportista
- fecha
- tipo
- concepto
- monto
- estado
=========================================================
*/


/*
=========================================================
1. VERIFICAR PERMISOS Y CONFIGURACIÓN
=========================================================
*/

require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");


/*
---------------------------------------------------------
PERMISO DEL MÓDULO
---------------------------------------------------------

Actualmente utilizamos el permiso "egresos", ya que
es el permiso financiero existente que estamos usando
para la gestión de este módulo.
---------------------------------------------------------
*/

if (!tiene_permiso('egresos')) {

    header(
        "Location: "
        . $url_base
        . "/index.php"
    );

    exit;
}


/*
=========================================================
2. AUDITORÍA
=========================================================
*/

include(
    "../../../modulos/auditoria/funciones/registrar_auditoria.php"
);


/*
=========================================================
3. CONEXIÓN A BASE DE DATOS
=========================================================
*/

include(
    "../../../modulos/conexion_modulos.php"
);


/*
=========================================================
4. FILTROS
=========================================================
*/

/*
---------------------------------------------------------
MES Y AÑO ACTUAL
---------------------------------------------------------
*/

$mes_actual  = date('m');
$anio_actual = date('Y');


/*
---------------------------------------------------------
RECUPERAR FILTROS
---------------------------------------------------------
*/

$filtro_mes = $_GET['mes'] ?? $mes_actual;

$filtro_anio = $_GET['anio'] ?? $anio_actual;

$filtro_tipo = trim(
    $_GET['tipo'] ?? ''
);

$filtro_estado = trim(
    $_GET['estado'] ?? ''
);

$filtro_buscar = trim(
    $_GET['buscar'] ?? ''
);


/*
=========================================================
5. TIPOS DE NOVEDAD
=========================================================
*/

/*
---------------------------------------------------------
Estos valores deben coincidir con el campo "tipo"
de la tabla novedades_financieras.
---------------------------------------------------------
*/

$tipos_novedad = [

    'DESCUENTO',

    'RECARGO',

    'BECA',

    'EXONERACION',

    'AJUSTE'

];


/*
=========================================================
6. ESTADOS
=========================================================
*/

/*
---------------------------------------------------------
Estos valores deben coincidir con el campo "estado"
de la tabla novedades_financieras.
---------------------------------------------------------
*/

$estados = [

    'ACTIVA',

    'INACTIVA'

];


/*
=========================================================
7. TARJETAS DE RESUMEN
=========================================================
*/


/*
---------------------------------------------------------
TOTAL DE NOVEDADES
---------------------------------------------------------
*/

$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM novedades_financieras
    WHERE MONTH(fecha) = ?
      AND YEAR(fecha) = ?
");

$stmt->execute([

    $filtro_mes,

    $filtro_anio

]);

$total_novedades = $stmt->fetchColumn();


/*
---------------------------------------------------------
NOVEDADES ACTIVAS
---------------------------------------------------------
*/

$stmt = $conexion->prepare("
    SELECT COUNT(*)
    FROM novedades_financieras
    WHERE MONTH(fecha) = ?
      AND YEAR(fecha) = ?
      AND estado = 'ACTIVA'
");

$stmt->execute([

    $filtro_mes,

    $filtro_anio

]);

$novedades_activas = $stmt->fetchColumn();


/*
---------------------------------------------------------
MONTO TOTAL
---------------------------------------------------------
*/

$stmt = $conexion->prepare("
    SELECT COALESCE(SUM(monto), 0)
    FROM novedades_financieras
    WHERE MONTH(fecha) = ?
      AND YEAR(fecha) = ?
      AND monto IS NOT NULL
");

$stmt->execute([

    $filtro_mes,

    $filtro_anio

]);

$monto_total = $stmt->fetchColumn();


/*
=========================================================
8. CONSULTAR NOVEDADES
=========================================================
*/

/*
---------------------------------------------------------
CONSULTA PRINCIPAL

Se obtiene:

- Todos los campos de novedades_financieras
- Nombre del deportista

El deportista se obtiene mediante LEFT JOIN para permitir
que una novedad pueda existir sin deportista específico.
---------------------------------------------------------
*/

$query = "

    SELECT

        n.*,

        d.nombre AS nombre_deportista

    FROM novedades_financieras n

    LEFT JOIN deportista d
        ON d.id = n.id_deportista

    WHERE MONTH(n.fecha) = :mes

      AND YEAR(n.fecha) = :anio

";


/*
=========================================================
9. FILTRO POR TIPO
=========================================================
*/

if ($filtro_tipo !== '') {

    $query .= "

        AND n.tipo = :tipo

    ";
}


/*
=========================================================
10. FILTRO POR ESTADO
=========================================================
*/

if ($filtro_estado !== '') {

    $query .= "

        AND n.estado = :estado

    ";
}


/*
=========================================================
11. FILTRO DE BÚSQUEDA
=========================================================
*/

/*
---------------------------------------------------------
Busca por:

- nombre del deportista
- concepto de la novedad
---------------------------------------------------------
*/

if ($filtro_buscar !== '') {

    $query .= "

        AND (

            d.nombre LIKE :buscar

            OR n.concepto LIKE :buscar

        )

    ";
}


/*
=========================================================
12. ORDEN
=========================================================
*/

$query .= "

    ORDER BY

        n.fecha DESC,

        n.id DESC

";


/*
=========================================================
13. PREPARAR CONSULTA
=========================================================
*/

$stmt = $conexion->prepare($query);


/*
=========================================================
14. PARÁMETROS
=========================================================
*/

$params = [

    ':mes' => $filtro_mes,

    ':anio' => $filtro_anio

];


/*
---------------------------------------------------------
TIPO
---------------------------------------------------------
*/

if ($filtro_tipo !== '') {

    $params[':tipo'] = $filtro_tipo;

}


/*
---------------------------------------------------------
ESTADO
---------------------------------------------------------
*/

if ($filtro_estado !== '') {

    $params[':estado'] = $filtro_estado;

}


/*
---------------------------------------------------------
BÚSQUEDA
---------------------------------------------------------
*/

if ($filtro_buscar !== '') {

    $params[':buscar'] =
        '%' . $filtro_buscar . '%';

}


/*
=========================================================
15. EJECUTAR CONSULTA
=========================================================
*/

$stmt->execute($params);


/*
=========================================================
16. OBTENER RESULTADOS
=========================================================
*/

$novedades = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
=========================================================
17. HEADER DEL MÓDULO
=========================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Novedades';


/*
---------------------------------------------------------
CARGAR HEADER GENERAL
---------------------------------------------------------
*/

include(
    "../../../template/header_modulos.php"
);

?>


<!-- =====================================================
     BOTÓN VOLVER Y NUEVA NOVEDAD
     ===================================================== -->

<div
    class="d-flex justify-content-between
           align-items-center mb-3"
>

    <div class="d-flex gap-2">


        <!-- =================================================
             VOLVER AL DASHBOARD
             ================================================= -->

        <a
            href="<?= $url_base ?>/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >

            ← Volver al Dashboard

        </a>


        <!-- =================================================
             NUEVA NOVEDAD
             ================================================= -->

        <?php if (tiene_permiso('egresos')): ?>

            <a
                href="nueva_novedad.php"
                class="btn btn-primary fw-bold"
            >

                <i class="fa-solid fa-plus me-1"></i>

                Nueva Novedad

            </a>

        <?php endif; ?>


    </div>

</div>


<!-- =====================================================
     MENSAJE: REGISTRADO
     ===================================================== -->

<?php if (isset($_GET['registrado'])): ?>

    <div
        class="alert alert-success
               alert-dismissible fade show"
        role="alert"
    >

        <i
            class="fa-solid fa-circle-check me-2"
        ></i>

        La novedad fue registrada correctamente.

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =====================================================
     MENSAJE: ACTUALIZADO
     ===================================================== -->

<?php if (isset($_GET['actualizado'])): ?>

    <div
        class="alert alert-success
               alert-dismissible fade show"
        role="alert"
    >

        <i
            class="fa-solid fa-circle-check me-2"
        ></i>

        La novedad fue actualizada correctamente.

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =====================================================
     MENSAJE: ELIMINADO
     ===================================================== -->

<?php if (isset($_GET['eliminado'])): ?>

    <div
        class="alert alert-success
               alert-dismissible fade show"
        role="alert"
    >

        <i
            class="fa-solid fa-circle-check me-2"
        ></i>

        La novedad fue eliminada correctamente.

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =====================================================
     MENSAJE: ERROR
     ===================================================== -->

<?php if (isset($_GET['error'])): ?>

    <div
        class="alert alert-danger
               alert-dismissible fade show"
        role="alert"
    >

        <i
            class="fa-solid fa-triangle-exclamation me-2"
        ></i>

        No fue posible completar la operación.

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =====================================================
     TÍTULO DEL MÓDULO
     ===================================================== -->

<div
    class="d-flex justify-content-between
           align-items-center mb-4"
>

    <div>

        <h2 class="fw-bold">

            <i
                class="fa-solid fa-note-sticky
                       text-primary"
            ></i>

            Novedades Financieras

        </h2>


        <p class="text-muted mb-0">

            Gestión y control de novedades financieras
            de Bellavista FC.

        </p>

    </div>

</div>


<!-- =====================================================
     TARJETAS RESUMEN
     ===================================================== -->

<div class="row g-3 mb-4">


    <!-- =================================================
         TOTAL NOVEDADES
         ================================================= -->

    <div class="col-md-4">

        <div
            class="card border-0 shadow-sm
                   border-start border-primary border-4"
        >

            <div class="card-body">

                <small
                    class="text-muted fw-bold
                           text-uppercase"
                >

                    Total de novedades

                </small>


                <h3
                    class="fw-bold text-primary mb-0"
                >

                    <?= (int) $total_novedades ?>

                </h3>


                <small class="text-muted">

                    <?= htmlspecialchars($filtro_mes) ?>

                    /

                    <?= htmlspecialchars($filtro_anio) ?>

                </small>

            </div>

        </div>

    </div>


    <!-- =================================================
         NOVEDADES ACTIVAS
         ================================================= -->

    <div class="col-md-4">

        <div
            class="card border-0 shadow-sm
                   border-start border-success border-4"
        >

            <div class="card-body">

                <small
                    class="text-muted fw-bold
                           text-uppercase"
                >

                    Novedades activas

                </small>


                <h3
                    class="fw-bold text-success mb-0"
                >

                    <?= (int) $novedades_activas ?>

                </h3>


                <small class="text-muted">

                    Registros activos del periodo

                </small>

            </div>

        </div>

    </div>


    <!-- =================================================
         MONTO TOTAL
         ================================================= -->

    <div class="col-md-4">

        <div
            class="card border-0 shadow-sm
                   border-start border-warning border-4"
        >

            <div class="card-body">

                <small
                    class="text-muted fw-bold
                           text-uppercase"
                >

                    Valor asociado

                </small>


                <h3
                    class="fw-bold text-warning mb-0"
                >

                    $<?= number_format(
                        (float) $monto_total,
                        2
                    ) ?>

                </h3>


                <small class="text-muted">

                    Novedades con valor registrado

                </small>

            </div>

        </div>

    </div>


</div>


<!-- =====================================================
     FILTROS
     ===================================================== -->

<div
    class="card border-0 shadow-sm mb-4"
>

    <div class="card-body">

        <form
            method="GET"
            class="row g-3 align-items-end"
        >


            <!-- ==========================================
                 MES
                 ========================================== -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Mes

                </label>


                <select
                    name="mes"
                    class="form-select"
                >

                    <?php
                    for (
                        $m = 1;
                        $m <= 12;
                        $m++
                    ):
                    ?>

                        <?php
                        $mes_formato =
                            sprintf(
                                '%02d',
                                $m
                            );
                        ?>

                        <option
                            value="<?= $mes_formato ?>"
                            <?= $filtro_mes ==
                                $mes_formato
                                ? 'selected'
                                : '' ?>
                        >

                            <?= date(
                                "F",
                                mktime(
                                    0,
                                    0,
                                    0,
                                    $m,
                                    10
                                )
                            ) ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <!-- ==========================================
                 AÑO
                 ========================================== -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Año

                </label>


                <select
                    name="anio"
                    class="form-select"
                >

                    <?php

                    $anio_desde =
                        $anio_actual - 2;

                    $anio_hasta =
                        $anio_actual + 1;

                    ?>


                    <?php
                    for (
                        $anio = $anio_hasta;
                        $anio >= $anio_desde;
                        $anio--
                    ):
                    ?>

                        <option
                            value="<?= $anio ?>"
                            <?= $filtro_anio == $anio
                                ? 'selected'
                                : '' ?>
                        >

                            <?= $anio ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <!-- ==========================================
                 TIPO DE NOVEDAD
                 ========================================== -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Tipo

                </label>


                <select
                    name="tipo"
                    class="form-select"
                >

                    <option value="">

                        Todos

                    </option>


                    <?php foreach (
                        $tipos_novedad
                        as $tipo
                    ): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $tipo
                            ) ?>"
                            <?= $filtro_tipo === $tipo
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars(
                                $tipo
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- ==========================================
                 ESTADO
                 ========================================== -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Estado

                </label>


                <select
                    name="estado"
                    class="form-select"
                >

                    <option value="">

                        Todos

                    </option>


                    <?php foreach (
                        $estados
                        as $estado
                    ): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $estado
                            ) ?>"
                            <?= $filtro_estado === $estado
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars(
                                $estado
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- ==========================================
                 BÚSQUEDA
                 ========================================== -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Buscar

                </label>


                <input
                    type="text"
                    name="buscar"
                    class="form-control"
                    placeholder="Deportista o concepto..."
                    value="<?= htmlspecialchars(
                        $filtro_buscar
                    ) ?>"
                >

            </div>


            <!-- ==========================================
                 BOTÓN FILTRAR
                 ========================================== -->

            <div class="col-md-2">

                <button
                    type="submit"
                    class="btn btn-dark
                           w-100 fw-bold"
                >

                    <i
                        class="fa-solid fa-filter me-1"
                    ></i>

                    Filtrar

                </button>

            </div>


        </form>

    </div>

</div>


<!-- =====================================================
     TABLA DE NOVEDADES
     ===================================================== -->

<div
    class="card border-0 shadow-sm"
>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table
                class="table table-hover
                       align-middle mb-0"
            >


                <!-- ======================================
                     ENCABEZADO
                     ====================================== -->

                <thead class="table-dark">

                    <tr>

                        <th>

                            Fecha

                        </th>


                        <th>

                            Deportista

                        </th>


                        <th>

                            Tipo

                        </th>


                        <th>

                            Concepto

                        </th>


                        <th>

                            Monto

                        </th>


                        <th>

                            Estado

                        </th>


                        <th
                            class="text-center"
                        >

                            Acciones

                        </th>

                    </tr>

                </thead>


                <!-- ======================================
                     CUERPO
                     ====================================== -->

                <tbody>


                <?php if (empty($novedades)): ?>


                    <!-- ==================================
                         SIN RESULTADOS
                         ================================== -->

                    <tr>

                        <td
                            colspan="7"
                            class="text-center
                                   py-5 text-muted"
                        >

                            <i
                                class="fa-solid
                                       fa-note-sticky
                                       fa-2x mb-3"
                            ></i>

                            <br>

                            No hay novedades registradas
                            para los filtros seleccionados.


                            <?php if (
                                tiene_permiso('egresos')
                            ): ?>

                                <br>


                                <a
                                    href="nueva_novedad.php"
                                    class="btn btn-primary mt-3"
                                >

                                    <i
                                        class="fa-solid
                                               fa-plus me-1"
                                    ></i>

                                    Registrar primera novedad

                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>


                <?php else: ?>


                    <!-- ==================================
                         RECORRER NOVEDADES
                         ================================== -->

                    <?php foreach (
                        $novedades as $n
                    ): ?>

                        <tr>


                            <!-- ==========================
                                 FECHA
                                 ========================== -->

                            <td>

                                <?= date(
                                    'd/m/Y',
                                    strtotime(
                                        $n['fecha']
                                    )
                                ) ?>

                            </td>


                            <!-- ==========================
                                 DEPORTISTA
                                 ========================== -->

                            <td>

                                <?php if (
                                    !empty(
                                        $n['nombre_deportista']
                                    )
                                ): ?>

                                    <span
                                        class="fw-bold"
                                    >

                                        <?= htmlspecialchars(
                                            $n['nombre_deportista']
                                        ) ?>

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="text-muted"
                                    >

                                        General

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ==========================
                                 TIPO
                                 ========================== -->

                            <td>

                                <span
                                    class="badge bg-secondary"
                                >

                                    <?= htmlspecialchars(
                                        $n['tipo']
                                    ) ?>

                                </span>

                            </td>


                            <!-- ==========================
                                 CONCEPTO
                                 ========================== -->

                            <td class="fw-bold">

                                <?= htmlspecialchars(
                                    $n['concepto']
                                ) ?>

                            </td>


                            <!-- ==========================
                                 MONTO
                                 ========================== -->

                            <td
                                class="fw-bold text-primary"
                            >

                                <?php if (
                                    $n['monto'] !== null
                                    &&
                                    $n['monto'] !== ''
                                ): ?>

                                    $<?= number_format(
                                        (float) $n['monto'],
                                        2
                                    ) ?>

                                <?php else: ?>

                                    <span
                                        class="text-muted"
                                    >

                                        Sin valor

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ==========================
                                 ESTADO
                                 ========================== -->

                            <td>

                                <?php

                                /*
                                -------------------------------------------------
                                ASIGNAR COLOR SEGÚN ESTADO
                                -------------------------------------------------
                                */

                                switch (
                                    strtoupper(
                                        $n['estado']
                                    )
                                ) {

                                    case 'ACTIVA':

                                        $clase_estado =
                                            'bg-success';

                                        break;


                                    case 'INACTIVA':

                                        $clase_estado =
                                            'bg-secondary';

                                        break;


                                    default:

                                        $clase_estado =
                                            'bg-dark';

                                        break;

                                }

                                ?>


                                <span
                                    class="badge
                                           <?= $clase_estado ?>"
                                >

                                    <?= htmlspecialchars(
                                        $n['estado']
                                    ) ?>

                                </span>

                            </td>


                            <!-- ==========================
                                 ACCIONES
                                 ========================== -->

                            <td class="text-center">

                                <?php if (
                                    tiene_permiso('egresos')
                                ): ?>


                                    <div
                                        class="d-flex
                                               justify-content-center
                                               align-items-center
                                               gap-1"
                                    >


                                        <!-- ==================
                                             VER
                                             ================== -->

                                        <a
                                            href="ver_novedad.php?id=<?= (int) $n['id'] ?>"
                                            class="btn btn-sm
                                                   btn-outline-secondary"
                                            title="Ver novedad"
                                        >

                                            <i
                                                class="fa-solid
                                                       fa-eye"
                                            ></i>


                                            <span
                                                class="d-none
                                                       d-lg-inline
                                                       ms-1"
                                            >

                                                Ver

                                            </span>

                                        </a>


                                        <!-- ==================
                                             EDITAR
                                             ================== -->

                                        <a
                                            href="editar_novedad.php?id=<?= (int) $n['id'] ?>"
                                            class="btn btn-sm
                                                   btn-outline-primary"
                                            title="Editar novedad"
                                        >

                                            <i
                                                class="fa-solid
                                                       fa-pen"
                                            ></i>


                                            <span
                                                class="d-none
                                                       d-lg-inline
                                                       ms-1"
                                            >

                                                Editar

                                            </span>

                                        </a>


                                        <!-- ==================
                                             ELIMINAR
                                             ================== -->

                                        <a
                                            href="eliminar_novedad.php?id=<?= (int) $n['id'] ?>"
                                            class="btn btn-sm
                                                   btn-outline-danger"
                                            title="Eliminar novedad"
                                            onclick="return confirm(
                                                '¿Estás seguro de eliminar esta novedad?'
                                            );"
                                        >

                                            <i
                                                class="fa-solid
                                                       fa-trash"
                                            ></i>


                                            <span
                                                class="d-none
                                                       d-lg-inline
                                                       ms-1"
                                            >

                                                Eliminar

                                            </span>

                                        </a>


                                    </div>


                                <?php else: ?>


                                    <!-- ==================
                                         SOLO LECTURA
                                         ================== -->

                                    <span
                                        class="badge bg-secondary"
                                    >

                                        Solo lectura

                                    </span>

                                <?php endif; ?>

                            </td>


                        </tr>

                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>

            </table>

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