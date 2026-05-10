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
    <h1>INSCRIPCIONES</h1>
</section>

<!-- ===== FORMULARIO ===== -->
<section class="contenedor-contacto">
    <div class="card-contacto">

        <h2>Formulario de Inscripción</h2>

        <form class="formulario">

            <input type="text" placeholder="Nombre completo" required>

            <input type="number" placeholder="Edad" required>

            <select required>
                <option value="">Seleccione la Categoria</required>
                <option>Sub10</required>
                <option>Sub11</required>
                <option>Sub13</required>
                <option>Sub14</required>
                <option>Sub15</required>
                <option>Sub16</required>
                <option>Sub17</required>
                <option>Sub18</required>
                <option>Sub19</required>
                <option>Sub20</required>
            </select>

            
            <input type="tel" placeholder="Teléfono" required>

            <input type="email" placeholder="Correo electrónico" required>

            <textarea placeholder="Observaciones"></textarea>

            <button type="submit">Inscribirse</button>

        </form>

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
    <p>© 2026 Escuela Deportiva</p>
</footer>

</body>
</html>

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