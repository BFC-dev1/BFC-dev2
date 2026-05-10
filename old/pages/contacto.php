<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - BellavistaFC</title>
    <link rel="stylesheet" href="/BFC-dev1.github.io/assets/estilo.css">
</head>

<body>

<?php include("../includes/header.php"); ?>

<!-- ===== BANNER SUPERIOR ===== -->
<section class="banner-contacto">
    <h1>CONTACTO</h1>
</section>

<!-- ===== CONTENIDO ===== -->
<section class="contenedor-contacto">

    <div class="card-contacto">
        <h2>PONTE EN CONTACTO CON NOSOTROS</h2>

        <p>
            En Bellavista FC estamos disponibles para resolver tus dudas,
            recibir sugerencias o gestionar inscripciones.
        </p>

        <div class="info-contacto">
            <p>📞 Línea única: 300 123 4567</p>
            <p>☎ Servicio al cliente: 01 8000 123 456</p>
            <p>📠 Fijo: (604) 123 4567</p>
            <p>💬 WhatsApp: 310 987 6543</p>
            <p>✉ Correo: contacto@bellavistafc.com</p>
        </div>

        <div class="horarios">
            <h3>Horarios de atención:</h3>
            <p>Lunes a viernes: 8:00 a.m. - 5:00 p.m.</p>
            <p>Sábados: 9:00 a.m. - 1:00 p.m.</p>
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

<script>
function toggleMenu() {
    const menu = document.getElementById("menu");
    menu.classList.toggle("activo");
}

document.addEventListener("click", function(e) {
    const menu = document.getElementById("menu");
    const icon = document.querySelector(".menu-icon");

    if (!menu.contains(e.target) && !icon.contains(e.target)) {
        menu.classList.remove("activo");
    }
});
</script>

</body>
</html>