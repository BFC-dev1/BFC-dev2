<!-- ===== FOOTER ===== -->
<footer class="footer">
    © 2026 Bellavista FC. Todos los derechos reservados. | Reglamento Interno, Términos y Condiciones.
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