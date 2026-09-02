<?php

/*
=========================================================
MÓDULO: USUARIOS
SISTEMA: BELLAVISTA FC
ARCHIVO: index.php
=========================================================

RESPONSABILIDADES DE ESTE ARCHIVO:

    1. Verificar permisos de acceso.
    2. Cargar configuración general.
    3. Cargar conexión a la base de datos.
    4. Definir identidad del módulo.
    5. Cargar header general.
    6. Cargar formulario/modal de creación.
    7. Consultar usuarios.
    8. Permitir búsqueda.
    9. Mostrar usuarios.
   10. Controlar estado activo/inactivo.
   11. Permitir editar usuarios.
   12. Permitir eliminar usuarios.
   13. Mostrar mensajes de operación.
   14. Cargar footer general.
   15. Adaptar automáticamente la interfaz a celulares/APK.

=========================================================
DISEÑO RESPONSIVE
=========================================================

IMPORTANTE:

NO se crea una segunda tabla para celulares.

Se utiliza UNA SOLA tabla HTML.

EN COMPUTADOR:

    Se mantiene la tabla tradicional.

EN CELULAR / APK:

    La misma tabla se transforma visualmente
    en tarjetas verticales.

Ejemplo:

    ┌───────────────────────────────┐
    │ Juan Pérez                   │
    │                               │
    │ Tipo Documento: CC            │
    │ Documento: 123456789          │
    │ Teléfono: 3001234567          │
    │ Correo: juan@email.com        │
    │ Rol: Entrenador               │
    │ Estado: ● Activo              │
    │                               │
    │ [ Editar ]   [ Eliminar ]    │
    └───────────────────────────────┘

Esto evita:

    - Scroll horizontal.
    - Tabla comprimida.
    - Texto demasiado pequeño.
    - Columnas apretadas.
    - Problemas de visualización en Android WebView.

=========================================================
*/


/*
=========================================================
1. CONFIGURACIÓN GENERAL DEL SISTEMA
=========================================================
*/

require_once(__DIR__ . "/../../includes/config.php");


/*
=========================================================
2. VERIFICAR SESIÓN Y PERMISOS
=========================================================

El módulo Usuarios continúa restringido al rol:

    admin

No modificamos la lógica actual de seguridad.

=========================================================
*/

require_once(__DIR__ . "/../../includes/verificar_roles.php");

permitirRoles([
    "admin"
]);


/*
=========================================================
3. CONEXIÓN A LA BASE DE DATOS
=========================================================
*/

include("../conexion_modulos.php");


/*
=========================================================
4. IDENTIDAD DEL MÓDULO
=========================================================
*/

$modulo_actual = 'Usuarios';

$submodulo_actual = '';

$menu_modulo = [];


/*
=========================================================
5. CARGAR HEADER GENERAL
=========================================================

Todos los módulos utilizan:

    header_modulos.php

=========================================================
*/

include("../../template/header_modulos.php");


/*
=========================================================
6. CARGAR FORMULARIO / MODAL DE CREACIÓN
=========================================================

Se carga después del header para mantener
la estructura visual correcta.

=========================================================
*/

include("crear_usuario.php");


/*
=========================================================
7. BUSCADOR
=========================================================

Permite buscar por:

    - Nombre
    - Tipo de documento
    - Documento
    - Teléfono
    - Correo
    - Rol
    - Estado

=========================================================
*/

$buscar = trim($_GET["buscar"] ?? "");


/*
=========================================================
8. CONSULTAR USUARIOS
=========================================================
*/

if ($buscar != "") {


    /*
    =====================================================
    BÚSQUEDA CON FILTRO
    =====================================================
    */

    $stm = $conexion->prepare("

        SELECT
            u.*,
            r.nombre AS rol_nombre

        FROM usuario u

        LEFT JOIN rol r
            ON u.rol_id = r.id

        WHERE
            u.nombre LIKE :buscar
            OR u.tipo_documento LIKE :buscar
            OR u.documento LIKE :buscar
            OR u.telefono LIKE :buscar
            OR u.correo LIKE :buscar
            OR r.nombre LIKE :buscar
            OR u.estado LIKE :buscar

        ORDER BY u.id DESC

    ");


    $stm->execute([
        ":buscar" => "%" . $buscar . "%"
    ]);


} else {


    /*
    =====================================================
    CONSULTA GENERAL
    =====================================================
    */

    $stm = $conexion->prepare("

        SELECT
            u.*,
            r.nombre AS rol_nombre

        FROM usuario u

        LEFT JOIN rol r
            ON u.rol_id = r.id

        ORDER BY u.id DESC

    ");


    $stm->execute();

}


/*
=========================================================
9. OBTENER RESULTADOS
=========================================================
*/

$usuarios = $stm->fetchAll(PDO::FETCH_ASSOC);


/*
=========================================================
10. MENSAJE DE ELIMINACIÓN EXITOSA
=========================================================
*/

if (isset($_GET["eliminado"])) {
?>

<script>

Swal.fire({

    icon: "success",

    title: "Usuario eliminado",

    text: "El usuario fue eliminado correctamente.",

    confirmButtonText: "Aceptar"

});

</script>

<?php
}


/*
=========================================================
11. MENSAJE DE ACTUALIZACIÓN EXITOSA
=========================================================
*/

if (isset($_GET["actualizado"])) {
?>

<script>

Swal.fire({

    icon: "success",

    title: "Operación Exitosa",

    text: "Los datos fueron actualizados correctamente.",

    confirmButtonText: "Aceptar"

});

</script>

<?php
}


/*
=========================================================
12. BARRA SUPERIOR DEL MÓDULO
=========================================================

EN PC:

    [Dashboard] [Crear Usuario]
                         [Buscar] [Buscar] [Limpiar]

EN CELULAR:

    [Volver al Dashboard]

    [Crear Usuario]

    [Buscar usuario................]

    [Buscar]

    [Limpiar]

=========================================================
*/

?>

<div class="usuarios-toolbar">


    <!-- =================================================
         BOTONES DE ACCIONES
    ================================================== -->

    <div class="usuarios-acciones">


        <!-- =============================================
             VOLVER AL DASHBOARD
        ============================================== -->

        <a
            href="<?= $url_base ?>/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Volver al Dashboard

        </a>


        <?php if (tiene_permiso('usuarios')): ?>


            <!-- =========================================
                 CREAR USUARIO
            ========================================== -->

            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#create"
            >

                Crear Usuario

            </button>


        <?php endif; ?>


    </div>


    <!-- =================================================
         BUSCADOR
    ================================================== -->

    <form
        method="GET"
        class="usuarios-busqueda"
    >


        <!-- =============================================
             CAMPO DE BÚSQUEDA
        ============================================== -->

        <input
            type="text"
            name="buscar"
            class="form-control usuarios-input-busqueda"
            placeholder="Buscar usuario, documento..."
            value="<?php echo htmlspecialchars($buscar); ?>"
        >


        <!-- =============================================
             BOTÓN BUSCAR
        ============================================== -->

        <button
            type="submit"
            class="btn btn-primary"
        >

            Buscar

        </button>


        <!-- =============================================
             BOTÓN LIMPIAR
        ============================================== -->

        <a
            href="index.php"
            class="btn btn-secondary"
        >

            Limpiar

        </a>


    </form>


</div>


<!-- =====================================================
     CONTENEDOR DE LA TABLA
====================================================== -->

<div class="tabla-usuarios-contenedor">


<!-- =====================================================
     TABLA DE USUARIOS
======================================================

IMPORTANTE:

Esta es la ÚNICA tabla del módulo.

PC:

    Tabla tradicional.

Celular/APK:

    Cada fila se convierte visualmente
    en una tarjeta.

====================================================== -->

<table
    class="table table-bordered table-hover text-center align-middle tabla-usuarios"
>


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <thead class="table-dark">

        <tr>

            <th>Nombre</th>

            <th>Tipo Documento</th>

            <th>Documento</th>

            <th>Teléfono</th>

            <th>Correo</th>

            <th>Rol</th>

            <th>Estado</th>

            <th>Acciones</th>

        </tr>

    </thead>


    <!-- =================================================
         CUERPO
    ================================================== -->

    <tbody>


    <?php foreach ($usuarios as $usuario): ?>


        <tr>


            <!-- =========================================
                 NOMBRE
            ========================================== -->

            <td
                data-label="Nombre"
                class="usuario-nombre"
            >

                <?php

                echo htmlspecialchars(
                    $usuario['nombre'] ?? ''
                );

                ?>

            </td>


            <!-- =========================================
                 TIPO DOCUMENTO
            ========================================== -->

            <td data-label="Tipo Documento">

                <?php

                echo htmlspecialchars(
                    $usuario['tipo_documento'] ?? ''
                );

                ?>

            </td>


            <!-- =========================================
                 DOCUMENTO
            ========================================== -->

            <td data-label="Documento">

                <?php

                echo htmlspecialchars(
                    $usuario['documento'] ?? ''
                );

                ?>

            </td>


            <!-- =========================================
                 TELÉFONO
            ========================================== -->

            <td data-label="Teléfono">

                <?php

                echo htmlspecialchars(
                    $usuario['telefono'] ?? ''
                );

                ?>

            </td>


            <!-- =========================================
                 CORREO
            ========================================== -->

            <td
                data-label="Correo"
                class="usuario-correo"
            >

                <?php

                echo htmlspecialchars(
                    $usuario['correo'] ?? ''
                );

                ?>

            </td>


            <!-- =========================================
                 ROL
            ========================================== -->

            <td data-label="Rol">

                <span class="badge bg-info text-dark">

                    <?php

                    echo htmlspecialchars(
                        $usuario['rol_nombre'] ?? 'Sin rol'
                    );

                    ?>

                </span>

            </td>


            <!-- =========================================
                 ESTADO
            ========================================== -->

            <td
                data-label="Estado"
                class="celda-estado-usuario"
            >

                <div
                    class="form-check form-switch d-flex justify-content-center"
                >


                    <input
                        class="form-check-input"
                        type="checkbox"

                        <?php

                        echo (
                            ($usuario['estado'] ?? '') == 'activo'
                        )

                            ? 'checked'
                            : '';

                        ?>


                        <?php if (tiene_permiso('usuarios')): ?>


                            onclick="cambiarEstado(
                                <?php
                                echo (int)$usuario['id'];
                                ?>
                            )"


                        <?php else: ?>


                            disabled


                        <?php endif; ?>

                    >


                </div>

            </td>


            <!-- =========================================
                 ACCIONES
            ========================================== -->

            <td
                data-label="Acciones"
                class="celda-acciones-usuario"
            >


                <?php if (tiene_permiso('usuarios')): ?>


                    <div class="usuario-botones">


                        <!-- =================================
                             EDITAR
                        ================================== -->

                        <a
                            href="editar_usuario.php?id=<?php echo (int)$usuario['id']; ?>"
                            class="btn btn-success btn-sm"
                        >

                            Editar

                        </a>


                        <!-- =================================
                             ELIMINAR
                        ================================== -->

                        <a
                            href="javascript:void(0)"
                            onclick="confirmarEliminacion(
                                <?php
                                echo (int)$usuario['id'];
                                ?>
                            )"
                            class="btn btn-danger btn-sm"
                        >

                            Eliminar

                        </a>


                    </div>


                <?php else: ?>


                    <span class="badge bg-secondary">

                        Solo lectura

                    </span>


                <?php endif; ?>


            </td>


        </tr>


    <?php endforeach; ?>


    </tbody>


</table>


</div>


<!-- =====================================================
     JAVASCRIPT DEL MÓDULO
====================================================== -->


<script>


/*
=========================================================
CONFIRMAR ELIMINACIÓN
=========================================================

Muestra una ventana de confirmación antes
de eliminar definitivamente un usuario.

=========================================================
*/

function confirmarEliminacion(id) {


    Swal.fire({

        title: "¿Eliminar usuario?",

        text: "Esta acción no se puede deshacer.",

        icon: "warning",

        showCancelButton: true,

        confirmButtonColor: "#d33",

        cancelButtonColor: "#3085d6",

        confirmButtonText: "Sí, eliminar",

        cancelButtonText: "Cancelar"

    })


    .then((result) => {


        if (result.isConfirmed) {


            window.location =
                "eliminar_usuario.php?id=" + id;


        }


    });

}


/*
=========================================================
CAMBIAR ESTADO DEL USUARIO
=========================================================

Realiza una petición AJAX hacia:

    cambiar_estado_usuario.php

Después de realizar el cambio:

    se recarga la página.

=========================================================
*/

function cambiarEstado(id) {


    fetch(
        "cambiar_estado_usuario.php?id=" + id
    )


    .then(response => {


        if (!response.ok) {


            throw new Error(
                "Error en la petición"
            );


        }


        return response.text();


    })


    .then(data => {


        console.log(
            "Respuesta:",
            data
        );


        /*
        -------------------------------------------------
        Recargar para mostrar el nuevo estado.
        -------------------------------------------------
        */

        location.reload();


    })


    .catch(error => {


        console.error(
            "Error:",
            error
        );


        Swal.fire({

            icon: "error",

            title: "Error",

            text:
                "No fue posible cambiar el estado del usuario."

        });


    });

}


</script>


<!-- =====================================================
     CSS RESPONSIVE DEL MÓDULO USUARIOS
======================================================

BREAKPOINT:

    768px

MAYOR DE 768px:

    Tabla tradicional.

MENOR O IGUAL A 768px:

    Tarjetas verticales.

====================================================== -->


<style>


/*
=========================================================
1. BARRA SUPERIOR
=========================================================
*/

.usuarios-toolbar {

    width: 100%;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    margin-bottom: 20px;

}


/*
=========================================================
2. BOTONES DE ACCIONES
=========================================================
*/

.usuarios-acciones {

    display: flex;

    align-items: center;

    gap: 8px;

    flex-wrap: wrap;

}


/*
=========================================================
3. FORMULARIO DE BÚSQUEDA
=========================================================
*/

.usuarios-busqueda {

    display: flex;

    align-items: center;

    gap: 8px;

}


/*
=========================================================
4. CAMPO DE BÚSQUEDA EN PC
=========================================================
*/

.usuarios-input-busqueda {

    width: 300px;

}


/*
=========================================================
5. CONTENEDOR DE TABLA
=========================================================
*/

.tabla-usuarios-contenedor {

    width: 100%;

}


/*
=========================================================
6. NOMBRE DEL USUARIO
=========================================================
*/

.tabla-usuarios .usuario-nombre {

    font-weight: 600;

}


/*
=========================================================
7. CORREO
=========================================================

Permite que correos largos puedan dividirse
correctamente en pantallas pequeñas.

=========================================================
*/

.tabla-usuarios .usuario-correo {

    word-break: break-word;

}


/*
=========================================================
8. BOTONES DE ACCIONES
=========================================================
*/

.usuario-botones {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 6px;

}


/*
=========================================================
9. CELDA DE ESTADO
=========================================================
*/

.tabla-usuarios .celda-estado-usuario {

    min-width: 90px;

}


/*
=========================================================
10. RESPONSIVE CELULAR / APK
=========================================================
*/

@media (max-width: 768px) {


    /*
    =====================================================
    BARRA SUPERIOR
    =====================================================
    */

    .usuarios-toolbar {

        display: flex;

        flex-direction: column;

        align-items: stretch;

        width: 100%;

        gap: 12px;

    }


    /*
    =====================================================
    BOTONES DEL MÓDULO
    =====================================================
    */

    .usuarios-acciones {

        display: flex;

        flex-direction: column;

        align-items: stretch;

        width: 100%;

        gap: 8px;

    }


    /*
    -----------------------------------------------------
    Botones ocupan todo el ancho.
    -----------------------------------------------------
    */

    .usuarios-acciones .btn {

        width: 100%;

        min-height: 44px;

        display: flex;

        align-items: center;

        justify-content: center;

    }


    /*
    =====================================================
    BUSCADOR
    =====================================================
    */

    .usuarios-busqueda {

        display: flex;

        flex-direction: column;

        align-items: stretch;

        width: 100%;

        gap: 8px;

    }


    /*
    =====================================================
    CAMPO DE BÚSQUEDA
    =====================================================
    */

    .usuarios-input-busqueda {

        width: 100% !important;

        min-height: 44px;

        font-size: 16px;

    }


    /*
    =====================================================
    BOTONES DEL BUSCADOR
    =====================================================
    */

    .usuarios-busqueda .btn {

        width: 100%;

        min-height: 44px;

    }


    /*
    =====================================================
    OCULTAR CABECERA DE LA TABLA
    =====================================================
    */

    .tabla-usuarios thead {

        display: none;

    }


    /*
    =====================================================
    CONVERTIR TABLA EN BLOQUES
    =====================================================
    */

    .tabla-usuarios,
    .tabla-usuarios tbody,
    .tabla-usuarios tr,
    .tabla-usuarios td {

        display: block;

        width: 100%;

    }


    /*
    =====================================================
    CADA USUARIO SE CONVIERTE EN UNA TARJETA
    =====================================================
    */

    .tabla-usuarios tbody tr {

        display: block;

        width: 100%;

        margin-bottom: 18px;

        padding: 14px;

        background: #ffffff;

        border: 1px solid #dee2e6;

        border-radius: 12px;

        box-shadow:
            0 2px 8px rgba(0, 0, 0, 0.08);

    }


    /*
    =====================================================
    CELDAS DE LAS TARJETAS
    =====================================================
    */

    .tabla-usuarios tbody td {

        display: flex;

        align-items: flex-start;

        justify-content: space-between;

        width: 100%;

        min-height: 40px;

        padding: 9px 0;

        margin: 0;

        border: none;

        border-bottom: 1px solid #eeeeee;

        text-align: right !important;

        white-space: normal;

        word-break: break-word;

    }


    /*
    =====================================================
    ETIQUETAS AUTOMÁTICAS
    =====================================================

    Utiliza:

        data-label="Documento"

    para mostrar:

        Documento: 123456

    =====================================================
    */

    .tabla-usuarios tbody td::before {

        content: attr(data-label);

        flex: 0 0 42%;

        padding-right: 10px;

        font-weight: 700;

        text-align: left;

    }


    /*
    =====================================================
    ÚLTIMA CELDA
    =====================================================
    */

    .tabla-usuarios tbody td:last-child {

        border-bottom: none;

    }


    /*
    =====================================================
    NOMBRE DEL USUARIO
    =====================================================

    El nombre será el encabezado visual
    de cada tarjeta.

    =====================================================
    */

    .tabla-usuarios tbody td.usuario-nombre {

        display: block;

        padding: 4px 0 14px;

        text-align: left !important;

        font-size: 18px;

        font-weight: 700;

        border-bottom: 2px solid #0A4FA3;

    }


    /*
    -----------------------------------------------------
    Ocultar la etiqueta "Nombre".
    -----------------------------------------------------
    */

    .tabla-usuarios tbody td.usuario-nombre::before {

        display: none;

    }


    /*
    =====================================================
    CORREO EN CELULAR
    =====================================================
    */

    .tabla-usuarios tbody td.usuario-correo {

        overflow-wrap: anywhere;

    }


    /*
    =====================================================
    ESTADO
    =====================================================
    */

    .tabla-usuarios tbody td.celda-estado-usuario {

        align-items: center;

    }


    /*
    -----------------------------------------------------
    Switch hacia la derecha.
    -----------------------------------------------------
    */

    .tabla-usuarios tbody td.celda-estado-usuario
    .form-check {

        margin-left: auto;

        margin-right: 0;

    }


    /*
    =====================================================
    ACCIONES
    =====================================================
    */

    .tabla-usuarios tbody td.celda-acciones-usuario {

        display: block;

        padding-top: 14px;

    }


    /*
    -----------------------------------------------------
    Ocultar etiqueta "Acciones".
    -----------------------------------------------------
    */

    .tabla-usuarios tbody td.celda-acciones-usuario::before {

        display: none;

    }


    /*
    =====================================================
    BOTONES EDITAR / ELIMINAR
    =====================================================
    */

    .usuario-botones {

        display: flex;

        width: 100%;

        gap: 8px;

    }


    .usuario-botones .btn {

        flex: 1;

        min-height: 44px;

        display: flex;

        align-items: center;

        justify-content: center;

    }


    /*
    =====================================================
    SWITCH MÁS CÓMODO PARA TÁCTIL
    =====================================================
    */

    .tabla-usuarios
    .form-check-input {

        width: 2.8em;

        height: 1.5em;

        cursor: pointer;

    }


    /*
    =====================================================
    TAMAÑO DEL TEXTO
    =====================================================
    */

    .tabla-usuarios tbody td {

        font-size: 14px;

    }


    /*
    =====================================================
    EVITAR ANCHO EXCESIVO
    =====================================================
    */

    .tabla-usuarios {

        width: 100% !important;

        max-width: 100% !important;

        table-layout: auto;

    }


    /*
    =====================================================
    CONTENEDOR
    =====================================================
    */

    .tabla-usuarios-contenedor {

        width: 100%;

        max-width: 100%;

        overflow: visible;

    }


}


/*
=========================================================
11. CELULARES MUY PEQUEÑOS
=========================================================

Ejemplo:

    320px
    360px

=========================================================
*/

@media (max-width: 380px) {


    /*
    -----------------------------------------------------
    Tarjeta más compacta.
    -----------------------------------------------------
    */

    .tabla-usuarios tbody tr {

        padding: 11px;

        border-radius: 10px;

    }


    /*
    -----------------------------------------------------
    Texto ligeramente menor.
    -----------------------------------------------------
    */

    .tabla-usuarios tbody td {

        font-size: 13px;

    }


    /*
    -----------------------------------------------------
    Distribución de etiqueta / valor.
    -----------------------------------------------------
    */

    .tabla-usuarios tbody td::before {

        flex-basis: 40%;

    }


    /*
    -----------------------------------------------------
    Nombre.
    -----------------------------------------------------
    */

    .tabla-usuarios tbody td.usuario-nombre {

        font-size: 17px;

    }


    /*
    -----------------------------------------------------
    Botones.
    -----------------------------------------------------
    */

    .usuario-botones .btn {

        font-size: 13px;

        padding-left: 6px;

        padding-right: 6px;

    }


}


/*
=========================================================
12. FIN CSS RESPONSIVE
=========================================================
*/

</style>


<?php

/*
=========================================================
13. FOOTER GENERAL
=========================================================

Todos los módulos utilizan:

    footer_modulos.php

=========================================================
*/

include("../../template/footer_modulos.php");

?>


<?php

/*
=========================================================
14. APERTURA AUTOMÁTICA DEL MODAL CREAR USUARIO
=========================================================

Cuando se accede mediante:

    index.php?crear=1

se abre automáticamente:

    #create

=========================================================
*/

if (
    isset($_GET['crear']) &&
    $_GET['crear'] == '1'
):

?>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /*
        -------------------------------------------------
        Buscar modal de creación.
        -------------------------------------------------
        */

        const modalCrear =
            document.getElementById("create");


        /*
        -------------------------------------------------
        Verificar que exista.
        -------------------------------------------------
        */

        if (modalCrear) {


            /*
            -------------------------------------------------
            Crear instancia Bootstrap.
            -------------------------------------------------
            */

            const modal =
                new bootstrap.Modal(modalCrear);


            /*
            -------------------------------------------------
            Mostrar modal.
            -------------------------------------------------
            */

            modal.show();

        }


    }
);

</script>

<?php endif; ?>

