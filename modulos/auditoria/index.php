<?php

/*
=================================================
INICIAR SESIÓN
=================================================
*/
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/*
=================================================
CONEXIÓN
=================================================
*/
include("../../modulos/conexion_modulos.php");

/*
=================================================
CONSULTAR OPERACIONES DE AUDITORÍA
=================================================
*/

$stmt = $conexion->query("
SELECT

    operacion_id,

    MAX(fecha) AS fecha,

    usuario.nombre AS usuario,

    tabla_afectada,

    accion,

    COUNT(*) AS cambios,

    MAX(ip) AS ip

FROM auditoria

LEFT JOIN usuario
ON auditoria.usuario_id = usuario.id

GROUP BY operacion_id

ORDER BY fecha DESC
");

$auditorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include("../../template/header_modulos.php"); ?>


<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="d-flex gap-2">

        <a
            href="/BFC-dev2/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >
            ← Volver al Dashboard
        </a>

    </div>

</div>


<h2 class="mb-4">
    Auditoría del Sistema
</h2>

<table class="table table-bordered table-hover">

    <thead class="table-dark">

        <tr>

            <th>Fecha</th>

            <th>Usuario</th>

            <th>Tabla</th>

            <th>Acción</th>

            <th>Cambios</th>

            <th>IP</th>

            <th>Detalle</th>

        </tr>

    </thead>

    <tbody>

<?php foreach($auditorias as $fila){ ?>

<tr>

    <td>
        <?= htmlspecialchars($fila["fecha"]) ?>
    </td>


    <td>
        <?= htmlspecialchars($fila["usuario"] ?? "Sistema") ?>
    </td>


    <td>
        <?= htmlspecialchars($fila["tabla_afectada"]) ?>
    </td>


    <td>
        <?= htmlspecialchars($fila["accion"]) ?>
    </td>


    <td>
        <?= htmlspecialchars($fila["cambios"]) ?>
    </td>


    <td>
        <?= htmlspecialchars($fila["ip"]) ?>
    </td>


    <td>

        <a
            href="detalle.php?operacion=<?= urlencode($fila["operacion_id"]) ?>"
            class="btn btn-primary btn-sm"
        >
            Ver
        </a>

    </td>

</tr>

<?php } ?>

    </tbody>

</table>

<?php include("../../template/footer_modulos.php"); ?>