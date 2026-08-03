<?php


/*
=================================================
INICIAR SESIÓN

Se verifica si la sesión ya fue iniciada.
Si no existe una sesión activa, se inicia
para poder identificar el usuario que realiza
la eliminación y registrar la acción en la
auditoría del sistema.

=================================================
*/

if(session_status() === PHP_SESSION_NONE){

    session_start();

}

/*
=================================================
CONEXIÓN A LA BASE DE DATOS

Se incluye el archivo encargado de establecer
la conexión con la base de datos mediante PDO,
la cual será utilizada para consultar,
eliminar registros y guardar la auditoría.

=================================================
*/

include("../conexion_modulos.php");

/*
=================================================
FUNCIÓN DE AUDITORÍA

Se carga el archivo que contiene la función
registrarAuditoria(), encargada de almacenar
el historial de las acciones realizadas por
los usuarios del sistema.

=================================================
*/

require_once("../auditoria/funciones/registrar_auditoria.php");


/*
=================================================
VALIDAR ID DEL USUARIO

Se verifica que el identificador del usuario
haya sido enviado mediante la URL. Si no existe,
el proceso se cancela y se regresa al listado
de usuarios.

=================================================
*/

if(!isset($_GET["id"])){

    header("Location:index.php");
    exit;

}


/*
=================================================
OBTENER ID DEL USUARIO

Se obtiene el identificador recibido por la URL
y se convierte a tipo entero para garantizar
que únicamente se procese un valor numérico.

=================================================
*/

$id = (int) $_GET["id"];


try{

    /*
    =============================================
    CONSULTAR USUARIO

    Se obtienen los datos antes de eliminar
    el registro para almacenarlos en auditoría.

    =============================================
    */

    $stmt = $conexion->prepare("
    SELECT *
    FROM usuario
    WHERE id = :id
    ");

    $stmt->execute([
        ":id"=>$id
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


  /*
=============================================
VALIDAR EXISTENCIA DEL USUARIO

Se verifica que el usuario exista en la base
de datos. Si no se encuentra el registro,
se cancela el proceso y se regresa al listado.

=============================================
*/

    if(!$usuario){

        header("Location:index.php");
        exit;

    }


    /*
    =============================================
    PREPARAR CAMBIOS

    En una eliminación el valor nuevo será NULL.

    =============================================
    */

    $cambios = [

        "nombre"=>[
            "antes"=>$usuario["nombre"],
            "despues"=>null
        ],

        "usuario"=>[
            "antes"=>$usuario["usuario"],
            "despues"=>null
        ],

        "documento"=>[
            "antes"=>$usuario["documento"],
            "despues"=>null
        ],

        "rol_id"=>[
            "antes"=>$usuario["rol_id"],
            "despues"=>null
        ],

        "estado"=>[
            "antes"=>$usuario["estado"],
            "despues"=>null
        ]

    ];


/*
=============================================
REGISTRAR AUDITORÍA

Antes de eliminar el usuario se registra la
operación en la auditoría, almacenando los
datos del registro y la información del
usuario que realizó la acción.

=============================================
*/

    registrarAuditoria(

        $conexion,

        "usuario",

        $id,

        "ELIMINAR",

        $cambios,

        "Eliminación del usuario: ".$usuario["usuario"]

    );


    /*
    =============================================
    ELIMINAR CATEGORÍAS DEL ENTRENADOR

    Si el usuario tenía categorías asignadas,
    primero se eliminan para evitar errores
    por claves foráneas.

    =============================================
    */

    $deleteCategorias = $conexion->prepare("
    DELETE
    FROM entrenador_categoria
    WHERE usuario_id = :usuario_id
    ");

    $deleteCategorias->execute([
        ":usuario_id"=>$id
    ]);


/*
=============================================
ELIMINAR USUARIO

Después de registrar la auditoría y eliminar
los registros relacionados, se elimina el
usuario de la base de datos.

=============================================
*/

    $deleteUsuario = $conexion->prepare("
    DELETE
    FROM usuario
    WHERE id = :id
    ");

    $deleteUsuario->execute([
        ":id"=>$id
    ]);


/*
=============================================
REDIRECCIONAR

Una vez finalizada la eliminación, se vuelve
al listado de usuarios mostrando un mensaje
de confirmación de la operación realizada.

=============================================
*/

    header("Location:index.php?eliminado=1");
    exit;

/*
=============================================
MANEJO DE ERRORES

Si ocurre una excepción durante el proceso
de eliminación, se muestra el mensaje de
error generado por la base de datos para
facilitar su identificación durante el
desarrollo.

=============================================
*/

}catch(PDOException $e){

    die($e->getMessage());

}