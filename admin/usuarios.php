<?php include('includes/header.php'); ?>

<div class="col-md-12">
  <div class="card">

    <div class="card-header">
      <h4>
        Usuarios
        <a href="crear_usuarios.php" class="btn bg-gradient-primary float-end">
          Agregar Usuarios
        </a>
      </h4>
    </div>

    <div class="card-body">

      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Tipo de Documento</th>
            <th>Documento</th>
            <th>Número de Telefono</th>
            <th>Correo</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>Nombre</td>
            <td>Tipo de Documento</td>
            <td>Documento</td>
            <td>Número de Telefono</td>
            <td>Correo</td>

            <!-- CHECKBOX para bloquear usuarios -->
            <td class="text-center">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" checked>
              </div>
            </td>

            <td>
              <a href="" class="btn btn-success btn-sm">Editar</a>
              <a href="" class="btn btn-danger btn-sm mx-2">Eliminar</a>
            </td>

          </tr>
        </tbody>
      </table>

    </div>

  </div>
</div>

<?php include('includes/footer.php'); ?>