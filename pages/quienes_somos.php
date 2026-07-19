<?php
session_start();

include(__DIR__ . "/../includes/conexion_BDcms.php");

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

        "titulo" => "Quiénes Somos",
        "descripcion" => "",

        "mision_titulo" => "Nuestra misión",
        "mision" => "",

        "vision_titulo" => "Nuestra visión",
        "vision" => ""

    ];

}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $quienes['titulo']; ?> - Bellavista FC</title>

    <link rel="stylesheet" href="/BFC-dev2/assets/estilo.css">

    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    />

</head>

<body>

<?php include("../includes/header.php"); ?>

<!-- ===== BANNER ===== -->
<section class="banner-contacto">

    <h1><?php echo $quienes['titulo']; ?></h1>

</section>

<!-- ===== CONTENIDO ===== -->
<section class="contenedor-contacto">

    <div class="card-contacto">

        <h2><?php echo $quienes['titulo']; ?></h2>

        <p>

            <?php echo nl2br($quienes['descripcion']); ?>

        </p>

        <div class="info-contacto">

            <h3><?php echo $quienes['mision_titulo']; ?></h3>

            <p>

                <?php echo nl2br($quienes['mision']); ?>

            </p>

            <h3><?php echo $quienes['vision_titulo']; ?></h3>

            <p>

                <?php echo nl2br($quienes['vision']); ?>

            </p>

        </div>

    </div>

</section>

<section class="redes">

    <h3>Síguenos</h3>

    <div class="iconos">

        <a
            href="https://www.facebook.com/BellavistaFC"
            target="_blank"
            title="Facebook"
        >
            <i class="fa-brands fa-facebook-f"></i>
        </a>

        <a
            href="https://wa.me/573001234567"
            target="_blank"
            title="WhatsApp"
        >
            <i class="fa-brands fa-whatsapp"></i>
        </a>

    </div>

</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">

  <?php include("../includes/footer.php"); ?>

</footer>

</body>
</html>