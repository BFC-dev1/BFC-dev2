<?php
session_start();
include(__DIR__ . "/../../includes/conexion_BDcms.php");

/*
=========================
CONSULTAR QUIÉNES SOMOS
=========================
*/

$stmt = $conexion->prepare("
    SELECT *
    FROM cms_quienes_somos
    LIMIT 1
");

$stmt->execute();

$quienes = $stmt->fetch(PDO::FETCH_ASSOC);

/* EVITAR ERRORES SI NO EXISTE REGISTRO */
if(!$quienes){

    $quienes = [
        "titulo" => "Quiénes somos",
        "descripcion" => "",
        "mision_titulo" => "Nuestra misión",
        "mision" => "",
        "vision_titulo" => "Nuestra visión",
        "vision" => ""
    ];
}

/*
=========================
GUARDAR CAMBIOS
=========================
*/

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $titulo = trim($_POST['titulo'] ?? "");
    $descripcion = trim($_POST['descripcion'] ?? "");

    $mision_titulo = trim($_POST['mision_titulo'] ?? "");
    $mision = trim($_POST['mision'] ?? "");

    $vision_titulo = trim($_POST['vision_titulo'] ?? "");
    $vision = trim($_POST['vision'] ?? "");

    $stmtUpdate = $conexion->prepare("
        UPDATE cms_quienes_somos SET

        titulo = :titulo,
        descripcion = :descripcion,

        mision_titulo = :mision_titulo,
        mision = :mision,

        vision_titulo = :vision_titulo,
        vision = :vision

        WHERE id = 1
    ");

    $stmtUpdate->execute([

        ":titulo" => $titulo,
        ":descripcion" => $descripcion,

        ":mision_titulo" => $mision_titulo,
        ":mision" => $mision,

        ":vision_titulo" => $vision_titulo,
        ":vision" => $vision

    ]);

    header("Location: editar_quienes_somos.php?ok=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Quiénes Somos</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card shadow p-4">

        <h2 class="mb-4">ℹ️ Editar Quiénes Somos</h2>

        <?php if(isset($_GET['ok'])){ ?>
            <div class="alert alert-success">
                Información actualizada correctamente
            </div>
        <?php } ?>

        <form method="POST">

            <!-- TITULO -->
            <div class="mb-3">
                <label>Título</label>
                <input type="text" name="titulo" class="form-control"
                    value="<?php echo $quienes['titulo']; ?>">
            </div>

            <!-- DESCRIPCIÓN -->
            <div class="mb-3">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"><?php echo $quienes['descripcion']; ?></textarea>
            </div>

            <hr>

            <!-- MISIÓN -->
            <h5>Misión</h5>

            <input type="text" name="mision_titulo" class="form-control mb-2"
                value="<?php echo $quienes['mision_titulo']; ?>">

            <textarea name="mision" class="form-control mb-3" rows="3"><?php echo $quienes['mision']; ?></textarea>

            <hr>

            <!-- VISIÓN -->
            <h5>Visión</h5>

            <input type="text" name="vision_titulo" class="form-control mb-2"
                value="<?php echo $quienes['vision_titulo']; ?>">

            <textarea name="vision" class="form-control mb-3" rows="3"><?php echo $quienes['vision']; ?></textarea>

            <!-- BOTÓN -->
            <button class="btn btn-primary">
                Guardar Cambios
            </button>

        </form>

    </div>

</div>

</body>
</html>