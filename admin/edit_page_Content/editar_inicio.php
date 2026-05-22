<?php
session_start();

include(__DIR__ . "/../../includes/conexion.php");

/*
=========================
CONSULTAR INFORMACIÓN
=========================
*/

$stmt = $conexion->prepare("
    SELECT *
    FROM pagina_inicio
    LIMIT 1
");

$stmt->execute();

$inicio = $stmt->fetch(PDO::FETCH_ASSOC);

/* SI NO HAY DATOS EVITA ERROR */
if(!$inicio){
    $inicio = [
        "descripcion" => "",
        "tarjeta1_texto" => "",
        "tarjeta2_texto" => "",
        "tarjeta3_texto" => "",
        "imagen_principal" => "",
        "tarjeta1_img" => "",
        "tarjeta2_img" => "",
        "tarjeta3_img" => ""
    ];
}

/*
=========================
GUARDAR CAMBIOS
=========================
*/

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $descripcion = trim($_POST['descripcion'] ?? "");

    $tarjeta1_texto = trim($_POST['tarjeta1_texto'] ?? "");
    $tarjeta2_texto = trim($_POST['tarjeta2_texto'] ?? "");
    $tarjeta3_texto = trim($_POST['tarjeta3_texto'] ?? "");

    /*
    =========================
    IMAGEN PRINCIPAL
    =========================
    */

    if(!empty($_FILES['imagen_principal']['name'])){

        $imagenPrincipal = time() . "_" . $_FILES['imagen_principal']['name'];

        move_uploaded_file(
            $_FILES['imagen_principal']['tmp_name'],
            "../assets/img/" . $imagenPrincipal
        );

        $stmtUpdate = $conexion->prepare("
            UPDATE pagina_inicio
            SET imagen_principal = :imagen
            WHERE id = 1
        ");

        $stmtUpdate->execute([
            ":imagen" => $imagenPrincipal
        ]);
    }

    /*
    =========================
    TARJETA 1
    =========================
    */

    if(!empty($_FILES['tarjeta1_img']['name'])){

        $img1 = time() . "_1_" . $_FILES['tarjeta1_img']['name'];

        move_uploaded_file(
            $_FILES['tarjeta1_img']['tmp_name'],
            "../assets/img/" . $img1
        );

        $stmtUpdate = $conexion->prepare("
            UPDATE pagina_inicio
            SET tarjeta1_img = :img1
            WHERE id = 1
        ");

        $stmtUpdate->execute([
            ":img1" => $img1
        ]);
    }

    /*
    =========================
    TARJETA 2
    =========================
    */

    if(!empty($_FILES['tarjeta2_img']['name'])){

        $img2 = time() . "_2_" . $_FILES['tarjeta2_img']['name'];

        move_uploaded_file(
            $_FILES['tarjeta2_img']['tmp_name'],
            "../assets/img/" . $img2
        );

        $stmtUpdate = $conexion->prepare("
            UPDATE pagina_inicio
            SET tarjeta2_img = :img2
            WHERE id = 1
        ");

        $stmtUpdate->execute([
            ":img2" => $img2
        ]);
    }

    /*
    =========================
    TARJETA 3
    =========================
    */

    if(!empty($_FILES['tarjeta3_img']['name'])){

        $img3 = time() . "_3_" . $_FILES['tarjeta3_img']['name'];

        move_uploaded_file(
            $_FILES['tarjeta3_img']['tmp_name'],
            "../assets/img/" . $img3
        );

        $stmtUpdate = $conexion->prepare("
            UPDATE pagina_inicio
            SET tarjeta3_img = :img3
            WHERE id = 1
        ");

        $stmtUpdate->execute([
            ":img3" => $img3
        ]);
    }

    /*
    =========================
    ACTUALIZAR TEXTOS
    =========================
    */

    $stmtTextos = $conexion->prepare("
        UPDATE pagina_inicio SET

        descripcion = :descripcion,
        tarjeta1_texto = :t1,
        tarjeta2_texto = :t2,
        tarjeta3_texto = :t3

        WHERE id = 1
    ");

    $stmtTextos->execute([
        ":descripcion" => $descripcion,
        ":t1" => $tarjeta1_texto,
        ":t2" => $tarjeta2_texto,
        ":t3" => $tarjeta3_texto
    ]);

    header("Location: editar_inicio.php?ok=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Inicio</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card shadow p-4">

        <h2 class="mb-4">Editar Página Principal</h2>

        <?php if(isset($_GET['ok'])){ ?>
            <div class="alert alert-success">
                Información actualizada correctamente
            </div>
        <?php } ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="mb-4">
                <label>Texto Principal</label>
                <textarea name="descripcion" class="form-control" rows="3"><?php echo $inicio['descripcion']; ?></textarea>
            </div>

            <div class="mb-4">
                <label>Imagen Principal</label>
                <input type="file" name="imagen_principal" class="form-control">
            </div>

            <hr>
            <h4>Tarjeta 1</h4>

            <input type="text" name="tarjeta1_texto" class="form-control mb-3" value="<?php echo $inicio['tarjeta1_texto']; ?>">
            <input type="file" name="tarjeta1_img" class="form-control mb-3">

            <hr>
            <h4>Tarjeta 2</h4>

            <input type="text" name="tarjeta2_texto" class="form-control mb-3" value="<?php echo $inicio['tarjeta2_texto']; ?>">
            <input type="file" name="tarjeta2_img" class="form-control mb-3">

            <hr>
            <h4>Tarjeta 3</h4>

            <input type="text" name="tarjeta3_texto" class="form-control mb-3" value="<?php echo $inicio['tarjeta3_texto']; ?>">
            <input type="file" name="tarjeta3_img" class="form-control mb-3">

            <button class="btn btn-primary">
                Guardar Cambios
            </button>

        </form>

    </div>

</div>

</body>
</html>