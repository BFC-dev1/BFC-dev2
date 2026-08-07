<?php

/*
=================================================
INICIAR SESIÓN

Si la sesión aún no existe, se inicia para
poder acceder a la información del usuario.
=================================================
*/
if(session_status() === PHP_SESSION_NONE){
    session_start();
}


/*
=================================================
CARGAR CONFIGURACIÓN GENERAL

Importa:

- $url_base
- $css_base

Con esto todas las rutas del Sidebar funcionarán
automáticamente tanto en:

LOCAL:
http://localhost/BFC-dev2/

WEB:
https://bellavistafcdev.page.gd/
=================================================
*/
require_once(__DIR__ . "/../../includes/config.php");

?>


<!-- FONT AWESOME -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<!--
=================================================
CSS DEL SIDEBAR

La carpeta de estilos cambia automáticamente
según el entorno (local o web).
=================================================
-->

<link
    rel="stylesheet"
    href="<?= $url_base ?>/<?= $css_base ?>/sidebar.css"
>

<!-- BOTON MOBILE -->
<button class="menu-toggle d-md-none" id="menuToggle">

    <i class="fa-solid fa-bars"></i>

</button>



<!-- SIDEBAR -->
<div 
    id="sidebar"
    class="sidebar bg-dark text-white shadow"
>

    <!-- TITULO -->
    <h4 class="text-center mb-4 fw-bold">

        <i class="fa-solid fa-chart-simple"></i>
        Panel Admin

    </h4>

    <!-- MENU -->
    <ul class="nav flex-column">

        <!-- DASHBOARD -->
        <li class="nav-item mb-2">

            <a 
                href="<?= $url_base ?>/modulos/Dashboard/index.php"
                class="nav-link text-white"
            >
                <i class="fa-solid fa-house"></i>
                Dashboard
            </a>

        </li>

        <!-- USUARIOS -->
        <li class="nav-item mb-2">

            <a 
                class="nav-link text-white d-flex justify-content-between align-items-center"
                data-bs-toggle="collapse"
                href="#submenuUsuarios"
            >

                <span>
                    <i class="fa-solid fa-users"></i>
                    Usuarios
                </span>

                <span>▼</span>

            </a>

            <div class="collapse ms-3" id="submenuUsuarios">

                <ul class="nav flex-column">

                    <li class="nav-item">

<a
    href="<?= $url_base ?>/modulos/usuarios/index.php"
    class="nav-link text-white"
>
                            <i class="fa-solid fa-list"></i>
                            Listado
                        </a>

                    </li>

                    <li class="nav-item">


                    </li>

                </ul>

            </div>

        </li>

        <!-- DEPORTISTAS -->
        <li class="nav-item mb-2">

            <a 
                class="nav-link text-white d-flex justify-content-between align-items-center"
                data-bs-toggle="collapse"
                href="#submenuDeportistas"
            >

                <span>
                    <i class="fa-solid fa-person-running"></i>
                    Deportistas
                </span>

                <span>▼</span>

            </a>

            <div class="collapse ms-3" id="submenuDeportistas">

                <ul class="nav flex-column">

                    <li class="nav-item">

                        <a 
                            href="<?= $url_base ?>/modulos/deportistas/"
                            class="nav-link text-white"
                        >
                            <i class="fa-solid fa-list"></i>
                            Listado
                        </a>

                    </li>

                    <li class="nav-item">

                        <a 
                            href="<?= $url_base ?>/modulos/pagos/"
                            class="nav-link text-white"
                        >
                            <i class="fa-solid fa-money-bill-wave"></i>
                            Pagos
                        </a>

                    </li>

                    <li class="nav-item">

                        <a 
                            href="<?= $url_base ?>/modulos/asistencia/"
                            class="nav-link text-white"
                        >
                            <i class="fa-solid fa-calendar-check"></i>
                            Asistencia
                        </a>

                    </li>


                    <li class="nav-item">

<!--
=================================================
CONVOCATORIA

Se utiliza la ruta base del sistema para que
funcione tanto en Local como en el servidor.
=================================================
-->

<a
    href="<?= $url_base ?>/modulos/deportistas/convocatoria.php"
    class="nav-link text-white"
>

                        <i class="fa-solid fa-clipboard-list"></i>
                        Convocatoria

                    </a>

                    </li>

                </ul>

            </div>

        </li>

        <!-- CMS -->
        <li class="nav-item mb-2">

            <a 
                class="nav-link text-white d-flex justify-content-between align-items-center"
                data-bs-toggle="collapse"
                href="#submenuCMS"
            >

                <span>
                    <i class="fa-solid fa-pen-to-square"></i>
                    Editar Páginas
                </span>

                <span>▼</span>

            </a>

            <div class="collapse ms-3" id="submenuCMS">

                <ul class="nav flex-column">

                    <li class="nav-item">

                        <a 
                            href="<?= $url_base ?>/admin/edit_page_Content/editar_inicio.php"
                            class="nav-link text-white"
                        >
                            <i class="fa-solid fa-house"></i>
                            Página principal
                        </a>

                    </li>

                    <li class="nav-item">

<!--
=================================================
EDITAR CONTACTO

Ruta dinámica compatible con Local y Web.
=================================================
-->

<a
    href="<?= $url_base ?>/admin/edit_page_Content/editar_contacto.php"
    class="nav-link text-white"
>
                            <i class="fa-solid fa-phone"></i>
                            Contacto
                        </a>

                    </li>

                    <li class="nav-item">

<!--
=================================================
EDITAR QUIÉNES SOMOS

Ruta dinámica compatible con Local y Web.
=================================================
-->

<a
    href="<?= $url_base ?>/admin/edit_page_Content/editar_quienes_somos.php"
    class="nav-link text-white"
>
                            <i class="fa-solid fa-circle-info"></i>
                            Quiénes somos
                        </a>

                    </li>

                </ul>

            </div>

        </li>


<!-- AUDITORIA -->
<li class="nav-item mb-2">

    <a 
        class="nav-link text-white d-flex justify-content-between align-items-center"
        data-bs-toggle="collapse"
        href="#submenuAuditoria"
    >

        <span>
            <i class="fa-solid fa-file-shield"></i>
            Auditoría
        </span>

        <span>▼</span>

    </a>


    <div class="collapse ms-3" id="submenuAuditoria">

        <ul class="nav flex-column">


            <!-- AUDITORIA USUARIOS -->
            <li class="nav-item">

                <a 
                    href="<?= $url_base ?>/modulos/auditoria/index.php"
                    class="nav-link text-white"
                >
                    <i class="fa-solid fa-users"></i>
                    Usuarios
                </a>

            </li>


            <!-- AUDITORIA DEPORTISTAS -->
            <li class="nav-item">

                <a 
                    href="<?= $url_base ?>/modulos/auditoria/deportistas.php"
                    class="nav-link text-white"
                >
                    <i class="fa-solid fa-person-running"></i>
                    Deportistas
                </a>

            </li>


            <!-- AUDITORIA PAGOS -->
            <li class="nav-item">

                <a 
                    href="<?= $url_base ?>/modulos/auditoria/pagos.php"
                    class="nav-link text-white"
                >
                    <i class="fa-solid fa-money-bill-wave"></i>
                    Pagos
                </a>

            </li>


        </ul>

    </div>

</li>

        <!-- REPORTES -->
        <li class="nav-item mb-2">

<!--
=================================================
REPORTES

Ruta dinámica compatible con Local y Web.
=================================================
-->

<a
    href="<?= $url_base ?>/modulos/reportes/reportes.php"
    class="nav-link text-white"
>
                <i class="fa-solid fa-chart-column"></i>
                Reportes
            </a>

        </li>

        <hr>


        <hr>

<!-- ==========================================
USUARIO AUTENTICADO
========================================== -->

<div class="text-center text-white mb-3">

    <i class="fa-solid fa-circle-user fa-3x mb-2"></i>

<div class="fw-bold">

    <?php echo htmlspecialchars($_SESSION["nombre"] ?? $_SESSION["usuario"] ?? "Usuario"); ?>

</div>

<small class="text-warning">

    <?php echo htmlspecialchars($_SESSION["rol"] ?? ""); ?>

</small>

</div>


<!-- ==========================================
CAMBIAR CONTRASEÑA
========================================== -->

<li class="nav-item">

<!--
=================================================
CAMBIAR CONTRASEÑA

Ruta dinámica compatible con Local y Web.
=================================================
-->

<a
    href="<?= $url_base ?>/modulos/usuarios/cambiar_password.php"
    class="nav-link text-white"
>

        <i class="fa-solid fa-key"></i>

        Cambiar contraseña

    </a>

</li>

   <!-- ==========================================
CERRAR SESIÓN
========================================== -->

<li class="nav-item">

<!--
=================================================
CERRAR SESIÓN

Ruta dinámica compatible con Local y Web.
=================================================
-->

<a
    href="<?= $url_base ?>/auth/logout.php"
    class="nav-link text-danger"
>

        <i class="fa-solid fa-right-from-bracket"></i>

        Cerrar sesión

    </a>

</li>

</ul>

</div>