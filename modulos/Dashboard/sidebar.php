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
    =================================================
    */
    require_once(__DIR__ . "/../../includes/config.php");

    ?>


    <!-- FONT AWESOME -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- CSS DEL SIDEBAR -->
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
            <?php if (tiene_permiso('dashboard')): ?>
            <li class="nav-item mb-2">

                <a 
                    href="<?= $url_base ?>/modulos/Dashboard/index.php"
                    class="nav-link text-white"
                >
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>

            </li>
            <?php endif; ?>

           <!-- USUARIOS -->
<?php if (tiene_permiso('usuarios')): ?>

<li class="nav-item mb-2">

    <a 
        class="nav-link text-white d-flex justify-content-between align-items-center"
        data-bs-toggle="collapse"
        href="#submenuUsuarios"
        role="button"
        aria-expanded="false"
        aria-controls="submenuUsuarios"
    >

        <span>
            <i class="fa-solid fa-users"></i>
            Usuarios
        </span>

        <span>▼</span>

    </a>


    <div class="collapse ms-3" id="submenuUsuarios">

        <ul class="nav flex-column">

            <!-- USUARIOS REGISTRADOS -->
            <li class="nav-item">

                <a
                    href="<?= $url_base ?>/modulos/usuarios/index.php"
                    class="nav-link text-white"
                >

                    <i class="fa-solid fa-users"></i>

                    Usuarios registrados

                </a>

            </li>


            <!-- REGISTRAR USUARIO -->
            <li class="nav-item">

<a
    href="<?= $url_base ?>/modulos/usuarios/index.php?crear=1"
    class="nav-link text-white"
>
    <i class="fa-solid fa-plus"></i>
    Registrar usuario
</a>

            </li>

        </ul>

    </div>

</li>

<?php endif; ?>

  <!-- =================================================
     MÓDULO DEPORTISTAS
     
     Este bloque controla las opciones disponibles
     dentro del módulo de Deportistas.
     
     Opciones:
     - Deportistas registrados
     - Registrar deportista
     - Asistencia
     - Convocatoria
================================================= -->

<?php if (
    tiene_permiso('deportistas') ||
    tiene_permiso('ver_deportistas') ||
    tiene_permiso('asistencia') ||
    tiene_permiso('convocatoria')
): ?>

<li class="nav-item mb-2">


    <!-- =================================================
         TÍTULO DEL MENÚ
         
         Al hacer clic se despliegan las opciones.
    ================================================= -->

    <a
        class="nav-link text-white d-flex justify-content-between align-items-center"
        data-bs-toggle="collapse"
        href="#submenuDeportistas"
        role="button"
        aria-expanded="false"
        aria-controls="submenuDeportistas"
    >

        <span>

            <i class="fa-solid fa-person-running"></i>

            Deportistas

        </span>

        <span>▼</span>

    </a>


    <!-- =================================================
         SUBMENÚ DEPORTISTAS
    ================================================= -->

    <div class="collapse ms-3" id="submenuDeportistas">

        <ul class="nav flex-column">


            <!-- =================================================
                 DEPORTISTAS REGISTRADOS
                 
                 Lleva al listado principal.
            ================================================= -->

            <?php if (
                tiene_permiso('deportistas') ||
                tiene_permiso('ver_deportistas')
            ): ?>

            <li class="nav-item">

                <a
                    href="<?= $url_base ?>/modulos/deportistas/"
                    class="nav-link text-white"
                >

                    <i class="fa-solid fa-users"></i>

                    Deportistas registrados

                </a>

            </li>

            <?php endif; ?>


            <!-- =================================================
                 REGISTRAR DEPORTISTA
                 
                 Igual que Usuarios:
                 
                 index.php?crear=1
                 
                 Esto permitirá que el index cargue normalmente
                 con header, CSS, sidebar, etc., y posteriormente
                 abra automáticamente el formulario/modal.
            ================================================= -->

            <?php if (tiene_permiso('deportistas')): ?>

            <li class="nav-item">

                <a
                    href="<?= $url_base ?>/modulos/deportistas/index.php?crear=1"
                    class="nav-link text-white"
                >

                    <i class="fa-solid fa-plus"></i>

                    Registrar deportista

                </a>

            </li>

            <?php endif; ?>


            <!-- =================================================
                 ASISTENCIA
            ================================================= -->

            <?php if (tiene_permiso('asistencia')): ?>

            <li class="nav-item">

                <a
                    href="<?= $url_base ?>/modulos/asistencia/"
                    class="nav-link text-white"
                >

                    <i class="fa-solid fa-calendar-check"></i>

                    Asistencia

                </a>

            </li>

            <?php endif; ?>


            <!-- =================================================
                 CONVOCATORIA
            ================================================= -->

            <?php if (
                tiene_permiso('convocatoria') ||
                tiene_permiso('deportistas')
            ): ?>

            <li class="nav-item">

                <a
                    href="<?= $url_base ?>/modulos/deportistas/convocatoria.php"
                    class="nav-link text-white"
                >

                    <i class="fa-solid fa-clipboard-list"></i>

                    Convocatoria

                </a>

            </li>

            <?php endif; ?>


        </ul>

    </div>

</li>

<?php endif; ?>

    <!-- ====================================== -->
    <!-- MÓDULO FINANCIERO                      -->
    <!-- ====================================== -->

    <?php if (
        tiene_permiso('financiero_resumen') ||
        tiene_permiso('financiero_mensualidades') ||
        tiene_permiso('financiero_matriculas') ||
        tiene_permiso('financiero_uniformes') ||
        tiene_permiso('financiero_egresos') ||
        tiene_permiso('financiero_novedades') ||
        tiene_permiso('financiero_movimientos') ||
        tiene_permiso('financiero_reportes')
    ): ?>

    <li class="nav-item mb-2">

        <!-- ================================== -->
        <!-- TÍTULO FINANCIERO                  -->
        <!-- ================================== -->

        <a
            class="nav-link text-white d-flex justify-content-between align-items-center"
            data-bs-toggle="collapse"
            href="#submenuFinanciero"
            role="button"
            aria-expanded="false"
            aria-controls="submenuFinanciero"
        >

            <span>

                <i class="fa-solid fa-money-bill-transfer"></i>

                Financiero

            </span>

            <span>▼</span>

        </a>


        <!-- ================================== -->
        <!-- SUBMENÚ FINANCIERO                 -->
        <!-- ================================== -->

        <div class="collapse ms-3" id="submenuFinanciero">

            <ul class="nav flex-column">


                <!-- ================================== -->
                <!-- RESUMEN FINANCIERO                  -->
                <!-- ================================== -->

                <?php if (tiene_permiso('financiero_resumen')): ?>

                <li class="nav-item">

                    <a
                        href="<?= $url_base ?>/modulos/financiero/resumen/index.php"
                        class="nav-link text-white"
                    >

                        <i class="fa-solid fa-chart-pie"></i>

                        Resumen financiero

                    </a>

                </li>

                <?php endif; ?>


                <!-- ================================== -->
                <!-- MENSUALIDADES                       -->
                <!-- ================================== -->

                <?php if (tiene_permiso('financiero_mensualidades')): ?>

                <li class="nav-item">

                    <a
                        href="<?= $url_base ?>/modulos/financiero/mensualidades/index.php"
                        class="nav-link text-white"
                    >

                        <i class="fa-solid fa-wallet"></i>

                        Mensualidades

                    </a>

                </li>

                <?php endif; ?>


                <!-- ================================== -->
                <!-- MATRÍCULAS                          -->
                <!-- ================================== -->

                <?php if (tiene_permiso('financiero_matriculas')): ?>

                <li class="nav-item">

                    <a
                        href="<?= $url_base ?>/modulos/financiero/matriculas/index.php"
                        class="nav-link text-white"
                    >

                        <i class="fa-solid fa-file-signature"></i>

                        Matrículas

                    </a>

                </li>

                <?php endif; ?>


                <!-- ================================== -->
                <!-- UNIFORMES                           -->
                <!-- ================================== -->

                <?php if (tiene_permiso('financiero_uniformes')): ?>

                <li class="nav-item">

                    <a
                        href="<?= $url_base ?>/modulos/financiero/uniformes/index.php"
                        class="nav-link text-white"
                    >

                        <i class="fa-solid fa-shirt"></i>

                        Uniformes

                    </a>

                </li>

                <?php endif; ?>


                <!-- ================================== -->
                <!-- EGRESOS                             -->
                <!-- ================================== -->

                <?php if (tiene_permiso('financiero_egresos')): ?>

                <li class="nav-item">

                    <a
                        href="<?= $url_base ?>/modulos/financiero/egresos/index.php"
                        class="nav-link text-white"
                    >

                        <i class="fa-solid fa-money-bill-trend-up"></i>

                        Egresos

                    </a>

                </li>

                <?php endif; ?>


                <!-- ================================== -->
                <!-- NOVEDADES                           -->
                <!-- ================================== -->

                <?php if (tiene_permiso('financiero_novedades')): ?>

                <li class="nav-item">

                    <a
                        href="<?= $url_base ?>/modulos/financiero/novedades/index.php"
                        class="nav-link text-white"
                    >

                        <i class="fa-solid fa-clipboard-list"></i>

                        Novedades

                    </a>

                </li>

                <?php endif; ?>


                <!-- ================================== -->
                <!-- MOVIMIENTOS                         -->
                <!-- ================================== -->

                <?php if (tiene_permiso('financiero_movimientos')): ?>

                <li class="nav-item">

                    <a
                        href="<?= $url_base ?>/modulos/financiero/movimientos/index.php"
                        class="nav-link text-white"
                    >

                        <i class="fa-solid fa-money-bill-transfer"></i>

                        Movimientos

                    </a>

                </li>

                <?php endif; ?>


                <!-- ================================== -->
                <!-- REPORTES FINANCIEROS               -->
                <!-- ================================== -->

                <?php if (tiene_permiso('financiero_reportes')): ?>

                <li class="nav-item">

                    <a
                        href="<?= $url_base ?>/modulos/financiero/reportes/index.php"
                        class="nav-link text-white"
                    >

                        <i class="fa-solid fa-chart-column"></i>

                        Reportes financieros

                    </a>

                </li>

                <?php endif; ?>


            </ul>

        </div>

    </li>

    <?php endif; ?>

            <!-- CMS -->
            <?php if (tiene_permiso('cms')): ?>
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

                            <a
                                href="<?= $url_base ?>/admin/edit_page_Content/editar_contacto.php"
                                class="nav-link text-white"
                            >
                                <i class="fa-solid fa-phone"></i>
                                Contacto
                            </a>

                        </li>

                        <li class="nav-item">

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
            <?php endif; ?>

            <!-- AUDITORIA -->
            <?php if (tiene_permiso('auditoria')): ?>
  <li class="nav-item mb-2">

    <a
        href="<?= $url_base ?>/modulos/auditoria/index.php"
        class="nav-link text-white"
    >

        <i class="fa-solid fa-file-shield"></i>

        Auditoría

    </a>

</li>

<?php endif; ?>

<!-- =========================================================
     MÓDULO REPORTES

     Reportes contiene tres opciones:

     1. Usuarios
     2. Deportistas
     3. Asistencias

     Los filtros como activos/inactivos se manejarán
     dentro de cada reporte y NO en este menú.
========================================================= -->

<?php if (
    tiene_permiso('reportes') ||
    tiene_permiso('reportes_asistencia') ||
    tiene_permiso('reportes_deportistas')
): ?>

<li class="nav-item mb-2">

    <!-- =====================================================
         MENÚ PRINCIPAL REPORTES
    ====================================================== -->

    <a
        class="nav-link text-white d-flex justify-content-between align-items-center"
        data-bs-toggle="collapse"
        href="#submenuReportes"
        role="button"
        aria-expanded="false"
        aria-controls="submenuReportes"
    >

        <span>
            <i class="fa-solid fa-chart-column"></i>
            Reportes
        </span>

        <span>▼</span>

    </a>


    <!-- =====================================================
         SUBMENÚ DE REPORTES
    ====================================================== -->

    <div
        class="collapse ms-3"
        id="submenuReportes"
    >

        <ul class="nav flex-column">


            <!-- =================================================
                 REPORTE DE USUARIOS
            ================================================== -->

            <?php if (tiene_permiso('reportes')): ?>

            <li class="nav-item">

                <a
                    href="<?= $url_base ?>/modulos/reportes/reportes.php?tipo=usuarios"
                    class="nav-link text-white"
                >

                    <i class="fa-solid fa-users"></i>
                    Usuarios

                </a>

            </li>

            <?php endif; ?>


<!-- =================================================
     REPORTE DE DEPORTISTAS
     
     Deportistas tendrá su propio submenú
     para agrupar las opciones relacionadas.
================================================== -->

<?php if (
    tiene_permiso('reportes_deportistas') ||
    tiene_permiso('reportes_asistencia')
): ?>

<li class="nav-item">

    <!-- MENÚ DEPORTISTAS -->

    <a
        class="nav-link text-white d-flex justify-content-between align-items-center"
        data-bs-toggle="collapse"
        href="#submenuReportesDeportistas"
        role="button"
        aria-expanded="false"
        aria-controls="submenuReportesDeportistas"
    >

        <span>
            <i class="fa-solid fa-person-running"></i>
            Deportistas
        </span>

        <span>▼</span>

    </a>


    <!-- SUBMENÚ DEPORTISTAS -->

    <div
        class="collapse ms-3"
        id="submenuReportesDeportistas"
    >

        <ul class="nav flex-column">


            <!-- REPORTE DE DEPORTISTAS -->

            <?php if (tiene_permiso('reportes_deportistas')): ?>

            <li class="nav-item">

                <a
                    href="<?= $url_base ?>/modulos/reportes/reportes.php?tipo=deportistas"
                    class="nav-link text-white"
                >

                    <i class="fa-solid fa-person-running"></i>
                    Deportistas

                </a>

            </li>

            <?php endif; ?>


            <!-- REPORTE DE ASISTENCIAS -->

            <?php if (tiene_permiso('reportes_asistencia')): ?>

            <li class="nav-item">

                <a
                    href="<?= $url_base ?>/modulos/reportes/reportes.php?tipo=asistencias"
                    class="nav-link text-white"
                >

                    <i class="fa-solid fa-calendar-check"></i>
                    Asistencias

                </a>

            </li>

            <?php endif; ?>


        </ul>

    </div>

</li>

<?php endif; ?>




        </ul>

    </div>

</li>

<?php endif; ?>

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