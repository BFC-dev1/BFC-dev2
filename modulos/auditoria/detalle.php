<?php

session_start();

include(__DIR__ . "/../conexion_modulos.php");


// =====================================
// VALIDAR OPERACION UUID
// RECIBIDO DESDE index.php
// =====================================

$operacion_id = $_GET["operacion"] ?? "";


if($operacion_id == ""){

    die("Operación no válida");

}


// =====================================
// DATOS GENERALES
// =====================================

$stmt = $conexion->prepare("
SELECT 
    a.operacion_id,
    a.tabla_afectada,
    a.registro_id,
    a.accion,
    a.fecha,
    a.ip,
    a.navegador,
    u.usuario

FROM auditoria a

LEFT JOIN usuario u
ON a.usuario_id = u.id

WHERE a.operacion_id = ?

LIMIT 1
");


$stmt->execute([
    $operacion_id
]);


$operacion = $stmt->fetch(PDO::FETCH_ASSOC);


if(!$operacion){
    die("No existe la operación");
}



// =====================================
// DETALLES
// =====================================

$stmtDetalle = $conexion->prepare("
SELECT
    campo,
    valor_anterior,
    valor_nuevo,
    descripcion

FROM auditoria

WHERE operacion_id = ?

ORDER BY id ASC
");


$stmtDetalle->execute([
    $operacion_id
]);


$detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);



?>

<?php include("../../template/header_modulos.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="d-flex gap-2">

        <a
            href="../dashboard/index.php"
            class="btn btn-outline-dark"
        >
            ← Volver al Dashboard
        </a>

    </div>

</div>

<h2>Detalle Auditoría</h2>


<h3>
Operación:
<?= htmlspecialchars($operacion["operacion_id"]) ?>
</h3>


<table class="table table-bordered">

<tr>
    <td>Usuario</td>
    <td>
        <?= htmlspecialchars($operacion["usuario"] ?? "Sistema") ?>
    </td>
</tr>


<tr>
    <td>Tabla</td>
    <td>
        <?= htmlspecialchars($operacion["tabla_afectada"]) ?>
    </td>
</tr>


<tr>
    <td>Acción</td>
    <td>
        <?= htmlspecialchars($operacion["accion"]) ?>
    </td>
</tr>


<tr>
    <td>Registro ID</td>
    <td>
        <?= $operacion["registro_id"] ?>
    </td>
</tr>


<tr>
    <td>Fecha</td>
    <td>
        <?= $operacion["fecha"] ?>
    </td>
</tr>


<tr>
    <td>IP</td>
    <td>
        <?= htmlspecialchars($operacion["ip"] ?? "") ?>
    </td>
</tr>


<tr>
    <td>Navegador</td>
    <td>
        <?= htmlspecialchars($operacion["navegador"] ?? "") ?>
    </td>
</tr>


</table>


</table>


<br>


<h3>Cambios realizados</h3>


<table border="1" cellpadding="8">

<tr>

<th>Campo</th>
<th>Valor anterior</th>
<th>Valor nuevo</th>
<th>Descripción</th>

</tr>


<?php foreach($detalles as $d): ?>

<tr>

<td>
<?= htmlspecialchars($d["campo"]) ?>
</td>


<td>
<?= htmlspecialchars($d["valor_anterior"] ?? "") ?>
</td>


<td>
<?= htmlspecialchars($d["valor_nuevo"] ?? "") ?>
</td>


<td>
<?= htmlspecialchars($d["descripcion"]) ?>
</td>


</tr>

<?php endforeach; ?>


</table>

<?php include("../../template/footer_modulos.php"); ?>