<?php
include("../../modulos/conexion_modulos.php");
include("../../template/header_modulos.php");
?>

<h3>Importar Deportistas</h3>

<form
    action="procesar_importacion.php"
    method="POST"
    enctype="multipart/form-data"
>

    <div class="mb-3">

        <label class="form-label">
            Seleccione el archivo CSV
        </label>

        <input
            type="file"
            name="archivo"
            class="form-control"
            accept=".csv"
            required
        >

    </div>

    <button class="btn btn-success">

        Importar

    </button>

</form>

<?php
include("../../template/footer_modulos.php");
?>