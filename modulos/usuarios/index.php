<?php  

// ✅ CONEXIÓN
include("../conexion_modulos.php");

// ✅ MODAL CREAR
include("crear_usuario.php");


// ✅ CONSULTAR USUARIOS + ROL
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

$usuarios = $stm->fetchAll(PDO::FETCH_ASSOC);


// ✅ ELIMINAR
if(isset($_GET['id'])){

    $txtid = $_GET['id'];

    $stm = $conexion->prepare("
        DELETE FROM usuario
        WHERE id = :id
    ");

    $stm->bindParam(":id", $txtid);

    $stm->execute();

    echo "
    <script>
        window.location='index.php';
    </script>
    ";

    exit;
}


// ✅ CAMBIAR ESTADO
if(isset($_GET['estado'])){

    $id = $_GET['estado'];

    $stm = $conexion->prepare("
        SELECT estado
        FROM usuario
        WHERE id = :id
    ");

    $stm->execute([
        ":id"=>$id
    ]);

    $usuario = $stm->fetch(PDO::FETCH_ASSOC);

    if($usuario){

        $nuevo_estado = ($usuario['estado'] == 'activo')
        ? 'inactivo'
        : 'activo';

        $update = $conexion->prepare("
            UPDATE usuario
            SET estado = :estado
            WHERE id = :id
        ");

        $update->execute([
            ":estado"=>$nuevo_estado,
            ":id"=>$id
        ]);

    }

    echo "
    <script>
        window.location='index.php';
    </script>
    ";

    exit;
}

?>


<?php include("../../template/header_modulos_Usuarios.php") ?>


<div class="d-flex align-items-center gap-2 mb-3">

    <a 
        href="http://localhost/BFC-dev2/modulos/dashboard/index.php" 
        class="btn btn-outline-dark"
    >
        ← Volver al Dashboard
    </a>

    <button 
        type="button" 
        class="btn btn-primary" 
        data-bs-toggle="modal" 
        data-bs-target="#create"
    >
        Crear Usuario
    </button>

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

        window.location = "index.php?id=" + id;

    }

}


// ✅ CAMBIAR ESTADO
function cambiarEstado(id){

    window.location = "index.php?estado=" + id;

}

</script>


<?php include("../../template/footer_modulos_Usuarios.php") ?>