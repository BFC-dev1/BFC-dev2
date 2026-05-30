<?php
session_start();

/*
=========================
CONEXIÓN NUEVA BD CMS
=========================
*/
include(__DIR__ . "/../includes/conexion_BDcms.php");

/*
=========================
CONSULTAR DATOS CMS
=========================
*/
$stmt = $conexion->prepare("
    SELECT *
    FROM cms_inicio
    LIMIT 1
");

$stmt->execute();

$inicio = $stmt->fetch(PDO::FETCH_ASSOC);

/*
=========================
EVITAR ERRORES
=========================
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

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Bellavista FC</title>

    <!-- CSS -->
    <link
        rel="stylesheet"
        href="/BFC-dev2/assets/estilo.css"
    >

</head>

<body>

<?php include("../includes/header.php"); ?>

<!-- ===================================== -->
<!-- SECCIÓN PRINCIPAL -->
<!-- ===================================== -->

<section class="principal">

    <!-- IMAGEN PRINCIPAL -->
    <div class="imagen-central">

        <?php if(!empty($inicio['imagen_principal'])){ ?>

            <img
                src="/BFC-dev2/assets/img/<?php echo htmlspecialchars($inicio['imagen_principal']); ?>"
                alt="Imagen Principal"
            >

        <?php } ?>

    </div>

    <!-- TEXTO -->
    <div class="texto-lateral">

        <p>

            <?php echo nl2br(htmlspecialchars($inicio['descripcion'])); ?>

        </p>

    </div>

</section>

<!-- ===================================== -->
<!-- TARJETAS -->
<!-- ===================================== -->

<section class="tarjetas">

    <!-- TARJETA 1 -->
    <div class="card">

        <?php if(!empty($inicio['tarjeta1_img'])){ ?>

            <img
                src="/BFC-dev2/assets/img/<?php echo htmlspecialchars($inicio['tarjeta1_img']); ?>"
                alt="Tarjeta 1"
            >

        <?php } ?>

        <div class="overlay">

            <p>

                <?php echo htmlspecialchars($inicio['tarjeta1_texto']); ?>

            </p>

        </div>

    </div>

    <!-- TARJETA 2 -->
    <div class="card">

        <?php if(!empty($inicio['tarjeta2_img'])){ ?>

            <img
                src="/BFC-dev2/assets/img/<?php echo htmlspecialchars($inicio['tarjeta2_img']); ?>"
                alt="Tarjeta 2"
            >

        <?php } ?>

        <div class="overlay">

            <p>

                <?php echo htmlspecialchars($inicio['tarjeta2_texto']); ?>

            </p>

        </div>

    </div>

    <!-- TARJETA 3 -->
    <div class="card">

        <?php if(!empty($inicio['tarjeta3_img'])){ ?>

            <img
                src="/BFC-dev2/assets/img/<?php echo htmlspecialchars($inicio['tarjeta3_img']); ?>"
                alt="Tarjeta 3"
            >

        <?php } ?>

        <div class="overlay">

            <p>

                <?php echo htmlspecialchars($inicio['tarjeta3_texto']); ?>

            </p>

        </div>

    </div>

</section>

<?php include("../includes/footer.php"); ?>

</body>
</html>