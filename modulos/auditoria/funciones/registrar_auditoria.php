<?php


/*
=================================================
FUNCIÓN CENTRAL DE AUDITORÍA
=================================================

Esta función registra:

- quién hizo la acción
- qué tabla modificó
- qué registro cambió
- qué campos cambiaron
- valores anteriores
- valores nuevos
- IP
- navegador

Se usa desde cualquier módulo.

=================================================
*/


function registrarAuditoria(

    $conexion,

    $tabla,

    $registro_id,

    $accion,

    $cambios,

    $descripcion = null

){


    /*
    =============================================
    CREAR ID ÚNICO DE OPERACIÓN
    =============================================

    Una operación puede tener varios cambios.

    Ejemplo:

    EDITAR deportista

    operación_id:
    abc-123


    cambios:

    telefono
    categoria
    estado

    Todos quedan agrupados.

    =============================================
    */


    $operacion_id = uniqid("", true);



    /*
    =============================================
    USUARIO ACTUAL
    =============================================
    */

    $usuario_id = $_SESSION["usuario_id"] 
            ?? $_SESSION["id_usuario"] 
            ?? $_SESSION["id"] 
            ?? null;


    /*
    =============================================
    INFORMACIÓN DEL CLIENTE
    =============================================
    */

    $ip = $_SERVER["REMOTE_ADDR"] ?? null;


    $navegador = $_SERVER["HTTP_USER_AGENT"] ?? null;



    /*
    =============================================
    INSERTAR CADA CAMBIO
    =============================================
    */


    foreach($cambios as $campo=>$valores){



        $stmt = $conexion->prepare("

        INSERT INTO auditoria
        (
            operacion_id,
            usuario_id,
            tabla_afectada,
            registro_id,
            accion,
            campo,
            valor_anterior,
            valor_nuevo,
            descripcion,
            ip,
            navegador
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )

        ");



        $stmt->execute([


            $operacion_id,


            $usuario_id,


            $tabla,


            $registro_id,


            $accion,


            $campo,


            $valores["antes"],


            $valores["despues"],


            $descripcion,


            $ip,


            $navegador


        ]);



    }



    return $operacion_id;


}



