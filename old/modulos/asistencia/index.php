<?php include("../../includes/header_dashboard.php"); ?>
<?php include("../Dashboard/sidebar.php"); ?>
<?php include("../conexion_modulos.php"); ?>

<<<<<<< HEAD
<link rel="stylesheet" href="../../assets/asistencia.css">

<?php
$categoria_id = $_GET['categoria_id'] ?? "";
$modo = $_GET['modo'] ?? "tomar"; // 🔥 NUEVO
=======
<?php
$categoria_id = $_GET['categoria_id'] ?? "";
$modo = $_GET['modo'] ?? "tomar";
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
?>

<div class="container">

<<<<<<< HEAD
    <h4 class="fw-bold mb-3">Asistencia</h4>

    <!-- 🔥 BOTONES -->
    <div class="mb-3">
        <a href="?modo=tomar&categoria_id=<?php echo $categoria_id; ?>" 
           class="btn btn-success <?php echo ($modo=="tomar") ? "active" : ""; ?>">
            ✍️ Tomar asistencia
        </a>

        <a href="?modo=consultar&categoria_id=<?php echo $categoria_id; ?>" 
           class="btn btn-primary <?php echo ($modo=="consultar") ? "active" : ""; ?>">
            👀 Consultar asistencia
        </a>
    </div>

    <!-- CONTROLES -->
    <div class="d-flex flex-column flex-md-row gap-2 mb-3">
=======
    <h4 class="fw-bold mb-3">📊 Asistencia tipo Excel</h4>

    <!-- CONTROLES -->
    <div class="d-flex gap-2 mb-3">
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62

        <input type="date" id="fecha" class="form-control"
               value="<?php echo date('Y-m-d'); ?>"
               onchange="cargarAsistencia()">

        <form method="GET" class="d-flex gap-2">
<<<<<<< HEAD
            
            <!-- 🔥 MANTENER MODO -->
            <input type="hidden" name="modo" value="<?php echo $modo; ?>">
=======
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62

            <select name="categoria_id" class="form-control" onchange="this.form.submit()">
                <option value="">Todas</option>
                <?php
                $stmt = $conexion->query("SELECT id, nombre FROM categoria");
                while($cat = $stmt->fetch(PDO::FETCH_ASSOC)){
                    $selected = ($categoria_id == $cat['id']) ? "selected" : "";
                    echo "<option value='".$cat['id']."' $selected>".$cat['nombre']."</option>";
                }
                ?>
            </select>

        </form>

    </div>

    <!-- TABLA -->
    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">

            <thead class="table-dark">
                <tr>
                    <th>Deportista</th>
                    <th>✅ Presente</th>
                    <th>❌ Ausente</th>
                    <th>⏰ Tarde</th>
                    <th>🧹</th>
                </tr>
            </thead>

            <tbody>

            <?php
            if($categoria_id != ""){
                $stmt = $conexion->prepare("
                    SELECT id, nombre 
                    FROM deportista 
                    WHERE estado='activo' AND categoria_id = ?
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

<<<<<<< HEAD
                <td data-label="Deportista" class="text-start fw-bold">
                    <?php echo $row['nombre']; ?>
                </td>

                <td data-label="Presente">
                    <input type="radio"
                        name="estado_<?php echo $row['id']; ?>"
                        value="presente"
                        <?php echo ($modo=="consultar") ? "disabled" : ""; ?>
                        onchange="guardar(<?php echo $row['id']; ?>, this.value)">
                </td>

                <td data-label="Ausente">
                    <input type="radio"
                        name="estado_<?php echo $row['id']; ?>"
                        value="ausente"
                        <?php echo ($modo=="consultar") ? "disabled" : ""; ?>
                        onchange="guardar(<?php echo $row['id']; ?>, this.value)">
                </td>

                <td data-label="Tarde">
                    <input type="radio"
                        name="estado_<?php echo $row['id']; ?>"
                        value="tarde"
                        <?php echo ($modo=="consultar") ? "disabled" : ""; ?>
                        onchange="guardar(<?php echo $row['id']; ?>, this.value)">
                </td>

                <td data-label="Acción">
                    <?php if($modo != "consultar"){ ?>
                        <button class="btn btn-sm btn-outline-secondary"
                            onclick="limpiar(<?php echo $row['id']; ?>)">
                            ❌
                        </button>
                    <?php } ?>
=======
                <td class="text-start fw-bold">
                    <?php echo $row['nombre']; ?>
                </td>

                <!-- PRESENTE -->
                <td>
                    <input type="radio"
                        name="estado_<?php echo $row['id']; ?>"
                        value="presente"
                        onchange="guardar(<?php echo $row['id']; ?>, this.value)">
                </td>

                <!-- AUSENTE -->
                <td>
                    <input type="radio"
                        name="estado_<?php echo $row['id']; ?>"
                        value="ausente"
                        onchange="guardar(<?php echo $row['id']; ?>, this.value)">
                </td>

                <!-- TARDE -->
                <td>
                    <input type="radio"
                        name="estado_<?php echo $row['id']; ?>"
                        value="tarde"
                        onchange="guardar(<?php echo $row['id']; ?>, this.value)">
                </td>

                <!-- LIMPIAR -->
                <td>
                    <button class="btn btn-sm btn-outline-secondary"
                        onclick="limpiar(<?php echo $row['id']; ?>)">
                        ❌
                    </button>
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
                </td>

            </tr>

            <?php } ?>

            </tbody>
        </table>
    </div>

</div>

<script>

<<<<<<< HEAD
// 🔥 GUARDAR (bloquear en modo consulta)
function guardar(deportista_id, estado){

    let modo = "<?php echo $modo; ?>";
    if(modo === "consultar") return;

=======
// 🔥 GUARDAR
function guardar(deportista_id, estado){

>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
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


<<<<<<< HEAD
// 🔥 ELIMINAR (bloquear en modo consulta)
function limpiar(deportista_id){

    let modo = "<?php echo $modo; ?>";
    if(modo === "consultar") return;

    let confirmar = confirm("¿Seguro que deseas eliminar la asistencia?");
    if(!confirmar) return;

    let fecha = document.getElementById("fecha").value;

=======
// 🔥 ELIMINAR
function limpiar(deportista_id){

    let fecha = document.getElementById("fecha").value;

    // limpiar UI
    document.querySelectorAll(`input[name="estado_${deportista_id}"]`)
        .forEach(r => r.checked = false);

    // eliminar BD
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
    fetch("eliminar_asistencia.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `deportista_id=${deportista_id}&fecha=${fecha}`
    })
    .then(res => res.json())
    .then(res => {
<<<<<<< HEAD

        if(res.status === "ok"){
            document.querySelectorAll(`input[name="estado_${deportista_id}"]`)
                .forEach(r => r.checked = false);

            alert("Registro eliminado correctamente");
        }
=======
        console.log("ELIMINADO:", res);
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
    });
}


// 🔥 CARGAR DATOS
function cargarAsistencia(){

    let fecha = document.getElementById("fecha").value;

    fetch("obtener_asistencia.php?fecha=" + fecha)
    .then(res => res.json())
    .then(data => {

<<<<<<< HEAD
        document.querySelectorAll("input[type=radio]")
            .forEach(r => r.checked = false);

=======
        // limpiar
        document.querySelectorAll("input[type=radio]")
            .forEach(r => r.checked = false);

        // marcar
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
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
<<<<<<< HEAD
=======

>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
