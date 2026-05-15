<div class="d-flex">

    <!-- SIDEBAR -->
    <div id="sidebar" class="bg-dark text-white p-3 vh-100 shadow sidebar" style="width: 250px;">

        <h4 class="text-center mb-4 fw-bold">⚙️ Admin</h4>

        <ul class="nav flex-column">

            <li class="nav-item mb-2">
                <a href="index.php" class="nav-link text-white">
                    🏠 Dashboard
                </a>
            </li>

            <li class="nav-item mb-2">
                <a href="usuarios.php" class="nav-link text-white">
                    👤 Usuarios
                </a>
            </li>

            <li class="nav-item mb-2">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" 
                   data-bs-toggle="collapse" 
                   href="#submenuDeportistas">
                    🏃 Deportistas
                    <span>▼</span>
                </a>

                <div class="collapse ms-3" id="submenuDeportistas">
                    <ul class="nav flex-column">

                        <li class="nav-item">
                            <a href="<?php echo $url_base; ?>modulos/deportistas/" class="nav-link text-white">
                                📋 Listado
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="pagos.php" class="nav-link text-white">
                                💰 Pagos
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?php echo $url_base; ?>modulos/asistencia/" class="nav-link text-white">
                                📅 Asistencia
                            </a>
                        </li>

                    </ul>
                </div>
            </li>

            <li class="nav-item mb-2">
                <a href="reportes.php" class="nav-link text-white">
                    📊 Reportes
                </a>
            </li>

            <hr class="bg-secondary">

            <li class="nav-item">
                <a href="/BFC-dev2/auth/logout.php" class="nav-link text-danger">
                🚪 Cerrar sesión
                </a>
            </li>

        </ul>
    </div>

    <!-- CONTENIDO -->
    <div id="contenido" class="flex-grow-1 p-4">