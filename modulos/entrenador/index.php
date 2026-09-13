<?php
/*
=========================================================
MÓDULO: ASISTENCIA DE ENTRENADORES
SISTEMA: BELLAVISTA FC
ARCHIVO: index.php
=========================================================
*/

require_once(__DIR__ . "/../../includes/config.php");
require_once(__DIR__ . "/../../includes/verificar_roles.php");

permitirRoles(['admin', 'entrenador']);

include("../conexion_modulos.php");

$modulo_actual = 'Entrenadores';
$submodulo_actual = 'Asistencia';
$menu_modulo = [];

include("../../template/header_modulos.php");

$fecha_consulta = $_GET['fecha'] ?? date('Y-m-d');

// Identificar si el usuario actual es administrador o entrenador
$es_admin = (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin');

// Capturar de forma inteligente cualquier variable de sesión posible para el usuario
$usuario_logueado_id = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['user_id'] ?? 0;
$nombre_usuario_sesion = $_SESSION['usuario'] ?? $_SESSION['user'] ?? $_SESSION['nombre'] ?? '';
?>

<div class="usuarios-toolbar">
    <div class="usuarios-acciones">
        <a href="<?= $url_base ?>/modulos/dashboard/index.php" class="btn btn-outline-dark">
            <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
        </a>
    </div>

    <div class="usuarios-busqueda">
        <label class="fw-bold me-2 align-self-center mb-0">Fecha:</label>
        <input type="date" id="fecha" class="form-control usuarios-input-busqueda" value="<?= $fecha_consulta ?>" onchange="cargarAsistencia()">
    </div>
</div>

<div class="tabla-usuarios-contenedor">
    <table class="table table-bordered table-hover text-center align-middle tabla-usuarios">
        <thead class="table-dark">
            <tr>
                <th>Entrenador</th>
                <th>Hora Entrada</th>
                <th>Hora Salida</th>
                <th>Total Horas</th>
                <th>Estado</th>
                <th>Observaciones</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Si es admin ve a todos los entrenadores activos; si es entrenador, busca por ID o por nombre de usuario de sesión
        if ($es_admin) {
            $stmt = $conexion->prepare("
                SELECT DISTINCT u.id, u.nombre 
                FROM usuario u
                INNER JOIN rol r ON u.rol_id = r.id
                WHERE LOWER(r.nombre) = 'entrenador' AND u.estado = 'activo'
                ORDER BY u.nombre ASC
            ");
            $stmt->execute();
        } else {
            $stmt = $conexion->prepare("
                SELECT DISTINCT u.id, u.nombre 
                FROM usuario u
                WHERE (u.id = ? OR u.usuario = ? OR u.nombre = ?) AND u.estado = 'activo'
            ");
            $stmt->execute([$usuario_logueado_id, $nombre_usuario_sesion, $nombre_usuario_sesion]);
        }
        
        $entrenadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(empty($entrenadores)):
        ?>
        <tr>
            <td colspan="7" class="text-muted py-4">No se encontró el registro de perfil activo para este usuario (Sesión detectada - ID: <?= $usuario_logueado_id ?>, Usuario: <?= htmlspecialchars($nombre_usuario_sesion) ?>).</td>
        </tr>
        <?php
        else:
            foreach($entrenadores as $row):
                $id = $row['id'];
        ?>
        <tr id="fila_<?= $id ?>">
            <td data-label="Entrenador" class="usuario-nombre">
                <i class="fa-solid fa-user me-2 text-secondary"></i>
                <?= htmlspecialchars($row['nombre']) ?>
            </td>

            <td data-label="Hora Entrada">
                <input type="time" class="form-control text-center mx-auto" id="entrada_<?= $id ?>" style="max-width: 140px;">
            </td>

            <td data-label="Hora Salida">
                <input type="time" class="form-control text-center mx-auto" id="salida_<?= $id ?>" style="max-width: 140px;">
            </td>

            <td data-label="Total Horas">
                <span id="total_<?= $id ?>" class="badge bg-light text-dark fs-6 border">0.00 hrs</span>
            </td>

            <td data-label="Estado" class="celda-estado-usuario">
                <span id="badge_estado_<?= $id ?>" class="badge bg-secondary">Sin Registro</span>
            </td>

            <td data-label="Observaciones">
                <input type="text" class="form-control form-control-sm mx-auto" id="obs_<?= $id ?>" placeholder="Opcional..." style="max-width: 200px;">
            </td>

            <td data-label="Acciones" class="celda-acciones-usuario">
                <div class="usuario-botones">
                    <button class="btn btn-success btn-sm" title="Marcar Hora Actual" onclick="marcarAhora(<?= $id ?>)">
                        <i class="fa-solid fa-clock"></i> Marcar
                    </button>
                    <button class="btn btn-warning btn-sm text-white" title="Marcar Ausente" onclick="marcarAusente(<?= $id ?>)">
                        <i class="fa-solid fa-user-slash"></i> Ausente
                    </button>
                    <button class="btn btn-primary btn-sm" title="Guardar Cambios" onclick="guardar(<?= $id ?>)">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                    <button class="btn btn-danger btn-sm" title="Eliminar Registro" onclick="limpiar(<?= $id ?>)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php 
            endforeach;
        endif; 
        ?>
        </tbody>
    </table>
</div>

<script>
function cargarAsistencia(){
    let fecha = document.getElementById("fecha").value;

    fetch("obtener_asistencia_entrenador.php?fecha=" + fecha)
    .then(res => res.json())
    .then(data => {
        document.querySelectorAll("input[type=time]").forEach(i => i.value = "");
        document.querySelectorAll("[id^='obs_']").forEach(i => i.value = "");
        document.querySelectorAll("[id^='total_']").forEach(i => i.textContent = "0.00 hrs");
        document.querySelectorAll("[id^='badge_estado_']").forEach(i => {
            i.className = "badge bg-secondary";
            i.textContent = "Sin Registro";
        });

        data.forEach(item => {
            let id = item.usuario_id;
            let inputEntrada = document.getElementById(`entrada_${id}`);
            let inputSalida = document.getElementById(`salida_${id}`);
            let inputObs = document.getElementById(`obs_${id}`);
            let spanTotal = document.getElementById(`total_${id}`);
            let badgeEstado = document.getElementById(`badge_estado_${id}`);

            if(inputEntrada && item.hora_entrada) inputEntrada.value = item.hora_entrada;
            if(inputSalida && item.hora_salida) inputSalida.value = item.hora_salida;
            if(inputObs && item.observaciones) inputObs.value = item.observaciones;
            if(spanTotal && item.horas_trabajadas) spanTotal.textContent = parseFloat(item.horas_trabajadas).toFixed(2) + " hrs";

            if(badgeEstado){
                if(item.estado === 'abierta'){
                    badgeEstado.className = "badge bg-info text-dark";
                    badgeEstado.textContent = "Abierta";
                } else if(item.estado === 'cerrada'){
                    badgeEstado.className = "badge bg-success";
                    badgeEstado.textContent = "Cerrada";
                } else if(item.estado === 'ausente'){
                    badgeEstado.className = "badge bg-danger";
                    badgeEstado.textContent = "Ausente";
                }
            }
        });
    });
}

function marcarAhora(usuario_id){
    let horaEntrada = document.getElementById(`entrada_${usuario_id}`);
    let horaSalida = document.getElementById(`salida_${usuario_id}`);
    let ahora = new Date().toTimeString().split(' ')[0].substring(0, 5);

    if(!horaEntrada.value){
        horaEntrada.value = ahora;
    } else if(!horaSalida.value){
        horaSalida.value = ahora;
    } else {
        Swal.fire('Atención', 'Ya se registraron la entrada y salida para esta fecha.', 'info');
        return;
    }
    guardar(usuario_id);
}

function marcarAusente(usuario_id){
    document.getElementById(`entrada_${usuario_id}`).value = "";
    document.getElementById(`salida_${usuario_id}`).value = "";
    guardar(usuario_id, 'ausente');
}

function guardar(usuario_id, estadoForzado = null){
    let fecha = document.getElementById("fecha").value;
    let hora_entrada = document.getElementById(`entrada_${usuario_id}`).value;
    let hora_salida = document.getElementById(`salida_${usuario_id}`).value;
    let observaciones = document.getElementById(`obs_${usuario_id}`).value;

    let params = new URLSearchParams({
        usuario_id: usuario_id,
        fecha: fecha,
        hora_entrada: hora_entrada,
        hora_salida: hora_salida,
        observaciones: observaciones
    });

    if(estadoForzado) params.append('estado', estadoForzado);

    fetch("guardar_asistencia_entrenador.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
    })
    .then(res => res.json())
    .then(res => {
        if(res.status === "ok"){
            cargarAsistencia();
        } else {
            Swal.fire('Error', res.mensaje || "Error al guardar el registro", 'error');
        }
    });
}

function limpiar(usuario_id){
    Swal.fire({
        title: "¿Eliminar registro?",
        text: "Esta acción borrará la asistencia seleccionada.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            let fecha = document.getElementById("fecha").value;

            fetch("eliminar_asistencia_entrenador.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `usuario_id=${usuario_id}&fecha=${fecha}`
            })
            .then(res => res.json())
            .then(res => {
                if(res.status === "ok"){
                    cargarAsistencia();
                } else {
                    Swal.fire('Error', res.mensaje || "Error al eliminar", 'error');
                }
            });
        }
    });
}

window.onload = cargarAsistencia;
</script>

<style>
.usuarios-toolbar { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 15px; margin-bottom: 20px; }
.usuarios-acciones { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.usuarios-busqueda { display: flex; align-items: center; gap: 8px; }
.usuarios-input-busqueda { width: 220px; }
.tabla-usuarios-contenedor { width: 100%; }
.tabla-usuarios .usuario-nombre { font-weight: 600; }
.usuario-botones { display: flex; justify-content: center; align-items: center; gap: 4px; flex-wrap: wrap; }

@media (max-width: 768px) {
    .usuarios-toolbar { flex-direction: column; align-items: stretch; }
    .usuarios-acciones, .usuarios-busqueda { flex-direction: column; align-items: stretch; width: 100%; }
    .usuarios-input-busqueda { width: 100% !important; min-height: 44px; }
    .tabla-usuarios thead { display: none; }
    .tabla-usuarios, .tabla-usuarios tbody, .tabla-usuarios tr, .tabla-usuarios td { display: block; width: 100%; }
    .tabla-usuarios tbody tr { margin-bottom: 18px; padding: 14px; background: #fff; border: 1px solid #dee2e6; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .tabla-usuarios tbody td { display: flex; align-items: center; justify-content: space-between; padding: 9px 0; border: none; border-bottom: 1px solid #eee; text-align: right !important; }
    .tabla-usuarios tbody td::before { content: attr(data-label); font-weight: 700; text-align: left; flex: 0 0 40%; }
    .tabla-usuarios tbody td.usuario-nombre { display: block; text-align: left !important; font-size: 18px; border-bottom: 2px solid #0A4FA3; }
    .tabla-usuarios tbody td.usuario-nombre::before { display: none; }
    .tabla-usuarios tbody td.celda-acciones-usuario::before { display: none; }
    .usuario-botones { width: 100%; flex-direction: column; gap: 6px; }
    .usuario-botones .btn { width: 100%; min-height: 40px; }
}
</style>

<?php include("../../template/footer_modulos.php"); ?>