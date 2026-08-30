<?php

/*
=================================================
API LOGIN - ANDROID
BELLAVISTA FC
=================================================
*/

header('Content-Type: application/json; charset=utf-8');

/*
=================================================
EVITAR ERRORES HTML
=================================================
*/

ini_set('display_errors', '0');
error_reporting(E_ALL);


/*
=================================================
INICIAR SESIÓN
=================================================
*/

session_start();


/*
=================================================
CONEXIÓN BASE DE DATOS
=================================================
*/

require_once(__DIR__ . "/../includes/conexion.php");


/*
=================================================
VERIFICAR MÉTODO
=================================================
*/

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'DESCONOCIDO';

if ($metodo !== 'POST') {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Método no permitido.',
        'metodo_recibido' => $metodo,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
        'post_recibido' => $_POST
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
=================================================
RECIBIR DATOS POST
=================================================
*/

$usuario = isset($_POST['usuario'])
    ? trim($_POST['usuario'])
    : '';

$password = isset($_POST['password'])
    ? $_POST['password']
    : '';


/*
=================================================
VALIDAR DATOS
=================================================
*/

if ($usuario === '') {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se recibió el usuario.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


if ($password === '') {

    echo json_encode([
        'success' => false,
        'mensaje' => 'No se recibió la contraseña.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
=================================================
PROCESAR LOGIN
=================================================
*/

try {

    /*
    =============================================
    BUSCAR USUARIO
    =============================================
    */

    $sql = "

        SELECT
            u.*,
            r.nombre AS rol_nombre

        FROM usuario u

        LEFT JOIN rol r
            ON u.rol_id = r.id

        WHERE u.usuario = :usuario

        LIMIT 1

    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':usuario' => $usuario
    ]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    =============================================
    USUARIO NO ENCONTRADO
    =============================================
    */

    if (!$admin) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'Usuario no encontrado.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /*
    =============================================
    VERIFICAR ESTADO
    =============================================
    */

    if (
        !isset($admin['estado']) ||
        $admin['estado'] !== 'activo'
    ) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'Usuario inactivo. Contacte al administrador del sistema.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /*
    =============================================
    VERIFICAR CONTRASEÑA
    =============================================

    Se mantiene el mismo sistema actual
    del login web.
    =============================================
    */

    if ($password != $admin['password']) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'Contraseña incorrecta.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /*
    =============================================
    REGENERAR SESIÓN
    =============================================
    */

    session_regenerate_id(true);


    /*
    =============================================
    CONSULTAR PERMISOS
    =============================================
    */

    $sqlPermisos = "

        SELECT
            p.modulo

        FROM permiso p

        INNER JOIN rol_permiso rp
            ON p.id = rp.permiso_id

        WHERE rp.rol_id = :rol_id

    ";

    $stmtPermisos =
        $conexion->prepare($sqlPermisos);

    $stmtPermisos->execute([
        ':rol_id' => $admin['rol_id']
    ]);

    $permisos_db =
        $stmtPermisos->fetchAll(
            PDO::FETCH_COLUMN
        );


    /*
    =============================================
    CREAR SESIÓN
    =============================================
    */

    $_SESSION['id_usuario'] =
        $admin['id'];

    $_SESSION['usuario'] =
        $admin['usuario'];

    $_SESSION['nombre'] =
        $admin['nombre'];

    $_SESSION['rol_id'] =
        $admin['rol_id'];

    $_SESSION['rol'] =
        $admin['rol_nombre'];

    $_SESSION['permisos'] =
        $permisos_db;


    /*
    =============================================
    GUARDAR SESIÓN
    =============================================
    */

    session_write_close();


    /*
    =============================================
    RESPUESTA JSON
    =============================================
    */

    echo json_encode([

        'success' => true,

        'mensaje' =>
            'Inicio de sesión correcto.',

        'id_usuario' =>
            $admin['id'],

        'usuario' =>
            $admin['usuario'],

        'nombre' =>
            $admin['nombre'],

        'rol_id' =>
            $admin['rol_id'],

        'rol' =>
            $admin['rol_nombre'],

        'permisos' =>
            $permisos_db

    ], JSON_UNESCAPED_UNICODE);

    exit;


} catch (PDOException $e) {

    /*
    =============================================
    ERROR BASE DE DATOS
    =============================================
    */

    echo json_encode([

        'success' => false,

        'mensaje' =>
            'Error de conexión con la base de datos.'

    ], JSON_UNESCAPED_UNICODE);

    exit;


} catch (Throwable $e) {

    /*
    =============================================
    CUALQUIER OTRO ERROR PHP
    =============================================
    */

    echo json_encode([

        'success' => false,

        'mensaje' =>
            'Error interno del servidor.'

    ], JSON_UNESCAPED_UNICODE);

    exit;
}
?>
