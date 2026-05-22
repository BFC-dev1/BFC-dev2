<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="header">

<div class="izquierda">
    <div class="menu-icon" onclick="toggleMenu()">☰</div>

    <div class="logo">
        <a href="/BFC-dev2/pages/index.php">
            <img src="/BFC-dev2/assets/img/Logo1.png" alt="Logo">
        </a>
    </div>
</div>

<div class="menu-desplegable" id="menu">
    <a href="#">Competencias</a>
    <a href="#">Grupo Técnico</a>
    <a href="/BFC-dev2/pages/inscripciones.php">Inscripciones</a>
    <a href="/BFC-dev2/pages/contacto.php">Contacto</a>


<?php if (isset($_SESSION['admin'])): ?>
        <span>Hola, <?php echo $_SESSION['admin']; ?></span>
        <a href="/BFC-dev2/auth/logout.php">Cerrar sesión</a>
<?php else: ?>
        <a href="/BFC-dev2/auth/login.php">Iniciar sesión</a>
<?php endif; ?>
</div>

<div class="nav">
    <div class="links">
        <a href="/BFC-dev2/pages/quienes_somos.php">Quienes somos</a>
        <a href="/BFC-dev2/pages/inscripciones.php">Inscripciones</a>
        <a href="/BFC-dev2/pages/contacto.php">Contactos</a>
        <?php if(isset($_SESSION['admin'])): ?>
            <a href="../admin/dashboard.php">Dashboard</a>
        <?php endif; ?>
    </div>

    <div class="derecha">
        <?php if (isset($_SESSION['admin'])): ?>
            <span>Hola, <?php echo $_SESSION['admin']; ?></span>
            <a href="/BFC-dev2/auth/logout.php">Cerrar sesión</a>
        <?php else: ?>
            <a href="/BFC-dev2/auth/login.php">Iniciar sesión</a>
        <?php endif; ?>
    </div>
</div>

</header>
