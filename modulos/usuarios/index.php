<?php

/*
=========================================================
MÓDULO: USUARIOS
SISTEMA: BELLAVISTA FC
ARCHIVO: index.php
=========================================================

RESPONSABILIDADES DE ESTE ARCHIVO:

- Verificar permisos de acceso.
- Cargar la configuración general.
- Cargar la conexión a la base de datos.
- Definir la identidad del módulo.
- Cargar el header general de módulos.
- Cargar el formulario/modal de creación.
- Consultar usuarios.
- Permitir búsqueda.
- Mostrar usuarios.
- Controlar estado activo/inactivo.
- Permitir editar y eliminar usuarios.
- Mostrar mensajes de operación.
- Cargar el footer general.

=========================================================
*/


/*
=========================================================
1. CONFIGURACIÓN GENERAL DEL SISTEMA
=========================================================

IMPORTANTE:

Debe cargarse ANTES de:

    header_modulos.php

porque el header utiliza variables como:

    $url_base
    $css_url
    $img_url

=========================================================
*/

require_once(__DIR__ . "/../../includes/config.php");


/*
=========================================================
2. VERIFICAR SESIÓN Y PERMISOS
=========================================================

Se conserva el sistema actual de permisos de Usuarios.

El módulo Usuarios actualmente está restringido
al rol "admin".

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

Se utiliza la conexión modular existente.

Este archivo establece la variable:

    $conexion

=========================================================
*/

include("../conexion_modulos.php");


/*
=========================================================
4. IDENTIDAD DEL MÓDULO
=========================================================

Estas variables son utilizadas por el framework
general de módulos.

Todos los módulos pueden utilizar la misma estructura:

    Deportistas
    Usuarios
    Financiero
    Auditoría
    etc.

=========================================================
*/

$modulo_actual = 'Usuarios';

$submodulo_actual = '';

$menu_modulo = [];


/*
=========================================================
5. CARGAR HEADER GENERAL DE MÓDULOS
=========================================================

IMPORTANTE:

ANTES:

    header_modulos_Usuarios.php

AHORA:

    header_modulos.php

Todos los módulos utilizan el mismo framework.

=========================================================
*/

include("../../template/header_modulos.php");


/*
=========================================================
6. MODAL / FORMULARIO DE CREAR USUARIO
=========================================================

IMPORTANTE:

Este include se coloca DESPUÉS del header.

De esta manera crear_usuario.php no imprime HTML
antes de que se haya construido la estructura
principal de la página.

=========================================================
*/

include("crear_usuario.php");


/*
=========================================================
7. BUSCADOR
=========================================================

Permite buscar usuarios por:

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
    -----------------------------------------------------
    BÚSQUEDA CON FILTRO
    -----------------------------------------------------
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
    -----------------------------------------------------
    CONSULTA GENERAL
    -----------------------------------------------------
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

Si la URL contiene:

    ?eliminado=1

se muestra un mensaje de confirmación.

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

Si la URL contiene:

    ?actualizado=1

se muestra un mensaje de confirmación.

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
12. BOTONES SUPERIORES Y BUSCADOR
=========================================================
*/

?>

<div class="d-flex justify-content-between align-items-center mb-3">


    <!-- =================================================
         BOTONES DEL MÓDULO
         ================================================= -->

    <div class="d-flex gap-2">

        <!-- VOLVER AL DASHBOARD -->

        <a
            href="<?= $url_base ?>/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Volver al Dashboard
        </a>


        <!-- =================================================
             CREAR USUARIO
             
             Solo se muestra si el usuario tiene permiso
             sobre el módulo Usuarios.
             ================================================= -->

        <?php if (tiene_permiso('usuarios')): ?>

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
         ================================================= -->

    <form method="GET" class="d-flex gap-2">

        <input
            type="text"
            name="buscar"
            class="form-control"
            style="width:300px;"
            placeholder="Buscar usuario, documento..."
            value="<?php echo htmlspecialchars($buscar); ?>"
        >

        <button
            type="submit"
            class="btn btn-primary"
        >
            Buscar
        </button>

        <a
            href="index.php"
            class="btn btn-secondary"
        >
            Limpiar
        </a>

    </form>

</div>


<!-- =====================================================
     TABLA DE USUARIOS
     ===================================================== -->

<table class="table table-bordered table-hover text-center align-middle">


    <!-- ENCABEZADO -->

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


    <!-- CUERPO -->

    <tbody>

    <?php foreach ($usuarios as $usuario): ?>

        <tr>


            <!-- =================================================
                 NOMBRE
                 ================================================= -->

            <td>
                <?php
                echo htmlspecialchars(
                    $usuario['nombre'] ?? ''
                );
                ?>
            </td>


            <!-- =================================================
                 TIPO DOCUMENTO
                 ================================================= -->

            <td>
                <?php
                echo htmlspecialchars(
                    $usuario['tipo_documento'] ?? ''
                );
                ?>
            </td>


            <!-- =================================================
                 DOCUMENTO
                 ================================================= -->

            <td>
                <?php
                echo htmlspecialchars(
                    $usuario['documento'] ?? ''
                );
                ?>
            </td>


            <!-- =================================================
                 TELÉFONO
                 ================================================= -->

            <td>
                <?php
                echo htmlspecialchars(
                    $usuario['telefono'] ?? ''
                );
                ?>
            </td>


            <!-- =================================================
                 CORREO
                 ================================================= -->

            <td>
                <?php
                echo htmlspecialchars(
                    $usuario['correo'] ?? ''
                );
                ?>
            </td>


            <!-- =================================================
                 ROL
                 ================================================= -->

            <td>

                <span class="badge bg-info text-dark">

                    <?php
                    echo htmlspecialchars(
                        $usuario['rol_nombre'] ?? 'Sin rol'
                    );
                    ?>

                </span>

            </td>


            <!-- =================================================
                 ESTADO
                 ================================================= -->

            <td>

                <div class="form-check form-switch d-flex justify-content-center">

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
                                <?php echo (int)$usuario['id']; ?>
                            )"

                        <?php else: ?>

                            disabled

                        <?php endif; ?>

                    >

                </div>

            </td>


            <!-- =================================================
                 ACCIONES
                 ================================================= -->

            <td>

                <?php if (tiene_permiso('usuarios')): ?>

                    <div class="d-flex justify-content-center gap-2">


                        <!-- EDITAR -->

                        <a
                            href="editar_usuario.php?id=<?php echo (int)$usuario['id']; ?>"
                            class="btn btn-success btn-sm"
                        >
                            Editar
                        </a>


                        <!-- ELIMINAR -->

                        <a
                            href="javascript:void(0)"
                            onclick="confirmarEliminacion(
                                <?php echo (int)$usuario['id']; ?>
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


<!-- =====================================================
     JAVASCRIPT DEL MÓDULO
     ===================================================== -->

<script>


/*
=========================================================
CONFIRMAR ELIMINACIÓN
=========================================================

Muestra una confirmación antes de eliminar un usuario.

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

    }).then((result) => {

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

Realiza una petición AJAX al archivo:

    cambiar_estado_usuario.php

Después de cambiar el estado se recarga la página.

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

        console.log(data);

        /*
        -------------------------------------------------
        Recargar tabla para mostrar el nuevo estado.
        -------------------------------------------------
        */

        location.reload();

    })

    .catch(error => {

        console.error(error);

        Swal.fire({

            icon: "error",

            title: "Error",

            text: "No fue posible cambiar el estado del usuario."

        });

    });

}

</script>


<?php

/*
=========================================================
FOOTER GENERAL DE MÓDULOS
=========================================================

ANTES:

    footer_modulos_Usuarios.php

AHORA:

    footer_modulos.php

Todos los módulos utilizan el mismo footer.

=========================================================
*/

include("../../template/footer_modulos.php");

?>

<?php if (isset($_GET['crear']) && $_GET['crear'] == '1'): ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const modalCrear = document.getElementById("create");

    if (modalCrear) {

        const modal = new bootstrap.Modal(modalCrear);

        modal.show();

    }

});
</script>

<?php endif; ?>