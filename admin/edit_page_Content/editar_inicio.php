<?php
session_start();

/*
=================================================
MOSTRAR ERRORES PHP
=================================================
*/
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
=================================================
CONEXIÓN CMS
=================================================
*/
include(__DIR__ . "/../../includes/conexion_BDcms.php");


/*
=================================================
CREAR REGISTRO SI NO EXISTE
=================================================
*/
$stmtCheck = $conexion->prepare("
    SELECT COUNT(*) total
    FROM cms_inicio
");

$stmtCheck->execute();

$existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if($existe['total'] == 0){

    $stmtInsert = $conexion->prepare("
        INSERT INTO cms_inicio (

            titulo,
            descripcion,

            imagen_principal,

            tarjeta1_texto,
            tarjeta1_img,

            tarjeta2_texto,
            tarjeta2_img,

            tarjeta3_texto,
            tarjeta3_img

        ) VALUES (

            '',
            '',

            '',

            '',
            '',

            '',
            '',

            '',
            ''

        )
    ");

    $stmtInsert->execute();
}


/*
=================================================
CONSULTAR INFORMACIÓN
=================================================
*/
$stmt = $conexion->prepare("
    SELECT *
    FROM cms_inicio
    LIMIT 1
");

$stmt->execute();

$inicio = $stmt->fetch(PDO::FETCH_ASSOC);


/*
=================================================
EVITAR ERRORES
=================================================
*/
if(!$inicio){

    $inicio = [

        "titulo" => "",
        "descripcion" => "",

        "imagen_principal" => "",

        "tarjeta1_texto" => "",
        "tarjeta1_img" => "",

        "tarjeta2_texto" => "",
        "tarjeta2_img" => "",

        "tarjeta3_texto" => "",
        "tarjeta3_img" => ""

    ];
}


/*
=================================================
GUARDAR CAMBIOS
=================================================
*/
if($_SERVER["REQUEST_METHOD"] == "POST"){

    $titulo = trim($_POST['titulo'] ?? "");

    $descripcion = trim($_POST['descripcion'] ?? "");

    $tarjeta1_texto = trim($_POST['tarjeta1_texto'] ?? "");
    $tarjeta2_texto = trim($_POST['tarjeta2_texto'] ?? "");
    $tarjeta3_texto = trim($_POST['tarjeta3_texto'] ?? "");


    /*
    =================================================
    CARPETA IMÁGENES
    =================================================
    */
    $carpetaImagenes = __DIR__ . "/../../assets/img/";

    if(!file_exists($carpetaImagenes)){

        mkdir($carpetaImagenes, 0777, true);

    }


    /*
    =================================================
    IMAGEN PRINCIPAL
    =================================================
    */
    if(!empty($_FILES['imagen_principal']['name'])){

        $imagenPrincipal =
        time() . "_" . basename($_FILES['imagen_principal']['name']);

        move_uploaded_file(

            $_FILES['imagen_principal']['tmp_name'],

            $carpetaImagenes . $imagenPrincipal

        );

        $stmtUpdate = $conexion->prepare("
            UPDATE cms_inicio
            SET imagen_principal = :imagen
            WHERE id = 1
        ");

        $stmtUpdate->execute([
            ":imagen" => $imagenPrincipal
        ]);
    }


    /*
    =================================================
    TARJETA 1
    =================================================
    */
    if(!empty($_FILES['tarjeta1_img']['name'])){

        $img1 =
        time() . "_1_" . basename($_FILES['tarjeta1_img']['name']);

        move_uploaded_file(

            $_FILES['tarjeta1_img']['tmp_name'],

            $carpetaImagenes . $img1

        );

        $stmtUpdate = $conexion->prepare("
            UPDATE cms_inicio
            SET tarjeta1_img = :img1
            WHERE id = 1
        ");

        $stmtUpdate->execute([
            ":img1" => $img1
        ]);
    }


    /*
    =================================================
    TARJETA 2
    =================================================
    */
    if(!empty($_FILES['tarjeta2_img']['name'])){

        $img2 =
        time() . "_2_" . basename($_FILES['tarjeta2_img']['name']);

        move_uploaded_file(

            $_FILES['tarjeta2_img']['tmp_name'],

            $carpetaImagenes . $img2

        );

        $stmtUpdate = $conexion->prepare("
            UPDATE cms_inicio
            SET tarjeta2_img = :img2
            WHERE id = 1
        ");

        $stmtUpdate->execute([
            ":img2" => $img2
        ]);
    }


    /*
    =================================================
    TARJETA 3
    =================================================
    */
    if(!empty($_FILES['tarjeta3_img']['name'])){

        $img3 =
        time() . "_3_" . basename($_FILES['tarjeta3_img']['name']);

        move_uploaded_file(

            $_FILES['tarjeta3_img']['tmp_name'],

            $carpetaImagenes . $img3

        );

        $stmtUpdate = $conexion->prepare("
            UPDATE cms_inicio
            SET tarjeta3_img = :img3
            WHERE id = 1
        ");

        $stmtUpdate->execute([
            ":img3" => $img3
        ]);
    }


    /*
    =================================================
    ACTUALIZAR TEXTOS
    =================================================
    */
    $stmtTextos = $conexion->prepare("

        UPDATE cms_inicio SET

        titulo = :titulo,

        descripcion = :descripcion,

        tarjeta1_texto = :t1,
        tarjeta2_texto = :t2,
        tarjeta3_texto = :t3

        WHERE id = 1

    ");

    $stmtTextos->execute([

        ":titulo" => $titulo,

        ":descripcion" => $descripcion,

        ":t1" => $tarjeta1_texto,
        ":t2" => $tarjeta2_texto,
        ":t3" => $tarjeta3_texto

    ]);


    /*
    =================================================
    REDIRECT
    =================================================
    */
    header("Location: editar_inicio.php?ok=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Editar Inicio</title>

<link 
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
rel="stylesheet">

<link 
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-light">

<div class="container py-5">

<div class="card shadow p-4">

<h2 class="mb-4">

<i class="fa-solid fa-house"></i>
Editar Página Principal

</h2>

<?php if(isset($_GET['ok'])){ ?>

<div class="alert alert-success">

<i class="fa-solid fa-circle-check"></i>
Información actualizada correctamente

</div>

<?php } ?>


<form method="POST" enctype="multipart/form-data">

<!-- TITULO -->
<div class="mb-4">

<label class="form-label fw-bold">

<i class="fa-solid fa-heading"></i>
Título Principal

</label>

<input
type="text"
name="titulo"
class="form-control"
value="<?php echo htmlspecialchars($inicio['titulo']); ?>">

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
><?php echo htmlspecialchars($inicio['descripcion']); ?></textarea>

</div>


<!-- IMAGEN PRINCIPAL -->
<div class="mb-4">

<label class="form-label fw-bold">

<i class="fa-solid fa-image"></i>
Imagen Principal

</label>

<input
type="file"
name="imagen_principal"
class="form-control">

</div>

<hr>

<!-- TARJETA 1 -->
<h5 class="mb-3">

<i class="fa-solid fa-layer-group"></i>
Tarjeta 1

</h5>

<input
type="text"
name="tarjeta1_texto"
class="form-control mb-3"
placeholder="Texto tarjeta 1"
value="<?php echo htmlspecialchars($inicio['tarjeta1_texto']); ?>">

<input
type="file"
name="tarjeta1_img"
class="form-control mb-4">


<!-- TARJETA 2 -->
<h5 class="mb-3">

<i class="fa-solid fa-layer-group"></i>
Tarjeta 2

</h5>

<input
type="text"
name="tarjeta2_texto"
class="form-control mb-3"
placeholder="Texto tarjeta 2"
value="<?php echo htmlspecialchars($inicio['tarjeta2_texto']); ?>">

<input
type="file"
name="tarjeta2_img"
class="form-control mb-4">


<!-- TARJETA 3 -->
<h5 class="mb-3">

<i class="fa-solid fa-layer-group"></i>
Tarjeta 3

</h5>

<input
type="text"
name="tarjeta3_texto"
class="form-control mb-3"
placeholder="Texto tarjeta 3"
value="<?php echo htmlspecialchars($inicio['tarjeta3_texto']); ?>">

<input
type="file"
name="tarjeta3_img"
class="form-control mb-4">


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