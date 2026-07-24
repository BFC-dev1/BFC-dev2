<?php include("../../includes/header_dashboard.php"); ?>
<?php include("../Dashboard/sidebar.php"); ?>
<?php include("../conexion_modulos.php"); ?>

<link rel="stylesheet" href="../../assets/asistencia.css">

<?php
$categoria_id = $_GET['categoria_id'] ?? "";
$modo = $_GET['modo'] ?? "tomar";
?>

<div class="container py-4">
    
    <!-- BOTON VOLVER -->
    <div class="d-flex align-items-center mb-3">

        <a
            href="http://localhost/BFC-dev2/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >
            <i class="fa-solid fa-arrow-left me-2"></i>
            Volver al Dashboard
        </a>

    </div>

    <!-- TITULO -->
    <div class="mb-4">

        <h3 class="fw-bold mb-1">
            <i class="fa-solid fa-calendar-check me-2 text-success"></i>
            Gestión de Asistencia
        </h3>

        <p class="text-muted">
            Control y seguimiento de asistencia de deportistas.
        </p>

    </div>

    <!-- BOTONES -->
    <div class="d-flex gap-2 mb-4 flex-wrap">

        <a href="?modo=tomar&categoria_id=<?php echo $categoria_id; ?>" 
           class="btn btn-success rounded-pill px-4 <?php echo ($modo=="tomar") ? "active" : ""; ?>">

            <i class="fa-solid fa-pen me-2"></i>
            Tomar asistencia

        </a>

        <a href="?modo=consultar&categoria_id=<?php echo $categoria_id; ?>" 
           class="btn btn-primary rounded-pill px-4 <?php echo ($modo=="consultar") ? "active" : ""; ?>">

            <i class="fa-solid fa-eye me-2"></i>
            Consultar asistencia

        </a>

    </div>

    <!-- FILTROS -->
    <div class="card border-0 rounded-4 shadow-sm p-4 mb-4">

        <div class="row g-3">

            <!-- FECHA -->
            <div class="col-md-4">

                <label class="fw-semibold mb-2">
                    <i class="fa-solid fa-calendar-day me-2 text-primary"></i>
                    Fecha
                </label>

                <input 
                    type="date" 
                    id="fecha" 
                    class="form-control rounded-3"
                    value="<?php echo date('Y-m-d'); ?>"
                    onchange="cargarAsistencia()"
                >

            </div>

            <!-- CATEGORIA -->
            <div class="col-md-4">

                <form method="GET">

                    <input type="hidden" name="modo" value="<?php echo $modo; ?>">

                    <label class="fw-semibold mb-2">
                        <i class="fa-solid fa-layer-group me-2 text-warning"></i>
                        Categoría
                    </label>

                    <select 
                        name="categoria_id" 
                        class="form-control rounded-3"
                        onchange="this.form.submit()"
                    >

                        <option value="">Todas</option>

                        <?php
                        $stmt = $conexion->query("SELECT id, nombre FROM categoria");

                        while($cat = $stmt->fetch(PDO::FETCH_ASSOC)){

                            $selected = ($categoria_id == $cat['id']) ? "selected" : "";

                            echo "
                                <option value='".$cat['id']."' $selected>
                                    ".$cat['nombre']."
                                </option>
                            ";
                        }
                        ?>

                    </select>

                </form>

            </div>

        </div>

    </div>

    <!-- TABLA -->
    <div class="card border-0 rounded-4 shadow-sm overflow-hidden">

        <div class="table-responsive">

            <table class="table table-hover text-center align-middle mb-0">

                <thead class="table-dark">

                    <tr>

                        <th>
                            <i class="fa-solid fa-user me-2"></i>
                            Deportista
                        </th>

                        <th>
                            <i class="fa-solid fa-circle-check me-2 text-success"></i>
                            Presente
                        </th>

                        <th>
                            <i class="fa-solid fa-circle-xmark me-2 text-danger"></i>
                            Ausente
                        </th>

                        <th>
                            <i class="fa-solid fa-clock me-2 text-warning"></i>
                            Tarde
                        </th>

                        <th>
                            <i class="fa-solid fa-trash me-2"></i>
                            Acción
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php

                if($categoria_id != ""){

                    $stmt = $conexion->prepare("
                        SELECT id, nombre 
                        FROM deportista 
                        WHERE estado='activo' 
                        AND categoria_id = ?
                    ");

                    $stmt->execute([$categoria_id]);

                } else {

                    $stmt = $conexion->query("
                        SELECT id, nombre 
                        FROM deportista 
                        WHERE estado='activo'
                    ");
                }

                $deportistas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($deportistas as $row){
                ?>

                <tr>

                    <td data-label="Deportista" class="text-start fw-semibold">

                        <i class="fa-solid fa-user me-2 text-secondary"></i>

                        <?php echo $row['nombre']; ?>

                    </td>

                    <!-- PRESENTE -->
                    <td data-label="Presente">

                        <input type="radio"
                            class="form-check-input"
                            name="estado_<?php echo $row['id']; ?>"
                            value="presente"
                            <?php echo ($modo=="consultar") ? "disabled" : ""; ?>
                            onchange="guardar(<?php echo $row['id']; ?>, this.value)"
                        >

                    </td>

                    <!-- AUSENTE -->
                    <td data-label="Ausente">

                        <input type="radio"
                            class="form-check-input"
                            name="estado_<?php echo $row['id']; ?>"
                            value="ausente"
                            <?php echo ($modo=="consultar") ? "disabled" : ""; ?>
                            onchange="guardar(<?php echo $row['id']; ?>, this.value)"
                        >

                    </td>

                    <!-- TARDE -->
                    <td data-label="Tarde">

                        <input type="radio"
                            class="form-check-input"
                            name="estado_<?php echo $row['id']; ?>"
                            value="tarde"
                            <?php echo ($modo=="consultar") ? "disabled" : ""; ?>
                            onchange="guardar(<?php echo $row['id']; ?>, this.value)"
                        >

                    </td>

                    <!-- ACCION -->
                    <td data-label="Acción">

                        <?php if($modo != "consultar"){ ?>

                            <button 
                                class="btn btn-sm btn-outline-danger rounded-circle"
                                onclick="limpiar(<?php echo $row['id']; ?>)"
                            >

                                <i class="fa-solid fa-trash"></i>

                            </button>

                        <?php } ?>

                    </td>

                </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>

// GUARDAR
function guardar(deportista_id, estado){

    let modo = "<?php echo $modo; ?>";

    if(modo === "consultar") return;

    let fecha = document.getElementById("fecha").value;

    fetch("guardar_asistencia.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `deportista_id=${deportista_id}&estado=${estado}&fecha=${fecha}`
    })
    .then(res => res.json())
    .then(res => {

        console.log("GUARDADO:", res);

        if(res.status !== "ok"){
            alert(res.mensaje);
        }

    });

}


// ELIMINAR
function limpiar(deportista_id){

    let modo = "<?php echo $modo; ?>";

    if(modo === "consultar") return;

    let confirmar = confirm("¿Seguro que deseas eliminar la asistencia?");

    if(!confirmar) return;

    let fecha = document.getElementById("fecha").value;

    fetch("eliminar_asistencia.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `deportista_id=${deportista_id}&fecha=${fecha}`
    })
    .then(res => res.json())
    .then(res => {

        if(res.status === "ok"){

            document.querySelectorAll(`input[name="estado_${deportista_id}"]`)
                .forEach(r => r.checked = false);

            alert("Registro eliminado correctamente");
        }

    });

}


// CARGAR DATOS
function cargarAsistencia(){

    let fecha = document.getElementById("fecha").value;

    fetch("obtener_asistencia.php?fecha=" + fecha)

    .then(res => res.json())

    .then(data => {

        document.querySelectorAll("input[type=radio]")
            .forEach(r => r.checked = false);

        data.forEach(item => {

            let input = document.querySelector(
                `input[name="estado_${item.deportista_id}"][value="${item.estado}"]`
            );

            if(input){
                input.checked = true;
            }

        });

    });

}

window.onload = cargarAsistencia;

</script>

<?php include("../../includes/footer_dashboard.php"); ?>