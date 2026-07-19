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

    <!-- BOOTSTRAP -->
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

    <!-- FONT AWESOME -->
    <link 
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card shadow p-4">

        <!-- TITULO -->
        <h2 class="mb-4">

            <i class="fa-solid fa-circle-info"></i>
            Editar Quiénes Somos

        </h2>

        <!-- ALERTA -->
        <?php if(isset($_GET['ok'])){ ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check"></i>
                Información actualizada correctamente

            </div>

        <?php } ?>

        <form method="POST">

            <!-- TITULO -->
            <div class="mb-3">

                <label class="form-label fw-bold">

                    <i class="fa-solid fa-heading"></i>
                    Título

                </label>

                <input 
                    type="text" 
                    name="titulo" 
                    class="form-control"
                    value="<?php echo $quienes['titulo']; ?>"
                >

            </div>

            <!-- DESCRIPCION -->
            <div class="mb-4">

                <label class="form-label fw-bold">

                    <i class="fa-solid fa-align-left"></i>
                    Descripción

                </label>

                <textarea 
                    name="descripcion" 
                    class="form-control" 
                    rows="4"
                ><?php echo $quienes['descripcion']; ?></textarea>

            </div>

            <hr>

            <!-- MISION -->
            <h5 class="mb-3">

                <i class="fa-solid fa-bullseye"></i>
                Misión

            </h5>

            <!-- TITULO MISION -->
            <div class="input-group mb-3">

                <span class="input-group-text">

                    <i class="fa-solid fa-flag"></i>

                </span>

                <input 
                    type="text" 
                    name="mision_titulo" 
                    class="form-control"
                    value="<?php echo $quienes['mision_titulo']; ?>"
                    placeholder="Título misión"
                >

            </div>

            <!-- TEXTO MISION -->
            <div class="input-group mb-4">

                <span class="input-group-text">

                    <i class="fa-solid fa-file-lines"></i>

                </span>

                <textarea 
                    name="mision" 
                    class="form-control" 
                    rows="4"
                    placeholder="Descripción de la misión"
                ><?php echo $quienes['mision']; ?></textarea>

            </div>

            <hr>

            <!-- VISION -->
            <h5 class="mb-3">

                <i class="fa-solid fa-eye"></i>
                Visión

            </h5>

            <!-- TITULO VISION -->
            <div class="input-group mb-3">

                <span class="input-group-text">

                    <i class="fa-solid fa-lightbulb"></i>

                </span>

                <input 
                    type="text" 
                    name="vision_titulo" 
                    class="form-control"
                    value="<?php echo $quienes['vision_titulo']; ?>"
                    placeholder="Título visión"
                >

            </div>

            <!-- TEXTO VISION -->
            <div class="input-group mb-4">

                <span class="input-group-text">

                    <i class="fa-solid fa-file-lines"></i>

                </span>

                <textarea 
                    name="vision" 
                    class="form-control" 
                    rows="4"
                    placeholder="Descripción de la visión"
                ><?php echo $quienes['vision']; ?></textarea>

            </div>

            <!-- BOTONES -->
            <button type="submit" class="btn btn-primary">

                <i class="fa-solid fa-floppy-disk me-2"></i>
                Guardar Cambios

            </button>

            <a
                href="/BFC-dev2/modulos/Dashboard/index.php"
                class="btn btn-outline-secondary ms-2"
            >

                <i class="fa-solid fa-arrow-left me-2"></i>
                Cancelar

            </a>

        </form>

    </div>

</div>

</body>
</html>