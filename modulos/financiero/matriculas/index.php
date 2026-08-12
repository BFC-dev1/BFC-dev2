<?php

/*
=========================================================
MÓDULO FINANCIERO - MATRÍCULAS
=========================================================

Archivo:
    /modulos/financiero/matriculas/index.php

Funciones:
    - Listar matrículas
    - Filtrar matrículas
    - Mostrar resumen financiero
    - Registrar pago
    - Ver matrícula
    - Editar matrícula
    - Eliminar matrícula
    - Crear nueva matrícula

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
VERIFICAR PERMISOS
---------------------------------------------------------

El usuario puede entrar si posee:

    matriculas
    o
    ver_matriculas

=========================================================
*/

if (
    !tiene_permiso('matriculas') &&
    !tiene_permiso('ver_matriculas')
) {

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
3. CONEXIÓN A LA BASE DE DATOS
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
BÚSQUEDA
---------------------------------------------------------

Permite buscar por:

- Nombre del deportista
- Documento
---------------------------------------------------------
*/

$filtro_busqueda = trim(
    $_GET['buscar'] ?? ''
);


/*
---------------------------------------------------------
CATEGORÍA
---------------------------------------------------------
*/

$filtro_categoria =
    $_GET['categoria'] ?? '';


/*
---------------------------------------------------------
AÑO
---------------------------------------------------------
*/

$filtro_anio =
    $_GET['anio'] ?? '';


/*
---------------------------------------------------------
ESTADO
---------------------------------------------------------
*/

$filtro_estado =
    $_GET['estado'] ?? '';


/*
=========================================================
5. OBTENER AÑOS DISPONIBLES
=========================================================
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

$anios = $stmtAnios->fetchAll(
    PDO::FETCH_COLUMN
);


/*
=========================================================
6. OBTENER CATEGORÍAS
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
7. CONSTRUIR CONDICIONES DE CONSULTA
=========================================================
*/

$where = [];

$params = [];


/*
---------------------------------------------------------
FILTRO DE BÚSQUEDA
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
FILTRO DE CATEGORÍA
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
FILTRO DE AÑO
---------------------------------------------------------
*/

if ($filtro_anio !== '') {

    $where[] =
        "m.anio = :anio";

    $params[':anio'] =
        $filtro_anio;
}


/*
---------------------------------------------------------
FILTRO DE ESTADO
---------------------------------------------------------
*/

if ($filtro_estado !== '') {

    $where[] =
        "m.estado = :estado";

    $params[':estado'] =
        $filtro_estado;
}


/*
=========================================================
8. CONSTRUIR WHERE
=========================================================
*/

$whereSQL = '';

if (!empty($where)) {

    $whereSQL =
        "WHERE " . implode(" AND ", $where);
}


/*
=========================================================
9. CONSULTAR MATRÍCULAS
=========================================================

Relaciones:

matriculas.id_deportista
        ↓
deportista.id

deportista.categoria_id
        ↓
categoria.id

=========================================================
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

    {$whereSQL}

    ORDER BY
        m.fecha_matricula DESC,
        d.nombre ASC

";


$stmt = $conexion->prepare($sql);

$stmt->execute($params);

$matriculas =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
=========================================================
10. RESUMEN FINANCIERO
=========================================================
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


$resumen =
    $stmtResumen->fetch(PDO::FETCH_ASSOC);


/*
=========================================================
11. VARIABLES DEL RESUMEN
=========================================================
*/

$total_matriculas =
    (int) (
        $resumen['total_matriculas'] ?? 0
    );


$matriculas_activas =
    (int) (
        $resumen['matriculas_activas'] ?? 0
    );


$matriculas_pagadas =
    (int) (
        $resumen['matriculas_pagadas'] ?? 0
    );


$total_matriculado =
    (float) (
        $resumen['total_matriculado'] ?? 0
    );


/*
=========================================================
12. HEADER DEL MÓDULO
=========================================================
*/

$modulo_actual =
    'Financiero';

$submodulo_actual =
    'Matrículas';


include(
    "../../../template/header_modulos.php"
);

?>


<!-- =====================================================
     CONTENIDO PRINCIPAL
     ===================================================== -->

<div class="container-fluid py-4">


    <!-- =================================================
         BOTÓN VOLVER AL DASHBOARD
         ================================================= -->

    <div class="mb-3">

        <a
            href="<?= rtrim($url_base, '/') ?>/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            Volver al Dashboard

        </a>

    </div>


    <!-- =================================================
         ENCABEZADO DEL MÓDULO
         ================================================= -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <h2 class="fw-bold mb-1">

                <i
                    class="fa-solid fa-file-signature text-primary me-2"
                ></i>

                Matrículas

            </h2>


            <p class="text-muted mb-0">

                Gestión de matrículas y pagos de inscripción.

            </p>

        </div>


        <!-- =================================================
             NUEVA MATRÍCULA
             ================================================= -->

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


    <!-- =================================================
         TARJETAS DE RESUMEN
         ================================================= -->

    <div class="row g-3 mb-4">


        <!-- TOTAL MATRÍCULAS -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <small
                        class="text-muted fw-bold text-uppercase"
                    >
                        Total Matrículas
                    </small>

                    <h3
                        class="fw-bold text-primary mb-0 mt-1"
                    >
                        <?= $total_matriculas ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- MATRÍCULAS ACTIVAS -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <small
                        class="text-muted fw-bold text-uppercase"
                    >
                        Matrículas Activas
                    </small>

                    <h3
                        class="fw-bold text-success mb-0 mt-1"
                    >
                        <?= $matriculas_activas ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- MATRÍCULAS PAGADAS -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <small
                        class="text-muted fw-bold text-uppercase"
                    >
                        Matrículas Pagadas
                    </small>

                    <h3
                        class="fw-bold text-info mb-0 mt-1"
                    >
                        <?= $matriculas_pagadas ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- VALOR MATRICULADO -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <small
                        class="text-muted fw-bold text-uppercase"
                    >
                        Valor Matriculado
                    </small>

                    <h3
                        class="fw-bold text-warning mb-0 mt-1"
                    >

                        $

                        <?= number_format(
                            $total_matriculado,
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
         ================================================= -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-3 align-items-end"
            >


                <!-- =====================================
                     BÚSQUEDA
                     ===================================== -->

                <div class="col-md-3">

                    <label class="form-label fw-bold">

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


                <!-- =====================================
                     CATEGORÍA
                     ===================================== -->

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

                                <?= htmlspecialchars(
                                    $categoria['nombre']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- =====================================
                     AÑO
                     ===================================== -->

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


                <!-- =====================================
                     ESTADO
                     ===================================== -->

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


                <!-- =====================================
                     BOTÓN FILTRAR
                     ===================================== -->

                <div class="col-md-3">

                    <button
                        type="submit"
                        class="btn btn-dark fw-bold w-100"
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
         TABLA DE MATRÍCULAS
         ================================================= -->

    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table
                    class="table table-hover align-middle mb-0"
                >


                    <!-- =================================
                         ENCABEZADO
                         ================================= -->

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


                    <!-- =================================
                         CUERPO
                         ================================= -->

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


                                    <!-- =========================
                                         DEPORTISTA
                                         ========================= -->

                                    <td class="px-3">

                                        <div class="fw-bold">

                                            <?= htmlspecialchars(
                                                $matricula[
                                                    'deportista_nombre'
                                                ]
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- =========================
                                         DOCUMENTO
                                         ========================= -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $matricula[
                                                'deportista_documento'
                                            ] ?? '-'
                                        ) ?>

                                    </td>


                                    <!-- =========================
                                         AÑO
                                         ========================= -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $matricula['anio']
                                        ) ?>

                                    </td>


                                    <!-- =========================
                                         FECHA MATRÍCULA
                                         ========================= -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $matricula[
                                                    'fecha_matricula'
                                                ]
                                            )
                                        ): ?>

                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $matricula[
                                                        'fecha_matricula'
                                                    ]
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


                                    <!-- =========================
                                         CATEGORÍA
                                         ========================= -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $matricula[
                                                'categoria_nombre'
                                            ] ?? '-'
                                        ) ?>

                                    </td>


                                    <!-- =========================
                                         MONTO
                                         ========================= -->

                                    <td class="fw-bold">

                                        $

                                        <?= number_format(
                                            (float)$matricula['monto'],
                                            2,
                                            '.',
                                            ','
                                        ) ?>

                                    </td>


                                    <!-- =========================
                                         ESTADO
                                         ========================= -->

                                    <td>


                                        <?php if (
                                            $matricula['estado']
                                            === 'pagado'
                                        ): ?>


                                            <span
                                                class="badge bg-success"
                                            >

                                                <i
                                                    class="fa-solid fa-check me-1"
                                                ></i>

                                                Pagado

                                            </span>


                                        <?php elseif (
                                            $matricula['estado']
                                            === 'pendiente'
                                        ): ?>


                                            <span
                                                class="badge bg-warning text-dark"
                                            >

                                                <i
                                                    class="fa-solid fa-clock me-1"
                                                ></i>

                                                Pendiente

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="badge bg-secondary"
                                            >

                                                <i
                                                    class="fa-solid fa-ban me-1"
                                                ></i>

                                                Anulado

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- =========================
                                         FECHA DE PAGO
                                         ========================= -->

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


                                    <!-- =========================
                                         MÉTODO DE PAGO
                                         ========================= -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $matricula[
                                                'metodo_pago'
                                            ] ?? '-'
                                        ) ?>

                                    </td>


                                    <!-- =========================
                                         ACCIONES
                                         ========================= -->

                                    <td>

                                        <div
                                            class="d-flex justify-content-center align-items-center gap-1 flex-nowrap"
                                        >


                                            <!-- =================
                                                 PAGAR
                                                 ================= -->

                                            <?php if (
                                                tiene_permiso(
                                                    'matriculas'
                                                )
                                                &&
                                                $matricula['estado']
                                                === 'pendiente'
                                            ): ?>

                                                <a
                                                    href="pagar_matricula.php?id=<?= (int)$matricula['id'] ?>"
                                                    class="btn btn-sm btn-success"
                                                    title="Registrar pago"
                                                >

                                                    <i
                                                        class="fa-solid fa-dollar-sign me-1"
                                                    ></i>

                                                    Pagar

                                                </a>

                                            <?php endif; ?>


                                            <!-- =================
                                                 VER
                                                 ================= -->

                                            <a
                                                href="ver_matricula.php?id=<?= (int)$matricula['id'] ?>"
                                                class="btn btn-sm btn-outline-dark"
                                                title="Ver matrícula"
                                            >

                                                <i
                                                    class="fa-solid fa-eye me-1"
                                                ></i>

                                                Ver

                                            </a>


                                            <!-- =================
                                                 EDITAR
                                                 ================= -->

                                            <?php if (
                                                tiene_permiso(
                                                    'matriculas'
                                                )
                                            ): ?>

                                                <a
                                                    href="editar_matricula.php?id=<?= (int)$matricula['id'] ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Editar matrícula"
                                                >

                                                    <i
                                                        class="fa-solid fa-pen me-1"
                                                    ></i>

                                                    Editar

                                                </a>


                                                <!-- ===============
                                                     ELIMINAR
                                                     =============== -->

                                                <a
                                                    href="eliminar_matricula.php?id=<?= (int)$matricula['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Eliminar matrícula"
                                                    onclick="return confirm('¿Está seguro de eliminar esta matrícula?');"
                                                >

                                                    <i
                                                        class="fa-solid fa-trash me-1"
                                                    ></i>

                                                    Eliminar

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


<?php

/*
=========================================================
13. FOOTER DEL MÓDULO
=========================================================
*/

include(
    "../../../template/footer_modulos.php"
);

?>