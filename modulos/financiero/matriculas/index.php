<?php

/*
=================================================
MÓDULO FINANCIERO - MATRÍCULAS
=================================================

Este archivo sigue el mismo framework utilizado
por el módulo de Mensualidades.

Orden del archivo:

1. Configuración
2. Verificación de permisos
3. Auditoría
4. Conexión
5. Filtros
6. Consulta de matrículas
7. Resumen financiero
8. Header del módulo
9. Contenido visual
10. Footer del módulo

=================================================
*/


/*
=================================================
CONFIGURACIÓN Y PERMISOS
=================================================
*/

require_once("../../../includes/config.php");
require_once("../../../includes/verificar_roles.php");


/*
=================================================
VERIFICAR PERMISOS
=================================================

Permite el acceso si el usuario tiene permiso
de gestión de matrículas o solamente permiso
de consulta.

=================================================
*/

if (
    !tiene_permiso('matriculas') &&
    !tiene_permiso('ver_matriculas')
) {

    header("Location: " . $url_base . "/index.php");
    exit;

}


/*
=================================================
AUDITORÍA
=================================================
*/

include("../../../modulos/auditoria/funciones/registrar_auditoria.php");


/*
=================================================
CONEXIÓN A LA BASE DE DATOS
=================================================
*/

include("../../../modulos/conexion_modulos.php");


/*
=================================================
FILTROS
=================================================
*/

$filtro_busqueda = trim($_GET['buscar'] ?? '');
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_anio = $_GET['anio'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';

/*
=================================================
OBTENER AÑOS DISPONIBLES
=================================================

Se obtienen los años registrados en la tabla
matriculas y se garantiza que el año actual
también aparezca aunque todavía no existan
matrículas registradas.

=================================================
*/

$anio_actual = date('Y');

$stmtAnios = $conexion->query("
    SELECT DISTINCT anio
    FROM matriculas
    WHERE anio IS NOT NULL

    UNION

    SELECT {$anio_actual}

    ORDER BY anio DESC
");

$anios = $stmtAnios->fetchAll(PDO::FETCH_COLUMN);


/*
=================================================
OBTENER CATEGORÍAS
=================================================

Se obtienen las categorías disponibles para
permitir filtrar las matrículas.

=================================================
*/

$stmtCategorias = $conexion->query("
    SELECT id, nombre
    FROM categoria
    ORDER BY nombre ASC
");

$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

/*
=================================================
CONDICIONES DE CONSULTA
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

    $params[':buscar'] = '%' . $filtro_busqueda . '%';
}


/*
-------------------------------------------------
FILTRO POR CATEGORÍA
-------------------------------------------------
*/

if ($filtro_categoria !== '') {

    $where[] = "d.categoria_id = :categoria_id";

    $params[':categoria_id'] = $filtro_categoria;
}


/*
-------------------------------------------------
FILTRO POR AÑO
-------------------------------------------------
*/

if ($filtro_anio !== '') {

    $where[] = "m.anio = :anio";

    $params[':anio'] = $filtro_anio;

}


/*
-------------------------------------------------
FILTRO POR ESTADO
-------------------------------------------------
*/

if ($filtro_estado !== '') {

    $where[] = "m.estado = :estado";

    $params[':estado'] = $filtro_estado;

}


/*
=================================================
CONSTRUIR WHERE
=================================================
*/

$whereSQL = '';

if (!empty($where)) {

    $whereSQL = "WHERE " . implode(" AND ", $where);

}


/*
=================================================
CONSULTAR MATRÍCULAS
=================================================

ESTRUCTURA REAL DE LA TABLA:

matriculas
- id
- id_deportista
- anio
- monto
- fecha_matricula
- estado
- fecha_pago
- metodo_pago
- observacion
- id_usuario_registra
- created_at

Relación:

matriculas.id_deportista
        ↓
deportista.id

=================================================
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

INNER JOIN categoria c
    ON c.id = d.categoria_id

$whereSQL

    ORDER BY
        m.fecha_matricula DESC,
        d.nombre ASC

";


$stmt = $conexion->prepare($sql);

$stmt->execute($params);

$matriculas = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
=================================================
RESUMEN GENERAL
=================================================
*/

$stmtResumen = $conexion->query("

    SELECT

        COUNT(*) AS total_matriculas,

        SUM(
            CASE
                WHEN estado != 'anulado'
                THEN 1
                ELSE 0
            END
        ) AS matriculas_activas,

        SUM(
            CASE
                WHEN estado = 'pagado'
                THEN 1
                ELSE 0
            END
        ) AS matriculas_pagadas,

        COALESCE(
            SUM(
                CASE
                    WHEN estado != 'anulado'
                    THEN monto
                    ELSE 0
                END
            ),
            0
        ) AS total_matriculado

    FROM matriculas

");


$resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);


/*
=================================================
VARIABLES DEL RESUMEN
=================================================
*/

$total_matriculas = (int) (
    $resumen['total_matriculas'] ?? 0
);


$matriculas_activas = (int) (
    $resumen['matriculas_activas'] ?? 0
);


$matriculas_pagadas = (int) (
    $resumen['matriculas_pagadas'] ?? 0
);


$total_matriculado = (float) (
    $resumen['total_matriculado'] ?? 0
);


/*
=================================================
HEADER DEL MÓDULO
=================================================

IMPORTANTE:

El header se carga DESPUÉS de toda la lógica PHP
para evitar el parpadeo visual que presentaba
el módulo.

=================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Matrículas';

include("../../../includes/config.php");
include("../../../template/header_modulos.php");

?>


<!--
=================================================
CONTENIDO DEL MÓDULO
=================================================
-->

<div class="container-fluid py-4">


    <!--
    =================================================
    VOLVER AL DASHBOARD
    =================================================
    -->

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div class="d-flex gap-2">

            <a
                href="<?= $url_base ?>/modulos/dashboard/index.php"
                class="btn btn-outline-dark"
            >

                <i class="fa-solid fa-arrow-left me-1"></i>

                Volver al Dashboard

            </a>

        </div>

    </div>



    <!--
    =================================================
    ENCABEZADO
    =================================================
    -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">

                <i class="fa-solid fa-file-signature text-primary me-2"></i>

                Matrículas

            </h2>

            <p class="text-muted mb-0">

                Gestión de matrículas y pagos de inscripción.

            </p>

        </div>


<?php if (tiene_permiso('matriculas')): ?>

<a
    href="<?= rtrim($url_base, '/') ?>/modulos/financiero/matriculas/nueva_matricula.php"
    class="btn btn-primary fw-bold"
>

    <i class="fa-solid fa-plus me-1"></i>

    Nueva Matrícula

</a>

<?php endif; ?>

</div>



    <!--
    =================================================
    TARJETAS DE RESUMEN
    =================================================
    -->

    <div class="row g-3 mb-4">


        <!-- TOTAL MATRÍCULAS -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted fw-bold text-uppercase">

                                Total Matrículas

                            </small>

                            <h3 class="fw-bold text-primary mb-0 mt-1">

                                <?= $total_matriculas ?>

                            </h3>

                        </div>


                        <div class="text-primary fs-2">

                            <i class="fa-solid fa-file-signature"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- MATRÍCULAS ACTIVAS -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted fw-bold text-uppercase">

                                Matrículas Activas

                            </small>

                            <h3 class="fw-bold text-success mb-0 mt-1">

                                <?= $matriculas_activas ?>

                            </h3>

                        </div>


                        <div class="text-success fs-2">

                            <i class="fa-solid fa-circle-check"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- MATRÍCULAS PAGADAS -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted fw-bold text-uppercase">

                                Matrículas Pagadas

                            </small>

                            <h3 class="fw-bold text-info mb-0 mt-1">

                                <?= $matriculas_pagadas ?>

                            </h3>

                        </div>


                        <div class="text-info fs-2">

                            <i class="fa-solid fa-money-bill-wave"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- VALOR MATRICULADO -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted fw-bold text-uppercase">

                                Valor Matriculado

                            </small>

                            <h3 class="fw-bold text-warning mb-0 mt-1">

                                $

                                <?= number_format(
                                    $total_matriculado,
                                    2,
                                    '.',
                                    ','
                                ) ?>

                            </h3>

                        </div>


                        <div class="text-warning fs-2">

                            <i class="fa-solid fa-dollar-sign"></i>

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

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

<form
    method="GET"
    class="row g-3 align-items-end"
>

    <!-- BÚSQUEDA -->

    <div class="col-md-3">

        <label class="form-label fw-bold">
            Buscar deportista
        </label>

        <input
            type="text"
            name="buscar"
            class="form-control"
            placeholder="Nombre o documento..."
            value="<?= htmlspecialchars($filtro_busqueda) ?>"
        >

    </div>


    <!-- CATEGORÍA -->

    <div class="col-md-2">

        <label class="form-label fw-bold">
            Categoría
        </label>

        <select
            name="categoria"
            class="form-select"
        >

            <option value="">
                Todas
            </option>

            <?php foreach ($categorias as $categoria): ?>

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

                    <?= htmlspecialchars($categoria['nombre']) ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- AÑO -->

    <div class="col-md-2">

        <label class="form-label fw-bold">
            Año
        </label>

        <select
            name="anio"
            class="form-select"
        >

            <option value="">
                Todos
            </option>

            <?php foreach ($anios as $anio): ?>

                <option
                    value="<?= htmlspecialchars($anio) ?>"
                    <?= (
                        (string)$filtro_anio ===
                        (string)$anio
                    )
                        ? 'selected'
                        : ''
                    ?>
                >

                    <?= htmlspecialchars($anio) ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- ESTADO -->

    <div class="col-md-2">

        <label class="form-label fw-bold">
            Estado
        </label>

        <select
            name="estado"
            class="form-select"
        >

            <option value="">
                Todos
            </option>

            <option
                value="pendiente"
                <?= $filtro_estado === 'pendiente'
                    ? 'selected'
                    : ''
                ?>
            >
                Pendiente
            </option>

            <option
                value="pagado"
                <?= $filtro_estado === 'pagado'
                    ? 'selected'
                    : ''
                ?>
            >
                Pagado
            </option>

            <option
                value="anulado"
                <?= $filtro_estado === 'anulado'
                    ? 'selected'
                    : ''
                ?>
            >
                Anulado
            </option>

        </select>

    </div>


    <!-- BOTONES -->

    <div class="col-md-3">

        <div class="d-flex gap-2">

            <button
                type="submit"
                class="btn btn-dark fw-bold flex-grow-1"
            >

                <i class="fa-solid fa-filter me-1"></i>

                Filtrar

            </button>


            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >

                <i class="fa-solid fa-rotate-left"></i>

                Limpiar

            </a>

        </div>

    </div>

</form>

        </div>

    </div>



    <!--
    =================================================
    TABLA DE MATRÍCULAS
    =================================================
    -->

    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">


                    <thead class="table-dark">

                        <tr>

                            <th class="px-3">
                                Deportista
                            </th>

                            <th>
                                Documento
                            </th>

                            <th>
                                Año
                            </th>

                            <th>
                                Fecha Matrícula
                            </th>

                            <th>
    Categoría
</th>

                            <th>
                                Monto
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Fecha Pago
                            </th>

                            <th>
                                Método Pago
                            </th>

                            <th class="text-center">
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php if (empty($matriculas)): ?>


                            <tr>

                                <td
                                    colspan="10"
                                    class="text-center py-5 text-muted"
                                >

                                    <i
                                        class="fa-solid fa-file-signature fa-2x mb-3"
                                    ></i>

                                    <div class="fw-bold">

                                        No hay matrículas registradas.

                                    </div>

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($matriculas as $matricula): ?>


                                <tr>


                                    <!-- DEPORTISTA -->

                                    <td class="px-3">

                                        <div class="fw-bold">

                                            <?= htmlspecialchars(
                                                $matricula['deportista_nombre']
                                            ) ?>

                                        </div>

                                    </td>



                                    <!-- DOCUMENTO -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $matricula['deportista_documento'] ?? '-'
                                        ) ?>

                                    </td>



                                    <!-- AÑO -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $matricula['anio']
                                        ) ?>

                                    </td>



                                    <!-- FECHA MATRÍCULA -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $matricula['fecha_matricula']
                                            )
                                        ): ?>

                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $matricula['fecha_matricula']
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>

<!-- FECHA MATRÍCULA -->

<td>

    <?php if (!empty($matricula['fecha_matricula'])): ?>

        <?= date(
            'd/m/Y',
            strtotime($matricula['fecha_matricula'])
        ) ?>

    <?php else: ?>

        -

    <?php endif; ?>

</td>


<!-- CATEGORÍA -->

<td>

    <?= htmlspecialchars(
        $matricula['categoria_nombre'] ?? '-'
    ) ?>

</td>


<!-- MONTO -->

<td class="fw-bold">

    $

    <?= number_format(
        (float)$matricula['monto'],
        2,
        '.',
        ','
    ) ?>

</td>

                                    <!-- MONTO -->

                                    <td class="fw-bold">

                                        $

                                        <?= number_format(
                                            (float)$matricula['monto'],
                                            2,
                                            '.',
                                            ','
                                        ) ?>

                                    </td>



                                    <!-- ESTADO -->

                                    <td>


                                        <?php if (
                                            $matricula['estado'] === 'pagado'
                                        ): ?>


                                            <span class="badge bg-success">

                                                <i
                                                    class="fa-solid fa-check me-1"
                                                ></i>

                                                Pagado

                                            </span>


                                        <?php elseif (
                                            $matricula['estado'] === 'pendiente'
                                        ): ?>


                                            <span class="badge bg-warning text-dark">

                                                <i
                                                    class="fa-solid fa-clock me-1"
                                                ></i>

                                                Pendiente

                                            </span>


                                        <?php else: ?>


                                            <span class="badge bg-secondary">

                                                <i
                                                    class="fa-solid fa-ban me-1"
                                                ></i>

                                                Anulado

                                            </span>


                                        <?php endif; ?>


                                    </td>



                                    <!-- FECHA DE PAGO -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $matricula['fecha_pago']
                                            )
                                        ): ?>

                                            <?= date(
                                                'd/m/Y H:i',
                                                strtotime(
                                                    $matricula['fecha_pago']
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>



                                    <!-- MÉTODO DE PAGO -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $matricula['metodo_pago'] ?? '-'
                                        ) ?>

                                    </td>



                                    <!-- ACCIONES -->

                                    <td class="text-center">


                                        <?php if (
                                            tiene_permiso('matriculas')
                                        ): ?>


                                            <?php if (
                                                $matricula['estado']
                                                === 'pendiente'
                                            ): ?>

                                                <a
                                                    href="pagar_matricula.php?id=<?= (int)$matricula['id'] ?>"
                                                    class="btn btn-sm btn-success"
                                                    title="Registrar pago"
                                                >

                                                    <i
                                                        class="fa-solid fa-dollar-sign"
                                                    ></i>

                                                    Pagar

                                                </a>

                                            <?php endif; ?>


<a
    href="ver_matricula.php?id=<?= (int)$matricula['id'] ?>"
    class="btn btn-sm btn-outline-dark"
    title="Ver matrícula"
>

                                                <i
                                                    class="fa-solid fa-eye"
                                                ></i>

                                            </a>


                                        <?php else: ?>


                                            <a
                                                href="ver.php?id=<?= (int)$matricula['id'] ?>"
                                                class="btn btn-sm btn-outline-dark"
                                                title="Ver matrícula"
                                            >

                                                <i
                                                    class="fa-solid fa-eye"
                                                ></i>

                                            </a>


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
FOOTER DEL MÓDULO
=================================================
*/

include("../../../template/footer_modulos.php");

?>