<?php
session_start();

/*
=========================
CONEXIÓN
=========================
*/
include("../includes/conexion.php");

/*
=========================
CONSULTAR DATOS
=========================
*/

$stmt = $conexion->prepare("
    SELECT *
    FROM pagina_inicio
    LIMIT 1
");

$stmt->execute();

$inicio = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BellavistaFC</title>

    <!-- CSS -->
    <link
        rel="stylesheet"
        href="/BFC-dev1.github.io/assets/estilo.css"
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

        <img
            src="/BFC-dev1.github.io/assets/img/<?php echo $inicio['imagen_principal']; ?>"
            alt="Image1"
        >

    </div>

    <!-- TEXTO -->

    <div class="texto-lateral">

        <p>

            <?php echo $inicio['descripcion']; ?>

        </p>

    </div>

</section>

<!-- ===================================== -->
<!-- TARJETAS -->
<!-- ===================================== -->

<section class="tarjetas">

    <!-- TARJETA 1 -->

    <div class="card">

        <img
            src="/BFC-dev1.github.io/assets/img/<?php echo $inicio['tarjeta1_img']; ?>"
        >

        <div class="overlay">

            <p>

                <?php echo $inicio['tarjeta1_texto']; ?>

            </p>

        </div>

    </div>

    <!-- TARJETA 2 -->

    <div class="card">

        <img
            src="/BFC-dev1.github.io/assets/img/<?php echo $inicio['tarjeta2_img']; ?>"
        >

        <div class="overlay">

            <p>

                <?php echo $inicio['tarjeta2_texto']; ?>

            </p>

        </div>

    </div>

    <!-- TARJETA 3 -->

    <div class="card">

        <img
            src="/BFC-dev1.github.io/assets/img/<?php echo $inicio['tarjeta3_img']; ?>"
        >

        <div class="overlay">

            <p>

                <?php echo $inicio['tarjeta3_texto']; ?>

            </p>

        </div>

    </div>

</section>

<?php include("../includes/footer.php"); ?>

</body>
</html>