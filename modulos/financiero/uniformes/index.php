<?php

/*
=========================================================
MÓDULO FINANCIERO - UNIFORMES
=========================================================

Archivo:
    /modulos/financiero/uniformes/index.php

Funciones:
    - Listar uniformes
    - Filtrar uniformes
    - Mostrar resumen
    - Ver uniforme
    - Editar uniforme
    - Eliminar uniforme
    - Crear nuevo uniforme

IMPORTANTE:
    Este archivo NO contiene CSS propio.
    Utiliza Bootstrap y Font Awesome cargados
    desde el header general del sistema.
=========================================================
*/


/*
=========================================================
1. CONFIGURACIÓN Y PERMISOS
=========================================================
*/

require_once("../../../includes/config.php");
require_once("../../../includes/verificar_roles.php");


/*
---------------------------------------------------------
VERIFICAR PERMISO DEL MÓDULO
---------------------------------------------------------
*/

if (!tiene_permiso('financiero_uniformes')) {

    header(
        "Location: " . $url_base . "/index.php"
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
4. FILTROS RECIBIDOS
=========================================================
*/

$filtro_busqueda = trim(
    $_GET['buscar'] ?? ''
);

$filtro_categoria =
    $_GET['categoria'] ?? '';

$filtro_tipo =
    $_GET['tipo'] ?? '';

$filtro_estado_entrega =
    $_GET['estado_entrega'] ?? '';

$filtro_estado_pago =
    $_GET['estado_pago'] ?? '';


/*
=========================================================
5. OBTENER CATEGORÍAS
=========================================================
*/

$stmtCategorias = $conexion->query("
    SELECT
        id,
        nombre
    FROM categoria
    ORDER BY nombre ASC
");

$categorias = $stmtCategorias->fetchAll(
    PDO::FETCH_ASSOC
);


/*
=========================================================
6. OBTENER TIPOS DE UNIFORME
=========================================================
*/

$stmtTipos = $conexion->query("
    SELECT DISTINCT
        tipo_uniforme
    FROM uniformes
    WHERE tipo_uniforme IS NOT NULL
      AND tipo_uniforme <> ''
    ORDER BY tipo_uniforme ASC
");

$tipos_uniforme = $stmtTipos->fetchAll(
    PDO::FETCH_COLUMN
);


/*
=========================================================
7. CONSTRUIR FILTROS SQL
=========================================================
*/

$where = [];

$params = [];


/*
---------------------------------------------------------
BÚSQUEDA
---------------------------------------------------------
*/

if ($filtro_busqueda !== '') {

    $where[] = "
        (
            d.nombre LIKE :buscar
            OR d.documento LIKE :buscar
        )
    ";

    $params[':buscar'] =
        '%' . $filtro_busqueda . '%';
}


/*
---------------------------------------------------------
CATEGORÍA
---------------------------------------------------------
*/

if ($filtro_categoria !== '') {

    $where[] =
        "d.categoria_id = :categoria_id";

    $params[':categoria_id'] =
        $filtro_categoria;
}


/*
---------------------------------------------------------
TIPO DE UNIFORME
---------------------------------------------------------
*/

if ($filtro_tipo !== '') {

    $where[] =
        "u.tipo_uniforme = :tipo_uniforme";

    $params[':tipo_uniforme'] =
        $filtro_tipo;
}


/*
---------------------------------------------------------
ESTADO DE ENTREGA
---------------------------------------------------------
*/

if ($filtro_estado_entrega !== '') {

    $where[] =
        "u.estado_entrega = :estado_entrega";

    $params[':estado_entrega'] =
        $filtro_estado_entrega;
}


/*
---------------------------------------------------------
ESTADO DE PAGO
---------------------------------------------------------
*/

if ($filtro_estado_pago !== '') {

    $where[] =
        "u.estado_pago = :estado_pago";

    $params[':estado_pago'] =
        $filtro_estado_pago;
}


/*
---------------------------------------------------------
WHERE FINAL
---------------------------------------------------------
*/

$whereSQL = '';

if (!empty($where)) {

    $whereSQL =
        "WHERE " . implode(
            " AND ",
            $where
        );
}


/*
=========================================================
8. CONSULTAR UNIFORMES
=========================================================
*/

$sql = "

    SELECT

        u.id,
        u.deportista_id,
        u.tipo_uniforme,
        u.talla,
        u.cantidad,
        u.valor,
        u.fecha_entrega,
        u.estado_entrega,
        u.estado_pago,
        u.observaciones,
        u.fecha_creacion,

        d.nombre AS deportista_nombre,
        d.documento AS deportista_documento,

        c.nombre AS categoria_nombre

    FROM uniformes u

    INNER JOIN deportista d
        ON d.id = u.deportista_id

    LEFT JOIN categoria c
        ON c.id = d.categoria_id

    $whereSQL

    ORDER BY
        u.fecha_creacion DESC,
        d.nombre ASC

";


$stmt = $conexion->prepare($sql);

$stmt->execute($params);

$uniformes = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
=========================================================
9. RESUMEN GENERAL
=========================================================
*/

$stmtResumen = $conexion->query("

    SELECT

        COUNT(*) AS total_uniformes,

        SUM(
            CASE
                WHEN estado_entrega = 'entregado'
                THEN 1
                ELSE 0
            END
        ) AS uniformes_entregados,

        SUM(
            CASE
                WHEN estado_entrega = 'pendiente'
                THEN 1
                ELSE 0
            END
        ) AS uniformes_pendientes,

        COALESCE(
            SUM(valor * cantidad),
            0
        ) AS valor_total

    FROM uniformes

");


$resumen = $stmtResumen->fetch(
    PDO::FETCH_ASSOC
);


/*
=========================================================
10. VARIABLES DEL RESUMEN
=========================================================
*/

$total_uniformes = (int)(
    $resumen['total_uniformes'] ?? 0
);

$uniformes_entregados = (int)(
    $resumen['uniformes_entregados'] ?? 0
);

$uniformes_pendientes = (int)(
    $resumen['uniformes_pendientes'] ?? 0
);

$valor_total = (float)(
    $resumen['valor_total'] ?? 0
);


/*
=========================================================
11. HEADER DEL MÓDULO
=========================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Uniformes';

include(
    "../../../template/header_modulos.php"
);

?>


<!-- =====================================================
     CONTENEDOR PRINCIPAL
     ===================================================== -->

<div class="container-fluid py-4">

    <div class="container">


        <!-- =================================================
             BOTÓN VOLVER
             ================================================= -->

        <div class="mb-3">

            <a
                href="<?= $url_base ?>/modulos/dashboard/index.php"
                class="btn btn-outline-dark"
            >

                <i class="fa-solid fa-arrow-left me-1"></i>

                Volver al Dashboard

            </a>

        </div>


        <!-- =================================================
             ENCABEZADO
             ================================================= -->

        <div
            class="d-flex
                   justify-content-between
                   align-items-center
                   mb-4"
        >

            <div>

                <h2 class="fw-bold mb-1">

                    <i
                        class="fa-solid fa-shirt text-primary me-2"
                    ></i>

                    Uniformes

                </h2>

                <p class="text-muted mb-0">

                    Gestión de uniformes, entregas y cobros.

                </p>

            </div>


            <!-- NUEVO UNIFORME -->

            <?php if (
                tiene_permiso('financiero_uniformes')
            ): ?>

                <a
                    href="nuevo_uniforme.php"
                    class="btn btn-primary fw-bold"
                >

                    <i class="fa-solid fa-plus me-1"></i>

                    Nuevo Uniforme

                </a>

            <?php endif; ?>

        </div>



        <!-- =================================================
             TARJETAS DE RESUMEN
             ================================================= -->

        <div class="row g-3 mb-4">


            <!-- TOTAL -->

            <div class="col-12 col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <small
                            class="text-muted
                                   fw-bold
                                   text-uppercase"
                        >
                            Total Uniformes
                        </small>

                        <h3
                            class="fw-bold
                                   text-primary
                                   mb-0
                                   mt-1"
                        >

                            <?= $total_uniformes ?>

                        </h3>

                    </div>

                </div>

            </div>



            <!-- ENTREGADOS -->

            <div class="col-12 col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <small
                            class="text-muted
                                   fw-bold
                                   text-uppercase"
                        >
                            Entregados
                        </small>

                        <h3
                            class="fw-bold
                                   text-success
                                   mb-0
                                   mt-1"
                        >

                            <?= $uniformes_entregados ?>

                        </h3>

                    </div>

                </div>

            </div>



            <!-- PENDIENTES -->

            <div class="col-12 col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <small
                            class="text-muted
                                   fw-bold
                                   text-uppercase"
                        >
                            Pendientes de Entrega
                        </small>

                        <h3
                            class="fw-bold
                                   text-warning
                                   mb-0
                                   mt-1"
                        >

                            <?= $uniformes_pendientes ?>

                        </h3>

                    </div>

                </div>

            </div>



            <!-- VALOR TOTAL -->

            <div class="col-12 col-md-6 col-xl-3">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <small
                            class="text-muted
                                   fw-bold
                                   text-uppercase"
                        >
                            Valor Total
                        </small>

                        <h3
                            class="fw-bold
                                   text-info
                                   mb-0
                                   mt-1"
                        >

                            $

                            <?= number_format(
                                $valor_total,
                                2,
                                '.',
                                ','
                            ) ?>

                        </h3>

                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             FILTROS
             
             IMPORTANTE:
             Se utiliza Bootstrap directamente.
             No depende de .filtros-uniformes
             ni de .filtro-item.
             ================================================= -->

        <div class="card border-0 shadow-sm mb-4">

            <div class="card-body">

                <form
                    method="GET"
                    class="row g-3 align-items-end"
                >


                    <!-- BUSCAR -->

                    <div class="col-12 col-md-4 col-xl-3">

                        <label
                            class="form-label fw-bold"
                        >

                            Buscar deportista

                        </label>

                        <input
                            type="text"
                            name="buscar"
                            class="form-control"
                            placeholder="Nombre o documento..."
                            value="<?= htmlspecialchars(
                                $filtro_busqueda
                            ) ?>"
                        >

                    </div>



                    <!-- CATEGORÍA -->

                    <div class="col-12 col-md-4 col-xl-2">

                        <label
                            class="form-label fw-bold"
                        >

                            Categoría

                        </label>

                        <select
                            name="categoria"
                            class="form-select"
                        >

                            <option value="">
                                Todas
                            </option>

                            <?php foreach (
                                $categorias
                                as $categoria
                            ): ?>

                                <option
                                    value="<?= (int)$categoria['id'] ?>"
                                    <?= (
                                        (string)$filtro_categoria ===
                                        (string)$categoria['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $categoria['nombre']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- TIPO -->

                    <div class="col-12 col-md-4 col-xl-2">

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
                                $tipos_uniforme
                                as $tipo
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars($tipo) ?>"
                                    <?= $filtro_tipo === $tipo
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars($tipo) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- ENTREGA -->

                    <div class="col-12 col-md-4 col-xl-2">

                        <label
                            class="form-label fw-bold"
                        >

                            Entrega

                        </label>

                        <select
                            name="estado_entrega"
                            class="form-select"
                        >

                            <option value="">
                                Todos
                            </option>

                            <option
                                value="pendiente"
                                <?= $filtro_estado_entrega === 'pendiente'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Pendiente
                            </option>

                            <option
                                value="entregado"
                                <?= $filtro_estado_entrega === 'entregado'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Entregado
                            </option>

                        </select>

                    </div>



                    <!-- PAGO -->

                    <div class="col-12 col-md-4 col-xl-2">

                        <label
                            class="form-label fw-bold"
                        >

                            Pago

                        </label>

                        <select
                            name="estado_pago"
                            class="form-select"
                        >

                            <option value="">
                                Todos
                            </option>

                            <option
                                value="pendiente"
                                <?= $filtro_estado_pago === 'pendiente'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Pendiente
                            </option>

                            <option
                                value="parcial"
                                <?= $filtro_estado_pago === 'parcial'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Parcial
                            </option>

                            <option
                                value="pagado"
                                <?= $filtro_estado_pago === 'pagado'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Pagado
                            </option>

                        </select>

                    </div>



                    <!-- BOTÓN FILTRAR -->

                    <div class="col-12 col-md-4 col-xl-1">

                        <button
                            type="submit"
                            class="btn btn-dark fw-bold w-100"
                            title="Aplicar filtros"
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



        <!-- =================================================
             TABLA DE UNIFORMES
             ================================================= -->

        <div class="card border-0 shadow-sm">

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table
                        class="table
                               table-hover
                               align-middle
                               mb-0
                               text-nowrap"
                    >


                        <!-- =================================================
                             ENCABEZADO
                             ================================================= -->

                        <thead class="table-dark">

                            <tr>

                                <th class="px-3">
                                    Deportista
                                </th>

                                <th>
                                    Documento
                                </th>

                                <th>
                                    Categoría
                                </th>

                                <th>
                                    Tipo
                                </th>

                                <th>
                                    Talla
                                </th>

                                <th>
                                    Cantidad
                                </th>

                                <th>
                                    Valor
                                </th>

                                <th>
                                    Entrega
                                </th>

                                <th>
                                    Pago
                                </th>

                                <th>
                                    Fecha Entrega
                                </th>

                                <th class="text-center">
                                    Acciones
                                </th>

                            </tr>

                        </thead>



                        <!-- =================================================
                             CUERPO
                             ================================================= -->

                        <tbody>


                            <?php if (
                                empty($uniformes)
                            ): ?>

                                <tr>

                                    <td
                                        colspan="11"
                                        class="text-center
                                               py-5
                                               text-muted"
                                    >

                                        <i
                                            class="fa-solid
                                                   fa-shirt
                                                   fa-2x
                                                   mb-3"
                                        ></i>

                                        <div class="fw-bold">

                                            No hay uniformes
                                            registrados.

                                        </div>

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $uniformes
                                    as $uniforme
                                ): ?>

                                    <tr>


                                        <!-- DEPORTISTA -->

                                        <td class="px-3">

                                            <span class="fw-bold">

                                                <?= htmlspecialchars(
                                                    $uniforme[
                                                        'deportista_nombre'
                                                    ]
                                                ) ?>

                                            </span>

                                        </td>



                                        <!-- DOCUMENTO -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $uniforme[
                                                    'deportista_documento'
                                                ] ?? '-'
                                            ) ?>

                                        </td>



                                        <!-- CATEGORÍA -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $uniforme[
                                                    'categoria_nombre'
                                                ] ?? '-'
                                            ) ?>

                                        </td>



                                        <!-- TIPO -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $uniforme[
                                                    'tipo_uniforme'
                                                ]
                                            ) ?>

                                        </td>



                                        <!-- TALLA -->

                                        <td>

                                            <span
                                                class="badge bg-secondary"
                                            >

                                                <?= htmlspecialchars(
                                                    $uniforme['talla']
                                                ) ?>

                                            </span>

                                        </td>



                                        <!-- CANTIDAD -->

                                        <td>

                                            <?= (int)
                                                $uniforme['cantidad']
                                            ?>

                                        </td>



                                        <!-- VALOR -->

                                        <td class="fw-bold">

                                            $

                                            <?= number_format(
                                                (float)$uniforme['valor']
                                                *
                                                (int)$uniforme['cantidad'],
                                                2,
                                                '.',
                                                ','
                                            ) ?>

                                        </td>



                                        <!-- ENTREGA -->

                                        <td>

                                            <?php if (
                                                $uniforme[
                                                    'estado_entrega'
                                                ] === 'entregado'
                                            ): ?>

                                                <span
                                                    class="badge bg-success"
                                                >

                                                    <i
                                                        class="fa-solid
                                                               fa-check
                                                               me-1"
                                                    ></i>

                                                    Entregado

                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="badge
                                                           bg-warning
                                                           text-dark"
                                                >

                                                    <i
                                                        class="fa-solid
                                                               fa-clock
                                                               me-1"
                                                    ></i>

                                                    Pendiente

                                                </span>

                                            <?php endif; ?>

                                        </td>



                                        <!-- PAGO -->

                                        <td>

                                            <?php if (
                                                $uniforme[
                                                    'estado_pago'
                                                ] === 'pagado'
                                            ): ?>

                                                <span
                                                    class="badge bg-success"
                                                >

                                                    <i
                                                        class="fa-solid
                                                               fa-check
                                                               me-1"
                                                    ></i>

                                                    Pagado

                                                </span>


                                            <?php elseif (
                                                $uniforme[
                                                    'estado_pago'
                                                ] === 'parcial'
                                            ): ?>

                                                <span
                                                    class="badge
                                                           bg-info
                                                           text-dark"
                                                >

                                                    <i
                                                        class="fa-solid
                                                               fa-coins
                                                               me-1"
                                                    ></i>

                                                    Parcial

                                                </span>


                                            <?php else: ?>

                                                <span
                                                    class="badge
                                                           bg-warning
                                                           text-dark"
                                                >

                                                    <i
                                                        class="fa-solid
                                                               fa-clock
                                                               me-1"
                                                    ></i>

                                                    Pendiente

                                                </span>

                                            <?php endif; ?>

                                        </td>



                                        <!-- FECHA ENTREGA -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $uniforme[
                                                        'fecha_entrega'
                                                    ]
                                                )
                                            ): ?>

                                                <?= date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $uniforme[
                                                            'fecha_entrega'
                                                        ]
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                -

                                            <?php endif; ?>

                                        </td>



                                        <!-- =================================================
                                             ACCIONES
                                             ================================================= -->

                                        <td class="text-center">

                                            <div
                                                class="d-flex
                                                       justify-content-center
                                                       gap-1"
                                            >


                                                <!-- VER -->

                                                <a
                                                    href="ver_uniforme.php?id=<?= (int)$uniforme['id'] ?>"
                                                    class="btn
                                                           btn-sm
                                                           btn-outline-dark"
                                                    title="Ver uniforme"
                                                >

                                                    <i
                                                        class="fa-solid
                                                               fa-eye"
                                                    ></i>

                                                    <span class="d-none d-lg-inline">
                                                        Ver
                                                    </span>

                                                </a>



                                                <?php if (
                                                    tiene_permiso(
                                                        'financiero_uniformes'
                                                    )
                                                ): ?>


                                                    <!-- EDITAR -->

                                                    <a
                                                        href="editar_uniforme.php?id=<?= (int)$uniforme['id'] ?>"
                                                        class="btn
                                                               btn-sm
                                                               btn-outline-primary"
                                                        title="Editar uniforme"
                                                    >

                                                        <i
                                                            class="fa-solid
                                                                   fa-pen"
                                                        ></i>

                                                        <span class="d-none d-lg-inline">
                                                            Editar
                                                        </span>

                                                    </a>



                                                    <!-- ELIMINAR -->

                                                    <a
                                                        href="eliminar_uniforme.php?id=<?= (int)$uniforme['id'] ?>"
                                                        class="btn
                                                               btn-sm
                                                               btn-outline-danger"
                                                        title="Eliminar uniforme"
                                                        onclick="
                                                            return confirm(
                                                                '¿Está seguro de eliminar este uniforme? Esta acción no se puede deshacer.'
                                                            );
                                                        "
                                                    >

                                                        <i
                                                            class="fa-solid
                                                                   fa-trash"
                                                        ></i>

                                                        <span class="d-none d-lg-inline">
                                                            Eliminar
                                                        </span>

                                                    </a>


                                                <?php endif; ?>


                                            </div>

                                        </td>


                                    </tr>

                                <?php endforeach; ?>


                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>


    </div>

</div>



<?php

/*
=========================================================
12. FOOTER DEL MÓDULO
=========================================================
*/

include(
    "../../../template/footer_modulos.php"
);

?>