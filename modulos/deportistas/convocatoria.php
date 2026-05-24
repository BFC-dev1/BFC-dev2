<?php
session_start();
include("../conexion_modulos.php");

/*
=================================================
INICIALIZAR VARIABLES SESIÓN
=================================================
*/
if (!isset($_SESSION['convocados'])) {
    $_SESSION['convocados'] = [];
}

if (!isset($_SESSION['fecha_convocatoria'])) {
    $_SESSION['fecha_convocatoria'] = "";
}

/*
=================================================
FILTROS
=================================================
*/
$categoria_id = $_GET['categoria_id'] ?? "";
$fecha = $_GET['fecha'] ?? date("Y-m-d");
$rival = $_GET['rival'] ?? "";

/*
=================================================
SI CAMBIA LA FECHA → LIMPIAR CONVOCADOS
=================================================
*/
if (
    !empty($_SESSION['fecha_convocatoria']) &&
    $_SESSION['fecha_convocatoria'] != $fecha
) {

    $_SESSION['convocados'] = [];
}

/* guardar fecha actual */
$_SESSION['fecha_convocatoria'] = $fecha;

/*
=================================================
AGREGAR / QUITAR CONVOCADOS
=================================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    $id = $_POST['id'];

    if ($_POST['accion'] === 'agregar') {
        $_SESSION['convocados'][$id] = $_POST['nombre'];
    }

    if ($_POST['accion'] === 'quitar') {
        unset($_SESSION['convocados'][$id]);
    }

    exit;
}

/*
=================================================
CATEGORIAS
=================================================
*/
$stmtCat = $conexion->prepare("
    SELECT *
    FROM categoria
    ORDER BY nombre ASC
");
$stmtCat->execute();

/*
=================================================
DEPORTISTAS
=================================================
*/
$deportistas = [];

if (!empty($categoria_id)) {

    $stmtDep = $conexion->prepare("
        SELECT 
            d.id,
            d.nombre,
            d.documento,
            c.nombre AS categoria
        FROM deportista d
        LEFT JOIN categoria c 
            ON c.id = d.categoria_id
        WHERE d.estado = 'activo'
        AND d.categoria_id = :cat
        ORDER BY d.nombre ASC
    ");

    $stmtDep->execute([
        ":cat" => $categoria_id
    ]);

    $deportistas = $stmtDep->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<title>Convocatoria</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-light">

<div class="container py-5">

<div class="card shadow p-4">

<h2 class="mb-4">
<i class="fa-solid fa-clipboard-list"></i>
Generar Convocatoria
</h2>

<!-- =====================================
FORM FILTRO
===================================== -->
<form method="GET" id="formFiltro">

<div class="mb-3">
<label class="form-label fw-bold">
Fecha del partido
</label>

<input 
type="date"
name="fecha"
id="fechaInput"
class="form-control"
value="<?php echo $fecha; ?>"
required>
</div>

<div class="mb-3">
<label class="form-label fw-bold">
Rival
</label>

<input 
type="text"
name="rival"
class="form-control"
value="<?php echo $rival; ?>"
required>
</div>

<div class="mb-4">
<label class="form-label fw-bold">
Categoría
</label>

<select 
name="categoria_id"
id="categoriaSelect"
class="form-select"
required>

<option value="">
Seleccionar categoría
</option>

<?php while($cat = $stmtCat->fetch(PDO::FETCH_ASSOC)){ ?>

<option 
value="<?php echo $cat['id']; ?>"
<?php if($categoria_id == $cat['id']) echo "selected"; ?>>

<?php echo $cat['nombre']; ?>

</option>

<?php } ?>

</select>

</div>

</form>

<?php if (!empty($deportistas)) { ?>

<!-- =====================================
TABLA JUGADORES
===================================== -->
<h4 class="mb-3">
<i class="fa-solid fa-users"></i>
Seleccionar Convocados
</h4>

<div class="table-responsive">

<table class="table table-bordered align-middle">

<thead class="table-dark">

<tr>
<th>#</th>
<th>Jugador</th>
<th>Documento</th>
<th>Convocar</th>
</tr>

</thead>

<tbody>

<?php foreach ($deportistas as $dep):

$checked = isset($_SESSION['convocados'][$dep['id']])
? "checked"
: "";

?>

<tr>

<td>
<?php echo $dep['id']; ?>
</td>

<td>
<?php echo $dep['nombre']; ?>
</td>

<td>
<?php echo $dep['documento']; ?>
</td>

<td class="text-center">

<input
type="checkbox"
class="jugador-check"
value="<?php echo $dep['id']; ?>"
data-nombre="<?php echo $dep['nombre']; ?>"
<?php echo $checked; ?>>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<hr class="my-4">

<!-- =====================================
TABLA CONVOCADOS
===================================== -->
<h4>
<i class="fa-solid fa-list"></i>
Convocados
</h4>

<table class="table table-striped">

<thead class="table-dark">

<tr>
<th>ID</th>
<th>Jugador</th>
</tr>

</thead>

<tbody id="tablaConvocados">

<?php foreach ($_SESSION['convocados'] as $id => $nombre): ?>

<tr>

<td>
<?php echo $id; ?>
</td>

<td>
<?php echo $nombre; ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<!-- =====================================
BOTÓN DESCARGAR
===================================== -->
<div class="mt-3 text-end">

<form 
method="POST"
action="/BFC-dev2/modulos/reportes/reporte_convocatoria.php">

<input 
type="hidden"
name="fecha"
value="<?php echo $fecha; ?>">

<input 
type="hidden"
name="rival"
value="<?php echo $rival; ?>">

<input 
type="hidden"
name="categoria_id"
value="<?php echo $categoria_id; ?>">

<button class="btn btn-success">

<i class="fa-solid fa-download"></i>
Descargar Convocatoria

</button>

</form>

</div>

<?php } ?>

</div>

</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

const checks = document.querySelectorAll(".jugador-check");
const categoriaSelect = document.getElementById("categoriaSelect");
const fechaInput = document.getElementById("fechaInput");

/*
=====================================
CAMBIAR CATEGORÍA
=====================================
*/
if (categoriaSelect) {

categoriaSelect.addEventListener("change", () => {

document.getElementById("formFiltro").submit();

});

}

/*
=====================================
CAMBIAR FECHA
=====================================
*/
if (fechaInput) {

fechaInput.addEventListener("change", () => {

document.getElementById("formFiltro").submit();

});

}

/*
=====================================
GUARDAR EN SESIÓN
=====================================
*/
checks.forEach(c => {

c.addEventListener("change", async () => {

let accion = c.checked
? "agregar"
: "quitar";

await fetch("", {

method: "POST",

headers: {
"Content-Type":
"application/x-www-form-urlencoded"
},

body:
`accion=${accion}&id=${c.value}&nombre=${c.dataset.nombre}`

});

location.reload();

});

});

});
</script>

</body>
</html>