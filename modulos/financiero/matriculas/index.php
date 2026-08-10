<?php
session_start();

require_once "../../../includes/conexion.php";

/** @var PDO $conexion */

// =====================================================
// FILTROS
// =====================================================

$filtro_estado = $_GET['estado'] ?? '';
$filtro_temporada = $_GET['temporada'] ?? '';


// =====================================================
// TEMPORADAS
// =====================================================

$stmtTemporadas = $conexion->query("
    SELECT id, nombre
    FROM temporada
    ORDER BY nombre DESC
");

$temporadas = $stmtTemporadas->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// CONDICIONES
// =====================================================

$where = [];
$params = [];

if (!empty($filtro_estado)) {
    $where[] = "m.estado = :estado";
    $params[':estado'] = $filtro_estado;
}

if (!empty($filtro_temporada)) {
    $where[] = "m.temporada_id = :temporada_id";
    $params[':temporada_id'] = $filtro_temporada;
}

$whereSQL = '';

if (!empty($where)) {
    $whereSQL = "WHERE " . implode(" AND ", $where);
}


// =====================================================
// MATRÍCULAS
// =====================================================

$sql = "
    SELECT
        m.id,
        m.deportista_id,
        m.categoria_id,
        m.temporada_id,
        m.usuario_id,
        m.valor,
        m.fecha_matricula,
        m.estado,

        d.nombre AS deportista_nombre,

        c.nombre AS categoria_nombre,

        t.nombre AS temporada_nombre,

        u.nombre AS acudiente_nombre,
        u.telefono AS telefono_acudiente

    FROM matricula m

    INNER JOIN deportista d
        ON d.id = m.deportista_id

    INNER JOIN categoria c
        ON c.id = m.categoria_id

    INNER JOIN temporada t
        ON t.id = m.temporada_id

    LEFT JOIN usuario u
        ON u.id = m.usuario_id

    $whereSQL

    ORDER BY m.fecha_matricula DESC, d.nombre ASC
";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);

$matriculas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// RESUMEN
// =====================================================

$stmtResumen = $conexion->query("
    SELECT

        COUNT(*) AS total_matriculas,

        SUM(CASE
            WHEN estado = 'activa'
            THEN 1
            ELSE 0
        END) AS matriculas_activas,

        SUM(CASE
            WHEN estado = 'pagada'
            THEN 1
            ELSE 0
        END) AS matriculas_pagadas,

        COALESCE(SUM(valor), 0) AS total_matriculado

    FROM matricula
");

$resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);

$total_matriculas = (int) ($resumen['total_matriculas'] ?? 0);
$matriculas_activas = (int) ($resumen['matriculas_activas'] ?? 0);
$matriculas_pagadas = (int) ($resumen['matriculas_pagadas'] ?? 0);
$total_matriculado = (float) ($resumen['total_matriculado'] ?? 0);

?>

<?php include "../../includes/header_dashboard.php"; ?>

<div class="container-fluid">

    <!-- =================================================
         VOLVER AL DASHBOARD
    ================================================== -->

    <div class="mb-3">

        <a
            href="/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Volver al Dashboard
        </a>

    </div>


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">

                <i class="fa-solid fa-file-signature text-primary"></i>

                Matrículas

            </h2>

            <p class="text-muted mb-0">
                Gestión de matrículas y pagos de inscripción.
            </p>

        </div>


        <div>

            <a
                href="nueva.php"
                class="btn btn-primary fw-bold shadow-sm"
            >

                <i class="fa-solid fa-plus me-1"></i>

                Nueva Matrícula

            </a>

        </div>

    </div>


    <!-- =================================================
         MENSAJES
    ================================================== -->

    <?php if (isset($_GET['msg'])): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <?= htmlspecialchars($_GET['msg']) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =================================================
         DASHBOARD CARDS
    ================================================== -->

    <div class="row g-3 mb-4">

        <!-- TOTAL -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm border-start border-primary border-4">

                <div class="card-body">

                    <small class="text-muted fw-bold text-uppercase">
                        Total Matrículas
                    </small>

                    <h3 class="fw-bold text-primary mb-0">
                        <?= $total_matriculas ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- ACTIVAS -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm border-start border-success border-4">

                <div class="card-body">

                    <small class="text-muted fw-bold text-uppercase">
                        Matrículas Activas
                    </small>

                    <h3 class="fw-bold text-success mb-0">
                        <?= $matriculas_activas ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- PAGADAS -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm border-start border-info border-4">

                <div class="card-body">

                    <small class="text-muted fw-bold text-uppercase">
                        Matrículas Pagadas
                    </small>

                    <h3 class="fw-bold text-info mb-0">
                        <?= $matriculas_pagadas ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- VALOR -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm border-start border-warning border-4">

                <div class="card-body">

                    <small class="text-muted fw-bold text-uppercase">
                        Valor Matriculado
                    </small>

                    <h3 class="fw-bold text-warning mb-0">

                        $
                        <?= number_format(
                            $total_matriculado,
                            2
                        ) ?>

                    </h3>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         FILTROS
    ================================================== -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-3 align-items-center"
            >

                <!-- TEMPORADA -->

                <div class="col-md-4">

                    <label class="form-label fw-bold">
                        Temporada
                    </label>

                    <select
                        name="temporada"
                        class="form-select"
                    >

                        <option value="">
                            Todas
                        </option>

                        <?php foreach ($temporadas as $temporada): ?>

                            <option
                                value="<?= $temporada['id'] ?>"
                                <?= $filtro_temporada == $temporada['id']
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $temporada['nombre']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- ESTADO -->

                <div class="col-md-4">

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
                            value="activa"
                            <?= $filtro_estado === 'activa'
                                ? 'selected'
                                : '' ?>
                        >
                            Activa
                        </option>

                        <option
                            value="pagada"
                            <?= $filtro_estado === 'pagada'
                                ? 'selected'
                                : '' ?>
                        >
                            Pagada
                        </option>

                    </select>

                </div>


                <!-- BOTÓN -->

                <div class="col-md-4 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn btn-dark w-100 fw-bold mt-4"
                    >

                        <i class="fa-solid fa-filter me-1"></i>

                        Filtrar

                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- =================================================
         TABLA
    ================================================== -->

    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-dark">

                        <tr>

                            <th>
                                Deportista
                            </th>

                            <th>
                                Categoría
                            </th>

                            <th>
                                Temporada
                            </th>

                            <th>
                                Acudiente
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Valor
                            </th>

                            <th>
                                Estado
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
                                colspan="8"
                                class="text-center py-4 text-muted"
                            >

                                <i class="fa-solid fa-file-signature fa-2x mb-2"></i>

                                <br>

                                No hay matrículas registradas.

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($matriculas as $m): ?>

                            <tr>


                                <!-- DEPORTISTA -->

                                <td class="fw-bold">

                                    <?= htmlspecialchars(
                                        $m['deportista_nombre']
                                    ) ?>

                                </td>


                                <!-- CATEGORÍA -->

                                <td>

                                    <?= htmlspecialchars(
                                        $m['categoria_nombre']
                                    ) ?>

                                </td>


                                <!-- TEMPORADA -->

                                <td>

                                    <?= htmlspecialchars(
                                        $m['temporada_nombre']
                                    ) ?>

                                </td>


                                <!-- ACUDIENTE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $m['acudiente_nombre']
                                        ?? 'Sin acudiente'
                                    ) ?>

                                </td>


                                <!-- FECHA -->

                                <td>

                                    <?= !empty($m['fecha_matricula'])
                                        ? date(
                                            'd/m/Y',
                                            strtotime(
                                                $m['fecha_matricula']
                                            )
                                        )
                                        : '-'
                                    ?>

                                </td>


                                <!-- VALOR -->

                                <td class="fw-bold">

                                    $
                                    <?= number_format(
                                        $m['valor'],
                                        2
                                    ) ?>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <?php if ($m['estado'] === 'activa'): ?>

                                        <span class="badge bg-success">
                                            Activa
                                        </span>

                                    <?php elseif ($m['estado'] === 'pagada'): ?>

                                        <span class="badge bg-primary">
                                            Pagada
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">

                                            <?= htmlspecialchars(
                                                ucfirst($m['estado'])
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACCIONES -->

                                <td class="text-center">

                                    <!-- PAGAR -->

                                    <?php if ($m['estado'] !== 'pagada'): ?>

                                        <a
                                            href="pagar.php?id=<?= $m['id'] ?>"
                                            class="btn btn-sm btn-success me-1 fw-bold"
                                        >

                                            <i class="fa-solid fa-dollar-sign"></i>

                                            Pagar

                                        </a>

                                    <?php endif; ?>


                                    <!-- WHATSAPP -->

                                    <?php

                                    $telefono = preg_replace(
                                        '/[^0-9]/',
                                        '',
                                        $m['telefono_acudiente'] ?? ''
                                    );

                                    $msg_wa =
                                        "Hola " .
                                        ($m['acudiente_nombre'] ?? '') .
                                        ", te recordamos la matrícula de " .
                                        $m['deportista_nombre'] .
                                        " para la temporada " .
                                        $m['temporada_nombre'] .
                                        ". Valor: $" .
                                        number_format(
                                            $m['valor'],
                                            2
                                        ) .
                                        ".";

                                    $url_wa = !empty($telefono)
                                        ? "https://wa.me/" .
                                            $telefono .
                                            "?text=" .
                                            urlencode($msg_wa)
                                        : "#";

                                    ?>


                                    <a
                                        href="<?= $url_wa ?>"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-success me-1
                                        <?= empty($telefono)
                                            ? 'disabled'
                                            : '' ?>"
                                    >

                                        <i class="fa-brands fa-whatsapp"></i>

                                        Recordar

                                    </a>


                                    <!-- VER -->

                                    <a
                                        href="ver.php?id=<?= $m['id'] ?>"
                                        class="btn btn-sm btn-outline-dark"
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                    </a>

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