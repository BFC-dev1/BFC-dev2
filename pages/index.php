<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BellavistaFC</title>

    <!-- Conecta el CSS -->
    <link rel="stylesheet" href="/BFC-dev1.github.io/assets/estilo.css">
</head>

<body>

<?php include("../includes/header.php"); ?>

    <!-- ===== SECCIÓN PRINCIPAL ===== -->
    <section class="principal">

        <!-- Imagen principal -->
        <div class="imagen-central">
            <img src="/BFC-dev1.github.io/assets/img/img1.png" alt="Image1">
        </div>

        <!-- Texto al lado derecho -->
        <div class="texto-lateral">
            <p>Entrenamientos enfocados en el desarrollo técnico</p>
        </div>

    </section>

    <!-- ===== TARJETAS (3 bloques) ===== -->
<section class="tarjetas">

    <div class="card">
        <img src="/BFC-dev1.github.io/assets/img/img2.png">
        <div class="overlay">
            <p>Trabajo de control y pase</p>
        </div>
    </div>

    <div class="card">
        <img src="/BFC-dev1.github.io/assets/img/img3.png">
        <div class="overlay">
            <p>Trabajo en equipo</p>
        </div>
    </div>

    <div class="card">
        <img src="/BFC-dev1.github.io/assets/img/img4.png">
        <div class="overlay">
            <p>Coordinación y técnica</p>
        </div>
    </div>

</section>

<?php include("../includes/footer.php"); ?>

</body>
</html>
