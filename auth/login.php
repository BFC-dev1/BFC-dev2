<?php
session_start();

/*
=================================================
CONFIGURACIÓN GENERAL
=================================================
*/
require_once(__DIR__ . "/../includes/config.php");

// Conexión PDO
require_once(__DIR__ . "/../includes/conexion.php");

/** @var PDO $conexion */

$error = "";

// Procesar login
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST['usuario'] ?? "");
    $password = trim($_POST['password'] ?? "");

    // Validar campos
    if (empty($usuario) || empty($password)) {

        $error = "Completa todos los campos.";

    } else {

        /*
        =================================================
        CONSULTAR USUARIO
        =================================================
        */

        $sql = "
            SELECT
                u.*,
                r.nombre AS rol_nombre

            FROM usuario u

            LEFT JOIN rol r
                ON u.rol_id = r.id

            WHERE u.usuario = :usuario
        ";

        $stmt = $conexion->prepare($sql);

        $stmt->bindParam(":usuario", $usuario);

        $stmt->execute();

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar usuario
        if (!$admin) {

            $error = "Usuario no encontrado.";

        } else {

            // Verificar estado del usuario
            if ($admin['estado'] != 'activo') {

                $error = "Usuario inactivo. Contacte al administrador del sistema.";

            } elseif ($password == $admin['password']) {

                /*
                =================================================
                REGENERAR EL ID DE SESIÓN
                =================================================
                */

                session_regenerate_id(true);

                /*
                =================================================
                CONSULTAR PERMISOS DEL ROL
                =================================================
                */

                $sqlPermisos = "
                    SELECT p.modulo
                    FROM permiso p
                    INNER JOIN rol_permiso rp
                        ON p.id = rp.permiso_id
                    WHERE rp.rol_id = :rol_id
                ";

                $stmtPermisos = $conexion->prepare($sqlPermisos);

                $stmtPermisos->execute([
                    ':rol_id' => $admin['rol_id']
                ]);

                $permisos_db = $stmtPermisos->fetchAll(PDO::FETCH_COLUMN);

                /*
                =================================================
                GUARDAR DATOS DEL USUARIO EN LA SESIÓN
                =================================================
                */

                $_SESSION['id_usuario'] = $admin['id'];
                $_SESSION['usuario']    = $admin['usuario'];
                $_SESSION['nombre']     = $admin['nombre'];
                $_SESSION['rol_id']     = $admin['rol_id'];
                $_SESSION['rol']        = $admin['rol_nombre'];
                $_SESSION['permisos']   = $permisos_db;

                /*
                =================================================
                REDIRECCIÓN AL DASHBOARD
                =================================================
                */

                header("Location: " . $url_base . "/modulos/dashboard/index.php");

                exit;

            } else {

                $error = "Contraseña incorrecta.";

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Iniciar sesión - Bellavista FC</title>

    <!-- FAVICON -->
    <link
        rel="icon"
        type="image/x-icon"
        href="<?= $favicon_url ?>"
    >

    <!-- CSS GENERAL -->
    <link
        rel="stylesheet"
        href="<?= $url_base ?>/<?= $css_base ?>/estilo.css"
    >

    <!-- CSS DEL LOGIN -->
    <link
        rel="stylesheet"
        href="<?= $url_base ?>/<?= $css_base ?>/login.css"
    >

</head>

<body>

<?php include("../includes/header.php"); ?>


<main class="login-page">

    <section class="login-card">


        <!-- =================================================
             LOGO Y ENCABEZADO
             ================================================= -->

        <div class="login-header">

<div class="login-logo">
    <img
        src="<?= $img_url ?>/logo1.png"
        alt="Bellavista FC"
        style="display:block !important; width:120px !important; height:auto !important; opacity:1 !important;"
    >
</div>

            </div>


            <h1>Iniciar sesión</h1>

            <p>
                Accede al sistema de Bellavista FC
            </p>

        </div>


        <!-- =================================================
             ERROR
             ================================================= -->

        <?php if (!empty($error)): ?>

            <div class="login-error">

                <span class="login-error-icon">

                    <!-- Icono de advertencia SVG -->
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="M12 3L2.5 20h19L12 3z"></path>
                        <path d="M12 9v5"></path>
                        <path d="M12 17.5h.01"></path>
                    </svg>

                </span>

                <span>
                    <?= htmlspecialchars($error) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- =================================================
             FORMULARIO
             ================================================= -->

        <form
            method="POST"
            class="login-form"
        >


            <!-- USUARIO -->

            <div class="login-field">

                <label for="usuario">
                    Usuario
                </label>

                <div class="login-input-container">

                    <span class="login-input-icon">

                        <!-- Icono usuario SVG -->
                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"></path>
                        </svg>

                    </span>


                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        placeholder="Ingresa tu usuario"
                        autocomplete="username"
                        required
                    >

                </div>

            </div>


            <!-- CONTRASEÑA -->

            <div class="login-field">

                <label for="password">
                    Contraseña
                </label>

                <div class="login-input-container">

                    <span class="login-input-icon">

                        <!-- Icono candado SVG -->
                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <rect
                                x="5"
                                y="10"
                                width="14"
                                height="10"
                                rx="2"
                            ></rect>

                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>

                        </svg>

                    </span>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ingresa tu contraseña"
                        autocomplete="current-password"
                        required
                    >

                </div>

            </div>


            <!-- BOTÓN -->

            <button
                type="submit"
                class="login-button"
            >

                <span>
                    Entrar
                </span>

                <span class="login-button-arrow">

                    <!-- Flecha SVG -->
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="M5 12h13"></path>
                        <path d="M13 6l6 6-6 6"></path>
                    </svg>

                </span>

            </button>


            <!-- RECUPERAR CONTRASEÑA -->

            <div class="login-forgot">

                <a href="recuperar.php">
                    ¿Olvidaste tu contraseña?
                </a>

            </div>


        </form>

    </section>

</main>


<?php include("../includes/footer.php"); ?>

</body>

</html>