<?php
session_start();

// Conexión PDO
include("../includes/conexion.php");

/** @var PDO $conexion */

$error = "";

// Procesar login
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    // Validar campos
    if (empty($usuario) || empty($password)) {

        $error = "Completa todos los campos.";

    } else {

        // Buscar usuario
        /*
=================================================
CONSULTAR USUARIO

Se consulta el usuario que intenta iniciar sesión.

Además de los datos del usuario, se obtiene el
nombre del rol mediante una relación con la
tabla "rol".
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
                CONSULTAR PERMISOS DEL ROL DE LA BASE DE DATOS
                =================================================
                */
                $sqlPermisos = "
                    SELECT p.modulo 
                    FROM permiso p
                    INNER JOIN rol_permiso rp ON p.id = rp.permiso_id
                    WHERE rp.rol_id = :rol_id
                ";
                $stmtPermisos = $conexion->prepare($sqlPermisos);
                $stmtPermisos->execute([':rol_id' => $admin['rol_id']]);
                
                // Extrae la lista simple de módulos permitidos
                $permisos_db = $stmtPermisos->fetchAll(PDO::FETCH_COLUMN);

                /*
                =================================================
                GUARDAR DATOS DEL USUARIO Y PERMISOS EN LA SESIÓN
                =================================================
                */
                $_SESSION['id_usuario'] = $admin['id'];
                $_SESSION['usuario']    = $admin['usuario'];
                $_SESSION['nombre']     = $admin['nombre'];
                $_SESSION['rol_id']     = $admin['rol_id'];
                $_SESSION['rol']        = $admin['rol_nombre'];
                $_SESSION['permisos']   = $permisos_db; // <-- Guardamos los permisos del rol

                /*
                =================================================
                REDIRECCIONAR AL DASHBOARD

Una vez autenticado correctamente el usuario,
se redirecciona al panel principal.

La ruta usa la configuración general del sistema
para funcionar en:

LOCAL:
http://localhost/BFC-dev2/

WEB:
https://bellavistafcdev.page.gd/

sin cambiar código.
=================================================
*/


require_once("../includes/config.php");


header("Location: ".$url_base."/modulos/dashboard/index.php");

exit;

            
            } else {

                $error = "Contraseña incorrecta.";

            }
        }

    }   // ← Cierra el else de "empty()"

}       // ← ESTA LLAVE Cierra el if ($_SERVER["REQUEST_METHOD"] == "POST")

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bellavista FC</title>

    <link rel="stylesheet" href="/BFC-dev2/assets/estilo.css">

    <style>

        .login-form{
            width:300px;
            margin:100px auto;
            padding:25px;
            border:1px solid #ccc;
            border-radius:8px;
            background:#f9f9f9;
        }

        .login-form input{
            width:100%;
            margin-bottom:10px;
            padding:8px;
        }

        .login-form button{
            width:100%;
            padding:8px;
            background:#0A4FA3;
            color:white;
            border:none;
            cursor:pointer;
        }

        .login-form button:hover{
            background:#083a7c;
        }

    </style>
</head>

<body>

<?php include("../includes/header.php"); ?>

<div class="login-form">

    <h2>Iniciar sesión</h2>

    <?php if(!empty($error)): ?>

        <p style="color:red;">
            <?php echo $error; ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <label>Usuario</label>

        <input
            type="text"
            name="usuario"
            required
        >

        <label>Contraseña</label>

        <input
            type="password"
            name="password"
            required
        >

        <button type="submit">
            Entrar
        </button>

        <p style="text-align:center;margin-top:10px;">
    <a href="recuperar.php">
        ¿Olvidaste tu contraseña?
    </a>
</p>

    </form>

</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>