<?php

/*
=================================================
INICIAR SESIÓN
=================================================
*/

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/*
=================================================
CONEXIÓN
=================================================
*/

include("../../modulos/conexion_modulos.php");

/*
=================================================
FUNCIÓN DE AUDITORÍA
=================================================
*/

require_once("../../modulos/auditoria/funciones/registrar_auditoria.php");


/*
=================================================
CAMBIAR ESTADO DEL DEPORTISTA
=================================================
*/

if(isset($_GET['id'])){

    $id = $_GET['id'];

    /*
    =============================================
    OBTENER ESTADO ACTUAL
    =============================================
    */

    $stm = $conexion->prepare("
        SELECT estado
        FROM deportista
        WHERE id = :id
    ");

    $stm->execute([
        ":id"=>$id
    ]);

    $deportista = $stm->fetch(PDO::FETCH_ASSOC);

    if($deportista){

        /*
        =============================================
        CALCULAR NUEVO ESTADO
        =============================================
        */

        $estadoAnterior = $deportista["estado"];

        $nuevoEstado =
            ($estadoAnterior == "activo")
            ? "inactivo"
            : "activo";

        /*
        =============================================
        ACTUALIZAR ESTADO
        =============================================
        */

        $update = $conexion->prepare("
            UPDATE deportista
            SET estado = :estado
            WHERE id = :id
        ");

        $update->execute([

            ":estado"=>$nuevoEstado,
            ":id"=>$id

        ]);

        /*
        =============================================
        REGISTRAR AUDITORÍA
        =============================================
        */

        $cambios = [

            "estado"=>[

                "antes"=>$estadoAnterior,
                "despues"=>$nuevoEstado

            ]

        ];

        registrarAuditoria(

            $conexion,

            "deportista",

            $id,

            "CAMBIAR_ESTADO",

            $cambios,

            "Cambio de estado del deportista"

        );

        echo "ok";

    }

}