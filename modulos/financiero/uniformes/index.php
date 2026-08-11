<?php

/*
=================================================
MÓDULO FINANCIERO - UNIFORMES
=================================================

Este archivo sigue el mismo framework utilizado
por el módulo de Matrículas.

Orden del archivo:

1. Configuración
2. Verificación de permisos
3. Auditoría
4. Conexión
5. Filtros
6. Categorías y tipos de uniforme
7. Condiciones de consulta
8. Consulta de uniformes
9. Resumen financiero
10. Header del módulo
11. Contenido visual
12. Footer del módulo

=================================================
*/


/*
=================================================
1. CONFIGURACIÓN Y PERMISOS
=================================================
*/

require_once("../../../includes/config.php");
require_once("../../../includes/verificar_roles.php");


/*
=================================================
VERIFICAR PERMISOS
=================================================

El módulo utiliza el permiso:

financiero_uniformes

Si el usuario no tiene permiso, vuelve al
Dashboard principal.

=================================================
*/

if (!tiene_permiso('financiero_uniformes')) {

    header(
        "Location: " . $url_base . "/index.php"
    );

    exit;
}


/*
=================================================
2. AUDITORÍA
=================================================
*/

include(
    "../../../modulos/auditoria/funciones/registrar_auditoria.php"
);


/*
=================================================
3. CONEXIÓN A LA BASE DE DATOS
=================================================
*/

include(
    "../../../modulos/conexion_modulos.php"
);


/*
=================================================
4. FILTROS
=================================================
*/

/*
-------------------------------------------------
FILTRO DE BÚSQUEDA
-------------------------------------------------

Permite buscar por:

- Nombre del deportista
- Documento
-------------------------------------------------
*/

$filtro_busqueda = trim(
    $_GET['buscar'] ?? ''
);


/*
-------------------------------------------------
FILTRO DE CATEGORÍA
-------------------------------------------------
*/

$filtro_categoria = $_GET['categoria'] ?? '';


/*
-------------------------------------------------
FILTRO DE TIPO DE UNIFORME
-------------------------------------------------
*/

$filtro_tipo = $_GET['tipo'] ?? '';


/*
-------------------------------------------------
FILTRO DE ESTADO DE ENTREGA
-------------------------------------------------

Valores reales de la BD:

pendiente
entregado
-------------------------------------------------
*/

$filtro_estado_entrega = $_GET['estado_entrega'] ?? '';


/*
-------------------------------------------------
FILTRO DE ESTADO DE PAGO
-------------------------------------------------

Valores reales de la BD:

pendiente
parcial
pagado
-------------------------------------------------
*/

$filtro_estado_pago = $_GET['estado_pago'] ?? '';


/*
=================================================
5. OBTENER CATEGORÍAS
=================================================

Las categorías se obtienen desde la tabla:

categoria

Esto permite filtrar los uniformes por la
categoría del deportista.

=================================================
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
=================================================
6. OBTENER TIPOS DE UNIFORME
=================================================

Los tipos se obtienen directamente de los
registros existentes en uniformes.

Ejemplos:

- Juego
- Entrenamiento
- Presentación

=================================================
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
=================================================
7. CONDICIONES DE CONSULTA
=================================================
*/

$where = [];

$params = [];


/*
-------------------------------------------------
FILTRO POR BÚSQUEDA
-------------------------------------------------
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
-------------------------------------------------
FILTRO POR CATEGORÍA
-------------------------------------------------
*/

if ($filtro_categoria !== '') {

    $where[] =
        "d.categoria_id = :categoria_id";

    $params[':categoria_id'] =
        $filtro_categoria;
}


/*
-------------------------------------------------
FILTRO POR TIPO DE UNIFORME
-------------------------------------------------
*/

if ($filtro_tipo !== '') {

    $where[] =
        "u.tipo_uniforme = :tipo_uniforme";

    $params[':tipo_uniforme'] =
        $filtro_tipo;
}


/*
-------------------------------------------------
FILTRO POR ESTADO DE ENTREGA
-------------------------------------------------
*/

if ($filtro_estado_entrega !== '') {

    $where[] =
        "u.estado_entrega = :estado_entrega";

    $params[':estado_entrega'] =
        $filtro_estado_entrega;
}


/*
-------------------------------------------------
FILTRO POR ESTADO DE PAGO
-------------------------------------------------
*/

if ($filtro_estado_pago !== '') {

    $where[] =
        "u.estado_pago = :estado_pago";

    $params[':estado_pago'] =
        $filtro_estado_pago;
}


/*
=================================================
8. CONSTRUIR WHERE
=================================================
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
=================================================
9. CONSULTAR UNIFORMES
=================================================

ESTRUCTURA REAL DE LA TABLA:

uniformes

- id
- deportista_id
- tipo_uniforme
- talla
- cantidad
- valor
- fecha_entrega
- estado_entrega
- estado_pago
- observaciones
- fecha_creacion


RELACIONES:

uniformes.deportista_id
        ↓
deportista.id


deportista.categoria_id
        ↓
categoria.id

=================================================
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
=================================================
10. RESUMEN GENERAL
=================================================

El resumen se calcula sobre todos los uniformes
registrados, independientemente de los filtros.

=================================================
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


        SUM(
            CASE
                WHEN estado_pago IN (
                    'pendiente',
                    'parcial'
                )
                THEN 1
                ELSE 0
            END
        ) AS uniformes_pendientes_pago,


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
=================================================
11. VARIABLES DEL RESUMEN
=================================================
*/

$total_uniformes = (int) (
    $resumen['total_uniformes'] ?? 0
);


$uniformes_entregados = (int) (
    $resumen['uniformes_entregados'] ?? 0
);


$uniformes_pendientes = (int) (
    $resumen['uniformes_pendientes'] ?? 0
);


$uniformes_pendientes_pago = (int) (
    $resumen['uniformes_pendientes_pago'] ?? 0
);


$valor_total = (float) (
    $resumen['valor_total'] ?? 0
);


/*
=================================================
12. HEADER DEL MÓDULO
=================================================

El header se carga después de toda la lógica PHP
para mantener el mismo patrón utilizado por
Matrículas.

=================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Uniformes';


include(
    "../../../template/header_modulos.php"
);

?>


<!--
=================================================
13. CONTENIDO DEL MÓDULO
=================================================
-->

<div class="container-fluid py-4">


    <!--
    =================================================
    VOLVER AL DASHBOARD
    =================================================
    -->

    <div
        class="d-flex justify-content-between
               align-items-center mb-3"
    >

        <div class="d-flex gap-2">

            <a
                href="<?= $url_base ?>/modulos/dashboard/index.php"
                class="btn btn-outline-dark"
            >

                <i
                    class="fa-solid fa-arrow-left me-1"
                ></i>

                Volver al Dashboard

            </a>

        </div>

    </div>


    <!--
    =================================================
    ENCABEZADO
    =================================================
    -->

    <div
        class="d-flex justify-content-between
               align-items-center mb-4"
    >

        <div>

            <h2 class="fw-bold mb-1">

                <i
                    class="fa-solid fa-shirt
                           text-primary me-2"
                ></i>

                Uniformes

            </h2>


            <p class="text-muted mb-0">

                Gestión de uniformes, entregas y cobros.

            </p>

        </div>


        <?php if (tiene_permiso('financiero_uniformes')): ?>

            <a
                href="nuevo_uniforme.php"
                class="btn btn-primary fw-bold"
            >

                <i
                    class="fa-solid fa-plus me-1"
                ></i>

                Nuevo Uniforme

            </a>

        <?php endif; ?>

    </div>


    <!--
    =================================================
    TARJETAS DE RESUMEN
    =================================================
    -->

    <div class="row g-3 mb-4">


        <!--
        -------------------------------------------------
        TOTAL UNIFORMES
        -------------------------------------------------
        -->

        <div class="col-md-3">

            <div
                class="card border-0
                       shadow-sm h-100"
            >

                <div class="card-body">

                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center"
                    >

                        <div>

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
                                       mb-0 mt-1"
                            >

                                <?= $total_uniformes ?>

                            </h3>

                        </div>


                        <div
                            class="text-primary
                                   fs-2"
                        >

                            <i
                                class="fa-solid fa-shirt"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!--
        -------------------------------------------------
        UNIFORMES ENTREGADOS
        -------------------------------------------------
        -->

        <div class="col-md-3">

            <div
                class="card border-0
                       shadow-sm h-100"
            >

                <div class="card-body">

                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center"
                    >

                        <div>

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
                                       mb-0 mt-1"
                            >

                                <?= $uniformes_entregados ?>

                            </h3>

                        </div>


                        <div
                            class="text-success
                                   fs-2"
                        >

                            <i
                                class="fa-solid
                                       fa-circle-check"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!--
        -------------------------------------------------
        PENDIENTES DE ENTREGA
        -------------------------------------------------
        -->

        <div class="col-md-3">

            <div
                class="card border-0
                       shadow-sm h-100"
            >

                <div class="card-body">

                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center"
                    >

                        <div>

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
                                       mb-0 mt-1"
                            >

                                <?= $uniformes_pendientes ?>

                            </h3>

                        </div>


                        <div
                            class="text-warning
                                   fs-2"
                        >

                            <i
                                class="fa-solid fa-clock"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!--
        -------------------------------------------------
        VALOR TOTAL
        -------------------------------------------------
        -->

        <div class="col-md-3">

            <div
                class="card border-0
                       shadow-sm h-100"
            >

                <div class="card-body">

                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center"
                    >

                        <div>

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
                                       mb-0 mt-1"
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


                        <div
                            class="text-info
                                   fs-2"
                        >

                            <i
                                class="fa-solid
                                       fa-dollar-sign"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


    </div>


    <!--
    =================================================
    FILTROS
    =================================================
    -->

    <div
        class="card border-0
               shadow-sm mb-4"
    >

        <div class="card-body">


            <form
                method="GET"
                class="row g-3 align-items-end"
            >


                <!--
                -------------------------------------------------
                BÚSQUEDA
                -------------------------------------------------
                -->

                <div class="col-md-3">

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


                <!--
                -------------------------------------------------
                CATEGORÍA
                -------------------------------------------------
                -->

                <div class="col-md-2">

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


                <!--
                -------------------------------------------------
                TIPO DE UNIFORME
                -------------------------------------------------
                -->

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
                            $tipos_uniforme
                            as $tipo
                        ): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $tipo
                                ) ?>"
                                <?= $filtro_tipo === $tipo
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $tipo
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!--
                -------------------------------------------------
                ESTADO DE ENTREGA
                -------------------------------------------------
                -->

                <div class="col-md-2">

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


                <!--
                -------------------------------------------------
                ESTADO DE PAGO
                -------------------------------------------------
                -->

                <div class="col-md-2">

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


                <!--
                -------------------------------------------------
                BOTONES
                -------------------------------------------------
                -->

                <div class="col-auto">

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-dark fw-bold"
                            title="Filtrar"
                        >

                            <i
                                class="fa-solid fa-filter me-1"
                            ></i>

                            Filtrar

                        </button>


                        <a
                            href="index.php"
                            class="btn btn-outline-secondary"
                            title="Limpiar filtros"
                        >

                            <i
                                class="fa-solid fa-rotate-left me-1"
                            ></i>

                            Limpiar

                        </a>

                    </div>

                </div>


            </form>

        </div>

    </div>


    <!--
    =================================================
    TABLA DE UNIFORMES
    =================================================
    -->

    <div
        class="card border-0
               shadow-sm"
    >

        <div class="card-body p-0">

            <div class="table-responsive">


                <table
                    class="table
                           table-hover
                           align-middle
                           mb-0"
                >


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

                            <th
                                class="text-center"
                            >
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php if (empty($uniformes)): ?>


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


                                    <div
                                        class="fw-bold"
                                    >

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


                                    <!--
                                    ---------------------------------
                                    DEPORTISTA
                                    ---------------------------------
                                    -->

                                    <td class="px-3">

                                        <div
                                            class="fw-bold"
                                        >

                                            <?= htmlspecialchars(
                                                $uniforme[
                                                    'deportista_nombre'
                                                ]
                                            ) ?>

                                        </div>

                                    </td>


                                    <!--
                                    ---------------------------------
                                    DOCUMENTO
                                    ---------------------------------
                                    -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $uniforme[
                                                'deportista_documento'
                                            ] ?? '-'
                                        ) ?>

                                    </td>


                                    <!--
                                    ---------------------------------
                                    CATEGORÍA
                                    ---------------------------------
                                    -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $uniforme[
                                                'categoria_nombre'
                                            ] ?? '-'
                                        ) ?>

                                    </td>


                                    <!--
                                    ---------------------------------
                                    TIPO DE UNIFORME
                                    ---------------------------------
                                    -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $uniforme[
                                                'tipo_uniforme'
                                            ]
                                        ) ?>

                                    </td>


                                    <!--
                                    ---------------------------------
                                    TALLA
                                    ---------------------------------
                                    -->

                                    <td>

                                        <span
                                            class="badge
                                                   bg-secondary"
                                        >

                                            <?= htmlspecialchars(
                                                $uniforme[
                                                    'talla'
                                                ]
                                            ) ?>

                                        </span>

                                    </td>


                                    <!--
                                    ---------------------------------
                                    CANTIDAD
                                    ---------------------------------
                                    -->

                                    <td>

                                        <?= (int)
                                            $uniforme[
                                                'cantidad'
                                            ]
                                        ?>

                                    </td>


                                    <!--
                                    ---------------------------------
                                    VALOR
                                    ---------------------------------
                                    -->

                                    <td
                                        class="fw-bold"
                                    >

                                        $

                                        <?= number_format(
                                            (float)$uniforme[
                                                'valor'
                                            ] *
                                            (int)$uniforme[
                                                'cantidad'
                                            ],
                                            2,
                                            '.',
                                            ','
                                        ) ?>

                                    </td>


                                    <!--
                                    ---------------------------------
                                    ESTADO ENTREGA
                                    ---------------------------------
                                    -->

                                    <td>


                                        <?php if (
                                            $uniforme[
                                                'estado_entrega'
                                            ] === 'entregado'
                                        ): ?>


                                            <span
                                                class="badge
                                                       bg-success"
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


                                    <!--
                                    ---------------------------------
                                    ESTADO PAGO
                                    ---------------------------------
                                    -->

                                    <td>


                                        <?php if (
                                            $uniforme[
                                                'estado_pago'
                                            ] === 'pagado'
                                        ): ?>


                                            <span
                                                class="badge
                                                       bg-success"
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


                                    <!--
                                    ---------------------------------
                                    FECHA DE ENTREGA
                                    ---------------------------------
                                    -->

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


   <!--
=================================================
ACCIONES
=================================================

Botones:
- Editar uniforme
- Ver uniforme

Se utiliza flexbox de Bootstrap para mantener
los botones juntos, centrados y con separación.
=================================================
-->

<td class="text-center">

    <?php if (
        tiene_permiso('financiero_uniformes')
    ): ?>

        <div
            class="d-inline-flex
                   align-items-center
                   justify-content-center
                   gap-1
                   acciones-uniforme"
        >

            <!--
            -----------------------------------------
            EDITAR UNIFORME
            -----------------------------------------
            -->

            <a
                href="editar_uniforme.php?id=<?= (int)$uniforme['id'] ?>"
                class="btn
                       btn-sm
                       btn-outline-primary
                       btn-accion-uniforme"
                title="Editar uniforme"
                aria-label="Editar uniforme"
            >

                <i class="fa-solid fa-pen"></i>

            </a>


            <!--
            -----------------------------------------
            VER UNIFORME
            -----------------------------------------
            -->

            <a
                href="ver_uniforme.php?id=<?= (int)$uniforme['id'] ?>"
                class="btn
                       btn-sm
                       btn-outline-dark
                       btn-accion-uniforme"
                title="Ver uniforme"
                aria-label="Ver uniforme"
            >

                <i class="fa-solid fa-eye"></i>

            </a>

        </div>

    <?php else: ?>

        <!--
        -----------------------------------------
        USUARIO SIN PERMISO DE EDICIÓN
        -----------------------------------------
        
        Importante:
        Se utiliza ver_uniforme.php y no ver.php.
        -----------------------------------------
        -->

        <div
            class="d-inline-flex
                   align-items-center
                   justify-content-center
                   acciones-uniforme"
        >

            <a
                href="ver_uniforme.php?id=<?= (int)$uniforme['id'] ?>"
                class="btn
                       btn-sm
                       btn-outline-dark
                       btn-accion-uniforme"
                title="Ver uniforme"
                aria-label="Ver uniforme"
            >

                <i class="fa-solid fa-eye"></i>

            </a>

        </div>

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


</div>


<?php

/*
=================================================
14. FOOTER DEL MÓDULO
=================================================
*/

include(
    "../../../template/footer_modulos.php"
);

?>