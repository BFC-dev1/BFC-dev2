<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - BellavistaFC</title>
    <link rel="stylesheet" href="../assets/estilo.css">
</head>

<body>

<?php include("../includes/header.php"); ?>

<!-- ===== BANNER ===== -->
<section class="banner-contacto">
    QUIÉNES SOMOS
</section>

<!-- ===== CONTENIDO SIMPLE (SIN EFECTOS) ===== -->
<section class="contenedor-contacto">
    <div class="card-contacto">

        <h2>Quiénes somos</h2>

        <p>
            Bellavista FC es un club dedicado a la formación deportiva de niños y jóvenes,
            enfocado en el desarrollo técnico, la disciplina y el trabajo en equipo.
        </p>

        <h3>Nuestra misión</h3>
        <p>
            Formar deportistas integrales con habilidades técnicas y valores sólidos.
        </p>

        <h3>Nuestra visión</h3>
        <p>
            Ser un referente en formación deportiva y desarrollo humano.
        </p>

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
