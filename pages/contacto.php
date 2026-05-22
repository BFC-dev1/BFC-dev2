<?php
session_start();
include(__DIR__ . "/../includes/conexion_BDcms.php");

/*
=========================
CONSULTAR CONTACTO
=========================
*/

$stmt = $conexion->prepare("
    SELECT * 
    FROM cms_contacto 
    LIMIT 1
");

$stmt->execute();

$contacto = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $contacto['titulo']; ?> - BellavistaFC</title>

    <link rel="stylesheet" href="/BFC-dev1.github.io/assets/estilo.css">
</head>

<body>

<?php include("../includes/header.php"); ?>

<!-- ===== BANNER ===== -->
<section class="banner-contacto">
    <h1><?php echo $contacto['titulo']; ?></h1>
</section>

<!-- ===== CONTENIDO ===== -->
<section class="contenedor-contacto">

    <div class="card-contacto">

        <h2><?php echo $contacto['titulo']; ?></h2>

        <p><?php echo $contacto['descripcion']; ?></p>

        <div class="info-contacto">

            <p>📞 Línea única: <?php echo $contacto['linea_unica']; ?></p>
            <p>☎ Servicio al cliente: <?php echo $contacto['servicio_cliente']; ?></p>
            <p>📠 Fijo: <?php echo $contacto['fijo']; ?></p>
            <p>💬 WhatsApp: <?php echo $contacto['whatsapp']; ?></p>
            <p>✉ Correo: <?php echo $contacto['correo']; ?></p>

        </div>

        <div class="horarios">

            <h3>Horarios de atención:</h3>

            <p><?php echo $contacto['horario_lun_vie']; ?></p>
            <p><?php echo $contacto['horario_sab']; ?></p>

        </div>

    </div>

</section>

<!-- ===== REDES ===== -->
<section class="redes">
    <p>SÍGUENOS:</p>
    <div class="iconos">
        <span>📸</span>
        <span>📘</span>
        <span>▶</span>
        <span>𝕏</span>
        <span>🎵</span>
        <span>in</span>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    © 2026 Bellavista FC. Todos los derechos reservados.
</footer>

</body>
</html>