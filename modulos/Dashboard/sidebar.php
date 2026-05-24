<!-- FONT AWESOME -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<!-- SIDEBAR CSS -->
<link rel="stylesheet" href="/BFC-dev2/assets/sidebar.css">

<!-- BOTON MOBILE -->
<button class="menu-toggle d-md-none" id="menuToggle">

    <i class="fa-solid fa-bars"></i>

</button>

<!-- OVERLAY -->
<div class="overlay" id="overlay"></div>

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
                href="/BFC-dev2/modulos/Dashboard/index.php" 
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
                            href="/BFC-dev2/modulos/usuarios/" 
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
                            href="/BFC-dev2/modulos/deportistas/" 
                            class="nav-link text-white"
                        >
                            <i class="fa-solid fa-list"></i>
                            Listado
                        </a>

                    </li>

                    <li class="nav-item">

                        <a 
                            href="/BFC-dev2/modulos/pagos/" 
                            class="nav-link text-white"
                        >
                            <i class="fa-solid fa-money-bill-wave"></i>
                            Pagos
                        </a>

                    </li>

                    <li class="nav-item">

                        <a 
                            href="/BFC-dev2/modulos/asistencia/" 
                            class="nav-link text-white"
                        >
                            <i class="fa-solid fa-calendar-check"></i>
                            Asistencia
                        </a>

                    </li>


                    <li class="nav-item">

                    <a 
                        href="/BFC-dev2/modulos/deportistas/convocatoria.php"
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
                            href="/BFC-dev2/admin/edit_page_Content/editar_inicio.php"
                            class="nav-link text-white"
                        >
                            <i class="fa-solid fa-house"></i>
                            Página principal
                        </a>

                    </li>

                    <li class="nav-item">

                        <a 
                            href="/BFC-dev2/admin/edit_page_Content/editar_contacto.php"
                            class="nav-link text-white"
                        >
                            <i class="fa-solid fa-phone"></i>
                            Contacto
                        </a>

                    </li>

                    <li class="nav-item">

                        <a 
                            href="/BFC-dev2/admin/edit_page_Content/editar_quienes_somos.php"
                            class="nav-link text-white"
                        >
                            <i class="fa-solid fa-circle-info"></i>
                            Quiénes somos
                        </a>

                    </li>

                </ul>

            </div>

        </li>

        <!-- REPORTES -->
        <li class="nav-item mb-2">

            <a 
                href="/BFC-dev2/modulos/reportes/reportes.php"
                class="nav-link text-white"
            >
                <i class="fa-solid fa-chart-column"></i>
                Reportes
            </a>

        </li>

        <hr>

        <!-- LOGOUT -->
        <li class="nav-item">

            <a 
                href="/BFC-dev2/auth/logout.php"
                class="nav-link text-danger"
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>

        </li>

    </ul>

</div>