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

<?php include("../../template/header_modulos_auditoria.php"); ?>


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
MENSAJE DE ELIMINACIÓN EXITOSA
=================================================
-->

<?php if(isset($_GET["eliminado"])){ ?>

<div class="alert alert-success">

    Los registros del rango fueron eliminados correctamente.

</div>

<?php } ?>

<!--
=================================================
FILTROS POR FECHA
=================================================
-->

<!--
=================================================
FILTROS Y BOTONES DE AUDITORÍA

Contiene:

1. Formulario para filtrar registros por fecha.
2. Botones Filtrar y Limpiar.
3. Formulario independiente para eliminar todos
   los registros comprendidos dentro del rango
   de fechas seleccionado.

Se utilizan dos formularios independientes para
evitar formularios anidados, ya que HTML no
permite tener un <form> dentro de otro <form>.
=================================================
-->

<div class="row g-3 mb-4 align-items-end">

    <!--
    =============================================
    FORMULARIO DE FILTROS
    =============================================
    -->
    <div class="col-md-9">

        <form method="GET">

            <div class="row g-3">

                <!-- FECHA DESDE -->
                <div class="col-md-4">

                    <label>Desde</label>

                    <input
                        type="date"
                        name="fecha_desde"
                        class="form-control"
                        value="<?= htmlspecialchars($fecha_desde) ?>">

                </div>

                <!-- FECHA HASTA -->
                <div class="col-md-4">

                    <label>Hasta</label>

                    <input
                        type="date"
                        name="fecha_hasta"
                        class="form-control"
                        value="<?= htmlspecialchars($fecha_hasta) ?>">

                </div>

                <!-- BOTONES FILTRAR Y LIMPIAR -->
                <div class="col-md-4 d-flex align-items-end gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary">

                        Filtrar

                    </button>

                    <a
                        href="index.php"
                        class="btn btn-secondary">

                        Limpiar

                    </a>

                </div>

            </div>

        </form>

    </div>

    <!--
    =============================================
    FORMULARIO ELIMINAR POR RANGO

    Este formulario solamente aparece cuando
    el usuario ha seleccionado ambas fechas.
    =============================================
    -->

    <?php if($fecha_desde != "" && $fecha_hasta != ""){ ?>

    <div class="col-md-3 d-flex justify-content-start">

        <form
            action="eliminar_rango.php"
            method="POST"
            onsubmit="return confirm('¿Eliminar TODOS los registros comprendidos entre estas fechas?\n\nEsta acción no se puede deshacer.');">

            <!-- FECHA INICIAL -->
            <input
                type="hidden"
                name="fecha_desde"
                value="<?= htmlspecialchars($fecha_desde) ?>">

            <!-- FECHA FINAL -->
            <input
                type="hidden"
                name="fecha_hasta"
                value="<?= htmlspecialchars($fecha_hasta) ?>">

            <!-- BOTÓN ELIMINAR -->
            <button
                type="submit"
                class="btn btn-danger">

                Eliminar rango

            </button>

        </form>

    </div>

    <?php } ?>

</div>

<!--
=================================================
TABLA DE AUDITORÍA
=================================================
-->

<table class="table table-bordered table-hover">


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

    <td><?= htmlspecialchars($fila["fecha"]) ?></td>

    <td><?= htmlspecialchars($fila["usuario"] ?? "Sistema") ?></td>

    <td><?= htmlspecialchars($fila["tabla_afectada"]) ?></td>

    <td><?= htmlspecialchars($fila["accion"]) ?></td>

    <td><?= htmlspecialchars($fila["cambios"]) ?></td>

    <td><?= htmlspecialchars($fila["ip"]) ?></td>

    <td>

        <a
            href="detalle.php?operacion=<?= urlencode($fila["operacion_id"]) ?>"
            class="btn btn-primary btn-sm">

            Ver

        </a>

    </td>

</tr>

<?php } ?>

    </tbody>

</table>

<?php include("../../template/footer_modulos.php"); ?>