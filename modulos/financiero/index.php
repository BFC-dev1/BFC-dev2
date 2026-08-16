<?php

/*
=========================================================
MÓDULO FINANCIERO - RESUMEN FINANCIERO
SISTEMA: BELLAVISTA FC
ARCHIVO: /modulos/financiero/resumen/index.php

DESCRIPCIÓN:

Dashboard financiero general del sistema.

Este módulo consolida la información registrada
en la tabla movimientos.

FUENTE CENTRAL:

    movimientos

TIPOS:

    INGRESO
    EGRESO

ORÍGENES:

    MENSUALIDAD
    MATRICULA
    EGRESO
    UNIFORME
    OTRO

IMPORTANTE:

Este archivo NO registra movimientos.

Solamente consulta y presenta información
financiera consolidada.

=========================================================
*/


/*
=========================================================
1. VERIFICAR PERMISOS Y CONFIGURACIÓN
=========================================================
*/

require_once("../../../includes/verificar_roles.php");

require_once("../../../includes/config.php");


/*
---------------------------------------------------------
PERMISO DEL MÓDULO
---------------------------------------------------------

Permitimos el acceso utilizando el permiso financiero
general.

Si posteriormente creamos un permiso específico
"resumen_financiero", solamente será necesario cambiar
esta validación.
---------------------------------------------------------
*/

if (
    !tiene_permiso('financiero')
    &&
    !tiene_permiso('movimientos')
) {

    header(
        "Location: "
        . $url_base
        . "/modulos/dashboard/index.php"
    );

    exit;
}


/*
=========================================================
2. AUDITORÍA
=========================================================
*/

include(
    "../../../modulos/auditoria/funciones/registrar_auditoria.php"
);


/*
=========================================================
3. CONEXIÓN A BASE DE DATOS
=========================================================
*/

include(
    "../../../modulos/conexion_modulos.php"
);