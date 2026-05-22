<div class="d-flex">

    <!-- SIDEBAR -->
    <div 
        id="sidebar" 
        class="bg-dark text-white p-3 vh-100 shadow sidebar" 
        style="width: 250px;"
    >

        <!-- TITULO -->
        <h4 class="text-center mb-4 fw-bold">
            ⚙️ Admin CMS
        </h4>

        <!-- MENU -->
        <ul class="nav flex-column">

            <!-- DASHBOARD -->
            <li class="nav-item mb-2">
                <a href="/BFC-dev2/modulos/Dashboard/index.php" class="nav-link text-white">
                    🏠 Dashboard
                </a>
            </li>

            <!-- ========================= -->
            <!-- USUARIOS -->
            <!-- ========================= -->
            <li class="nav-item mb-2">

                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#submenuUsuarios"
                    role="button">
                    👤 Usuarios
                    <span>▼</span>
                </a>

                <div class="collapse ms-3" id="submenuUsuarios">

                    <ul class="nav flex-column">

                        <li class="nav-item">
                            <a href="/BFC-dev2/modulos/usuarios/" class="nav-link text-white">
                                📋 Listado
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/BFC-dev2/admin/crear_usuarios.php" class="nav-link text-white">
                                ➕ Crear Usuario
                            </a>
                        </li>

                    </ul>

                </div>

            </li>

            <!-- ========================= -->
            <!-- DEPORTISTAS -->
            <!-- ========================= -->
            <li class="nav-item mb-2">

                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#submenuDeportistas"
                    role="button">
                    🏃 Deportistas
                    <span>▼</span>
                </a>

                <div class="collapse ms-3" id="submenuDeportistas">

                    <ul class="nav flex-column">

                        <li class="nav-item">
                            <a href="/BFC-dev2/modulos/deportistas/" class="nav-link text-white">
                                📋 Listado
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/BFC-dev2/modulos/pagos/" class="nav-link text-white">
                                💰 Pagos
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/BFC-dev2/modulos/asistencia/" class="nav-link text-white">
                                📅 Asistencia
                            </a>
                        </li>

                    </ul>

                </div>

            </li>

            <!-- ========================= -->
            <!-- CMS / EDITOR DE PÁGINAS -->
            <!-- ========================= -->
            <li class="nav-item mb-2">

                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#submenuCMS"
                    role="button">
                    🧩 Editar Páginas
                    <span>▼</span>
                </a>

                <div class="collapse ms-3" id="submenuCMS">

                    <ul class="nav flex-column">

                        <li class="nav-item">
                            <a href="/BFC-dev2/admin/edit_page_Content/editar_inicio.php" class="nav-link text-white">
                                🏠 Página principal
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/BFC-dev2/admin/edit_page_Content/editar_contacto.php" class="nav-link text-white">
                                📞 Contacto
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/BFC-dev2/admin/edit_page_Content/editar_quienes_somos.php" class="nav-link text-white">
                                ℹ️ Quiénes somos
                            </a>
                        </li>

                    </ul>

                </div>

            </li>

            <!-- ========================= -->
            <!-- REPORTES -->
            <!-- ========================= -->
            <li class="nav-item mb-2">
                <a href="/BFC-dev2/modulos/reportes/reportes.php" class="nav-link text-white">
                    📊 Reportes
                </a>
            </li>

            <hr class="bg-secondary">

            <!-- LOGOUT -->
            <li class="nav-item">
                <a href="/BFC-dev2/auth/logout.php" class="nav-link text-danger">
                    🚪 Cerrar sesión
                </a>
            </li>

        </ul>

    </div>

    <!-- CONTENIDO -->
    <div id="contenido" class="flex-grow-1 p-4">