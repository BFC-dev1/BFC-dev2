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

        fetch("cambiar_estado_usuario.php?id=" + id)
        .then(response => response.text())
        .then(data => {
            console.log(data);
        });

    }

    </script>


    <?php include("../../template/footer_modulos_Usuarios.php") ?>