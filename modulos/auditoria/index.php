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
RECIBIR FILTROS DE FECHA
=================================================
*/

$fecha_desde = $_GET["fecha_desde"] ?? "";
$fecha_hasta = $_GET["fecha_hasta"] ?? "";

/*
=================================================
CONSTRUIR FILTRO SQL
=================================================
*/

$where = [];
$parametros = [];

if($fecha_desde != ""){

    $where[] = "DATE(fecha) >= :desde";
    $parametros[":desde"] = $fecha_desde;

}

if($fecha_hasta != ""){

    $where[] = "DATE(fecha) <= :hasta";
    $parametros[":hasta"] = $fecha_hasta;

}


/*
=================================================
CONSULTAR AUDITORÍA
=================================================
*/

$sql = "

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

";

if(count($where) > 0){

    $sql .= " WHERE " . implode(" AND ", $where);

}

$sql .= "

GROUP BY operacion_id

ORDER BY fecha DESC

";

$stmt = $conexion->prepare($sql);

$stmt->execute($parametros);

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

<!--
=================================================
FILTROS POR FECHA
=================================================
-->

<form method="GET" class="row g-3 mb-4">

    <div class="col-md-3">

        <label>Desde</label>

        <input
            type="date"
            name="fecha_desde"
            class="form-control"
            value="<?= htmlspecialchars($fecha_desde) ?>">

    </div>

    <div class="col-md-3">

        <label>Hasta</label>

        <input
            type="date"
            name="fecha_hasta"
            class="form-control"
            value="<?= htmlspecialchars($fecha_hasta) ?>">

    </div>

    <div class="col-md-6 d-flex align-items-end gap-2">

        <button class="btn btn-primary">

            Filtrar

        </button>

        <a href="index.php" class="btn btn-secondary">

            Limpiar

        </a>

    </div>

</form>


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

            <th>Eliminar</th>

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

    <td>

    <a
        href="eliminar.php?operacion=<?= urlencode($fila["operacion_id"]) ?>"
        class="btn btn-danger btn-sm"
        onclick="return confirm('¿Eliminar esta operación?');">

        Eliminar

    </a>

</td>

</tr>

<?php } ?>

    </tbody>

</table>

<?php include("../../template/footer_modulos.php"); ?>