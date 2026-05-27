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

    /*
    =============================================
    AGREGAR
    =============================================
    */
    if ($_POST['accion'] === 'agregar') {

        $_SESSION['convocados'][$id] = [

            'nombre' => $_POST['nombre'] ?? '',
            'documento' => $_POST['documento'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'entrenador' => $_POST['entrenador'] ?? '',
            'acudiente' => $_POST['acudiente'] ?? '',
            'parentesco' => $_POST['parentesco'] ?? '',
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
            'categoria' => $_POST['categoria'] ?? ''

        ];
    }

    /*
    =============================================
    QUITAR
    =============================================
    */
    if ($_POST['accion'] === 'quitar') {

        unset($_SESSION['convocados'][$id]);
    }

    echo json_encode([
        "success" => true
    ]);

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
            d.telefono,
            d.fecha_nacimiento,

            ud.acudiente,
            ud.parentesco,
            ud.entrenador,

            c.nombre AS categoria

        FROM deportista d

        LEFT JOIN categoria c 
            ON c.id = d.categoria_id

        LEFT JOIN usuario_deportista ud
            ON ud.deportista_id = d.id

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

<style>

body{
    background:#f4f6f9;
}

.card{
    border:none;
    border-radius:18px;
}

.table th{
    white-space: nowrap;
}

</style>

</head>

<body>

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

<div class="row">

<div class="col-md-4 mb-3">

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

<div class="col-md-4 mb-3">

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

<div class="col-md-4 mb-3">

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

</div>

</form>

<?php if (!empty($deportistas)) { ?>

<!-- =====================================
TABLA JUGADORES
===================================== -->
<h4 class="mb-3 mt-4">
<i class="fa-solid fa-users"></i>
Seleccionar Convocados
</h4>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-dark">

<tr>

<th>Jugador</th>
<th>Documento</th>
<th>Teléfono</th>
<th>Entrenador</th>
<th>Acudiente</th>
<th>Parentesco</th>
<th>Nacimiento</th>
<th>Categoría</th>
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

<td><?php echo $dep['nombre']; ?></td>

<td><?php echo $dep['documento']; ?></td>

<td><?php echo $dep['telefono']; ?></td>

<td><?php echo $dep['entrenador'] ?? ''; ?></td>

<td><?php echo $dep['acudiente'] ?? ''; ?></td>

<td><?php echo $dep['parentesco'] ?? ''; ?></td>

<td><?php echo $dep['fecha_nacimiento']; ?></td>

<td><?php echo $dep['categoria']; ?></td>

<td class="text-center">

<input
type="checkbox"
class="form-check-input jugador-check"

data-id="<?php echo $dep['id']; ?>"
data-nombre="<?php echo htmlspecialchars($dep['nombre']); ?>"
data-documento="<?php echo htmlspecialchars($dep['documento']); ?>"
data-telefono="<?php echo htmlspecialchars($dep['telefono']); ?>"
data-entrenador="<?php echo htmlspecialchars($dep['entrenador'] ?? ''); ?>"
data-acudiente="<?php echo htmlspecialchars($dep['acudiente'] ?? ''); ?>"
data-parentesco="<?php echo htmlspecialchars($dep['parentesco'] ?? ''); ?>"
data-fecha="<?php echo htmlspecialchars($dep['fecha_nacimiento']); ?>"
data-categoria="<?php echo htmlspecialchars($dep['categoria']); ?>"

<?php echo $checked; ?>>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php } ?>

<hr class="my-4">

<!-- =====================================
TABLA CONVOCADOS
===================================== -->
<h4 class="mb-3">
<i class="fa-solid fa-list"></i>
Convocados
</h4>

<div class="table-responsive">

<table class="table table-striped table-bordered">

<thead class="table-dark">

<tr>

<th>Jugador</th>
<th>Documento</th>
<th>Teléfono</th>
<th>Entrenador</th>
<th>Acudiente</th>
<th>Parentesco</th>
<th>Nacimiento</th>
<th>Categoría</th>

</tr>

</thead>

<tbody>

<?php foreach ($_SESSION['convocados'] as $convocado): ?>

<tr>

<td><?php echo $convocado['nombre'] ?? ''; ?></td>

<td><?php echo $convocado['documento'] ?? ''; ?></td>

<td><?php echo $convocado['telefono'] ?? ''; ?></td>

<td><?php echo $convocado['entrenador'] ?? ''; ?></td>

<td><?php echo $convocado['acudiente'] ?? ''; ?></td>

<td><?php echo $convocado['parentesco'] ?? ''; ?></td>

<td><?php echo $convocado['fecha_nacimiento'] ?? ''; ?></td>

<td><?php echo $convocado['categoria'] ?? ''; ?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<!-- =====================================
BOTÓN DESCARGAR
===================================== -->
<div class="mt-4 text-end">

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

</div>

</div>

<script>

document.addEventListener("DOMContentLoaded", () => {

const checks = document.querySelectorAll(".jugador-check");

const categoriaSelect =
document.getElementById("categoriaSelect");

const fechaInput =
document.getElementById("fechaInput");

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

const body = new URLSearchParams({

accion: accion,
id: c.dataset.id,
nombre: c.dataset.nombre,
documento: c.dataset.documento,
telefono: c.dataset.telefono,
entrenador: c.dataset.entrenador,
acudiente: c.dataset.acudiente,
parentesco: c.dataset.parentesco,
fecha_nacimiento: c.dataset.fecha,
categoria: c.dataset.categoria

});

await fetch(window.location.href, {

method: "POST",

headers: {
"Content-Type":
"application/x-www-form-urlencoded"
},

body: body.toString()

});

location.reload();

});

});

});

</script>

</body>
</html>