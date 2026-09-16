<?php
/*
=========================================================
MÓDULO: ASISTENCIA DE ENTRENADORES (ACORDEÓN / MULTITURNO)
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
$es_admin = (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin');
$usuario_logueado_id = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['user_id'] ?? 0;
$nombre_usuario_sesion = $_SESSION['usuario'] ?? $_SESSION['user'] ?? $_SESSION['nombre'] ?? '';

// Obtener la lista de entrenadores según los permisos de sesión
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
?>

<!-- VÍNCULO A ARCHIVO DE ESTILOS ASISTENCIA -->
<link rel="stylesheet" href="<?= $url_base ?>/<?= $css_base ?>/asistencia.css">

<div class="entrenadores-toolbar mb-3">

    <div class="entrenadores-acciones">

        <a href="<?= $url_base ?>/modulos/dashboard/index.php" class="btn btn-outline-dark">
            <i class="fa-solid fa-arrow-left"></i>
            Volver al Dashboard
        </a>

    </div>

    <div class="entrenadores-busqueda">

        <label class="fw-bold me-2 align-self-center mb-0">
            Fecha:
        </label>

        <input
            type="date"
            id="fecha"
            class="form-control entrenadores-input-busqueda"
            value="<?= $fecha_consulta ?>"
            onchange="cargarAsistencia()"
        >

    </div>

</div>

<div id="contenedor_entrenadores">
    <!-- Se genera dinámicamente con JS -->
</div>

<script>
// Imprimimos la variable de entrenadores directamente al inicio del script
const entrenadoresLista = <?= json_encode($entrenadores, JSON_UNESCAPED_UNICODE) ?> || [];

function cargarAsistencia(){
    let fecha = document.getElementById("fecha").value;
    let contenedor = document.getElementById("contenedor_entrenadores");
    contenedor.innerHTML = "";

    if (!entrenadoresLista || entrenadoresLista.length === 0) {
        contenedor.innerHTML = `<div class="alert alert-warning text-center">No se encontraron entrenadores activos para mostrar.</div>`;
        return;
    }

    fetch("obtener_asistencia_entrenador.php?fecha=" + fecha)
    .then(res => res.json())
    .then(data => {
entrenadoresLista.forEach(entrenador => {

    let marcaciones = data.filter(
        m => parseInt(m.usuario_id) === parseInt(entrenador.id)
    );

    // Ordenar del turno más nuevo al más antiguo
    marcaciones.sort((a, b) => {

        const horaA = a.hora_entrada || '00:00:00';
        const horaB = b.hora_entrada || '00:00:00';

        return horaB.localeCompare(horaA);
    });

    contenedor.innerHTML += generarTarjetasEntrenador(
        entrenador,
        marcaciones
    );
});
    })
    .catch(err => {
        console.error("Error al obtener asistencia:", err);
        contenedor.innerHTML = `<div class="alert alert-danger text-center">Error al cargar la información de asistencia.</div>`;
    });
}

function generarTarjetasEntrenador(entrenador, marcaciones){
let totalHorasDia = marcaciones.reduce(
    (acc, m) => acc + (parseFloat(m.horas_trabajadas) || 0),
    0
);

// Buscar el turno actualmente abierto.
// Un turno está abierto cuando tiene entrada pero todavía
// no tiene hora de salida.
let ultimaMarcacion = marcaciones.find(
    m => m.hora_entrada && !m.hora_salida
) || null;

let tieneTurnoAbierto = ultimaMarcacion !== null;

    let estadoBadge = '<span class="badge bg-secondary">Sin Registro</span>';
    if(tieneTurnoAbierto) estadoBadge = '<span class="badge bg-info text-dark">En Turno</span>';
    else if(marcaciones.length > 0) estadoBadge = '<span class="badge bg-success">Turnos Completados</span>';

    let collapseId = `detalles_entrenador_${entrenador.id}`;

    let htmlSubFilas = '';
    if(marcaciones.length === 0){
        htmlSubFilas = `<tr><td colspan="6" class="text-muted py-3">No hay marcaciones para este día. Presiona <b>Marcar Entrada</b> para iniciar jornada.</td></tr>`;
    } else {
        marcaciones.forEach((item, index) => {
            htmlSubFilas += `
            <tr>
                <td class="fw-bold align-middle">Turno ${index + 1}</td>
                <td><input type="time" class="form-control form-control-sm text-center mx-auto" id="entrada_${item.id}" value="${item.hora_entrada || ''}" style="max-width: 130px;"></td>
                <td><input type="time" class="form-control form-control-sm text-center mx-auto" id="salida_${item.id}" value="${item.hora_salida || ''}" style="max-width: 130px;"></td>
                <td class="align-middle"><span class="badge bg-light text-dark border fs-6">${parseFloat(item.horas_trabajadas || 0).toFixed(2)} hrs</span></td>
                <td><input type="text" class="form-control form-control-sm mx-auto" id="obs_${item.id}" value="${item.observaciones || ''}" placeholder="Opcional..." style="max-width: 200px;"></td>
                <td class="align-middle text-center">
                    <div class="btn-acciones-contenedor">
                        <button class="btn btn-primary btn-accion-tabla" title="Guardar Cambios" onclick="guardarFila(${entrenador.id}, ${item.id})">
                            <i class="fa-solid fa-floppy-disk me-1"></i> <span>Guardar</span>
                        </button>
                        <button class="btn btn-danger btn-accion-tabla" title="Eliminar Registro" onclick="eliminarMarcacion(${item.id})">
                            <i class="fa-solid fa-trash me-1"></i> <span>Eliminar</span>
                        </button>
                    </div>
                </td>
            </tr>`;
        });
    }

    return `
    <div class="card mb-3 shadow-sm border">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light btn-sm fw-bold px-3 py-1 text-dark shadow-sm border" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="true">
                    <i class="fa-solid fa-chevron-down me-1 text-primary"></i>
                    <span>Ver / Ocultar Turnos</span>
                </button>
                <span class="fw-bold fs-5"><i class="fa-solid fa-user me-2"></i>${entrenador.nombre}</span>
                ${estadoBadge}
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="fs-6 me-2">Total Día: <b>${totalHorasDia.toFixed(2)} hrs</b></span>
                <button class="btn btn-success btn-sm px-3" onclick="accionRapidaMarcar(${entrenador.id}, ${tieneTurnoAbierto ? ultimaMarcacion.id : 'null'})">
                    <i class="fa-solid fa-clock me-1"></i> ${tieneTurnoAbierto ? 'Marcar Salida' : 'Marcar Entrada'}
                </button>
                <button class="btn btn-outline-light btn-sm px-3" onclick="crearNuevoTurno(${entrenador.id})">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Turno
                </button>
            </div>
        </div>
        <div class="collapse show" id="${collapseId}">
            <div class="card-body p-0">
                <table class="table table-sm table-hover text-center align-middle mb-0 tabla-turnos">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">Turno</th>
                            <th style="width: 18%;">Entrada</th>
                            <th style="width: 18%;">Salida</th>
                            <th style="width: 14%;">Horas</th>
                            <th style="width: 22%;">Observaciones</th>
                            <th style="width: 18%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${htmlSubFilas}
                    </tbody>
                </table>
            </div>
        </div>
    </div>`;
}

function accionRapidaMarcar(usuario_id, idUltimaMarcacion){
    let ahora = new Date().toTimeString().split(' ')[0].substring(0, 5);

    if(idUltimaMarcacion){
        document.getElementById(`salida_${idUltimaMarcacion}`).value = ahora;
        guardarFila(usuario_id, idUltimaMarcacion);
    } else {
        crearNuevoTurno(usuario_id, ahora);
    }
}

function crearNuevoTurno(usuario_id, horaEntrada = ''){
    let fecha = document.getElementById("fecha").value;
    let horaInicio = horaEntrada || new Date().toTimeString().split(' ')[0].substring(0, 5);

    let params = new URLSearchParams({
        usuario_id: usuario_id,
        fecha: fecha,
        hora_entrada: horaInicio,
        hora_salida: '',
        observaciones: ''
    });

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
            Swal.fire('Error', res.mensaje || "Error al crear turno", 'error');
        }
    });
}

function guardarFila(usuario_id, idMarcacion){
    let fecha = document.getElementById("fecha").value;
    let hora_entrada = document.getElementById(`entrada_${idMarcacion}`).value;
    let hora_salida = document.getElementById(`salida_${idMarcacion}`).value;
    let observaciones = document.getElementById(`obs_${idMarcacion}`).value;

    let params = new URLSearchParams({
        id: idMarcacion,
        usuario_id: usuario_id,
        fecha: fecha,
        hora_entrada: hora_entrada,
        hora_salida: hora_salida,
        observaciones: observaciones
    });

    fetch("actualizar_asistencia_entrenador.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
    })
    .then(res => res.json())
    .then(res => {
        if(res.status === "ok"){
            cargarAsistencia();
        } else {
            Swal.fire('Error', res.mensaje || "Error al guardar", 'error');
        }
    });
}

function eliminarMarcacion(id){
    Swal.fire({
        title: "¿Eliminar marcación?",
        text: "Se borrará este turno específico.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("eliminar_asistencia_entrenador.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${id}`
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

document.addEventListener("DOMContentLoaded", cargarAsistencia);
</script>

<?php include("../../template/footer_modulos.php"); ?>