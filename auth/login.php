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

Se genera un nuevo identificador de sesión para
evitar ataques de Session Fixation y mejorar la
seguridad del inicio de sesión.
=================================================
*/

session_regenerate_id(true);


/*
=================================================
GUARDAR DATOS DEL USUARIO EN LA SESIÓN

Se almacenan los datos principales del usuario
autenticado para utilizarlos durante toda la
sesión sin realizar consultas repetitivas a la
base de datos.

id_usuario : Identificador del usuario.
usuario    : Nombre de usuario.
nombre     : Nombre completo.
rol_id     : Id del rol.
rol         : Nombre del rol.
=================================================
*/

$_SESSION['id_usuario'] = $admin['id'];
$_SESSION['usuario'] = $admin['usuario'];
$_SESSION['nombre'] = $admin['nombre'];
$_SESSION['rol_id'] = $admin['rol_id'];
$_SESSION['rol'] = $admin['rol_nombre'];


/*
=================================================
REDIRECCIONAR AL DASHBOARD

Una vez autenticado correctamente el usuario,
se redirecciona al panel principal del sistema.
=================================================
*/

header("Location: /BFC-dev2/modulos/Dashboard/index.php");
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
        <p style="text-align:center; margin-top:10px;">
            <a href="../auth/registro.php">
                Crear cuenta
            </a>
        </p>

    </form>

</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>