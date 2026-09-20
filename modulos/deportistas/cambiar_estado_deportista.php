<?php

/*
=================================================
INICIAR SESIÓN Y VERIFICAR PERMISOS
=================================================
*/

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once("../../includes/verificar_roles.php");
require_once("../../includes/config.php");

// ✅ BLOQUEO DE SEGURIDAD
if (!tiene_permiso('deportistas')) {
    http_response_code(403);
    exit("No tienes permisos para realizar esta acción.");
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

    $id = (int)$_GET['id'];

    /*
    =============================================
    OBTENER ESTADO Y DORSAL ACTUAL
    =============================================
    */

    $stm = $conexion->prepare("
        SELECT id, estado, dorsal, nombre
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

        $dorsal = $deportista["dorsal"];

        /*
        =============================================
        VALIDAR SI EL DORSAL ESTÁ OCUPADO AL ACTIVAR
        =============================================
        */
        if ($nuevoEstado == "activo" && !empty($dorsal)) {

            $stmt_dorsal = $conexion->prepare("
                SELECT id, nombre 
                FROM deportista 
                WHERE dorsal = :dorsal 
                  AND estado = 'activo' 
                  AND id != :id
            ");

            $stmt_dorsal->execute([
                ":dorsal" => $dorsal,
                ":id"     => $id
            ]);

            $jugador_ocupado = $stmt_dorsal->fetch(PDO::FETCH_ASSOC);

            if ($jugador_ocupado) {
                // Denegar el cambio de estado notificando el conflicto
                header('Content-Type: application/json');
                echo json_encode([
                    "status" => "error",
                    "mensaje" => "No se puede activar a " . htmlspecialchars($deportista['nombre']) . " porque el dorsal #" . $dorsal . " ya está asignado al deportista activo: " . htmlspecialchars($jugador_ocupado['nombre']) . ". Modifica su dorsal antes de activarlo."
                ]);
                exit;
            }
        }

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

        header('Content-Type: application/json');
        echo json_encode([
            "status" => "success",
            "nuevo_estado" => $nuevoEstado
        ]);
        exit;

    }

}