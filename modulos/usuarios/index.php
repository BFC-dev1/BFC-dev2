<?php


/*
=================================================
VERIFICAR PERMISOS DEL MÓDULO USUARIOS

Este archivo controla el acceso a la administración
de usuarios.

Permite:

- Crear usuarios.
- Editar usuarios.
- Eliminar usuarios.
- Cambiar estados.

Solamente el rol "admin" puede ingresar.

Si otro rol intenta entrar:
será enviado al Dashboard.
=================================================
*/


require_once("../../includes/verificar_roles.php");



permitirRoles([

    "admin"

]);



/*
=================================================
CONEXIÓN DEL MÓDULO

La conexión se carga después de validar
los permisos.

Así evitamos ejecutar consultas antes de
comprobar el acceso.
=================================================
*/

include("../conexion_modulos.php");



/*
=================================================
MODAL CREAR USUARIO

Se carga después del control de permisos.
=================================================
*/

include("crear_usuario.php");



// ✅ BUSCADOR
$buscar = trim($_GET["buscar"] ?? "");

if($buscar != ""){

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
        ":buscar" => "%".$buscar."%"
    ]);

}else{

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

$usuarios = $stm->fetchAll(PDO::FETCH_ASSOC);


    ?>


    <?php include("../../template/header_modulos_Usuarios.php") ?>

    <?php

/*
=================================================
MENSAJE DE ELIMINACIÓN EXITOSA

Si la URL contiene el parámetro "eliminado",
se muestra un mensaje indicando que el usuario
fue eliminado correctamente.

=================================================
*/

if(isset($_GET["eliminado"])){

?>

<script>

Swal.fire({

    icon: "success",

    title: "Usuario eliminado",

    text: "El usuario fue eliminado correctamente.",

    confirmButtonText: "Aceptar"

});

</script>

<?php } ?>

<?php if(isset($_GET['actualizado'])){ ?>

    

<script>

Swal.fire({
    icon: "success",
    title: "Operación Exitosa",
    text: "Los datos fueron actualizados correctamente.",
    confirmButtonText: "Aceptar"
});

</script>

<?php } ?>


<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="d-flex gap-2">

<!-- BOTÓN VOLVER AL DASHBOARD -->
<div class="mb-3">

    <a
        href="<?= $url_base ?>/modulos/dashboard/index.php"
        class="btn btn-outline-dark"
    >
        <i class="fa-solid fa-arrow-left"></i>
        Volver al Dashboard
    </a>

</div>

        <button
            type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#create"
        >
            Crear Usuario
        </button>

    </div>

    <form method="GET" class="d-flex gap-2">

        <input
            type="text"
            name="buscar"
            class="form-control"
            style="width:300px;"
            placeholder="Buscar usuario, documento..."
            value="<?php echo htmlspecialchars($buscar); ?>"
        >

        <button class="btn btn-primary">
            Buscar
        </button>

        <a href="index.php" class="btn btn-secondary">
            Limpiar
        </a>

    </form>

</div>


    <table class="table table-bordered table-hover text-center align-middle">

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

        <tbody>

        <?php foreach($usuarios as $usuario) { ?>

            <tr>

                <td><?php echo $usuario['nombre']; ?></td>

                <td><?php echo $usuario['tipo_documento']; ?></td>

                <td><?php echo $usuario['documento']; ?></td>

                <td><?php echo $usuario['telefono']; ?></td>

                <td><?php echo $usuario['correo']; ?></td>

                <!-- ✅ ROL -->
                <td>

                    <span class="badge bg-info text-dark">

                        <?php echo $usuario['rol_nombre']; ?>

                    </span>

                </td>


                <!-- ✅ ESTADO -->
                <td>

                    <div class="form-check form-switch d-flex justify-content-center">

                        <input 
                            class="form-check-input"
                            type="checkbox"

                            <?php 
                            if($usuario['estado'] == 'activo'){
                                echo 'checked';
                            }
                            ?>

                            onclick="cambiarEstado(<?php echo $usuario['id']; ?>)"
                        >

                    </div>

                </td>


                <!-- ✅ ACCIONES -->
                <td>

                    <div class="d-flex justify-content-center gap-2">

                        <a 
                            href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" 
                            class="btn btn-success btn-sm"
                        >
                            Editar
                        </a>

                        <a 
                            href="javascript:void(0)"
                            onclick="confirmarEliminacion(<?php echo $usuario['id']; ?>)"
                            class="btn btn-danger btn-sm"
                        >
                            Eliminar
                        </a>

                    </div>

                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>


    <!-- ✅ SCRIPT -->

    <script>

    function confirmarEliminacion(id){

        let confirmar = confirm("¿Seguro que deseas eliminar este usuario?");

        if(confirmar){

            window.location = "eliminar_usuario.php?id=" + id;

        }

    }


    // ✅ CAMBIAR ESTADO
function cambiarEstado(id){

    fetch("cambiar_estado_usuario.php?id=" + id)
    .then(response => {

        if(!response.ok){
            throw new Error("Error en la petición");
        }

        return response.text();

    })
    .then(data => {

        console.log(data);

        // Recargar tabla para mostrar nuevo estado
        location.reload();

    })
    .catch(error => {

        console.error(error);

    });

}

    </script>


    <?php include("../../template/footer_modulos_Usuarios.php") ?>