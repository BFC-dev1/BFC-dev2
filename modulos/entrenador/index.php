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

$es_admin = (
    isset($_SESSION['rol']) &&
    strtolower($_SESSION['rol']) === 'admin'
);

$usuario_logueado_id =
    $_SESSION['usuario_id']
    ?? $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? 0;

$nombre_usuario_sesion =
    $_SESSION['usuario']
    ?? $_SESSION['user']
    ?? $_SESSION['nombre']
    ?? '';


// =========================================================
// OBTENER ENTRENADORES SEGÚN LOS PERMISOS
// =========================================================

if ($es_admin) {

    $stmt = $conexion->prepare("
        SELECT DISTINCT
            u.id,
            u.nombre
        FROM usuario u
        INNER JOIN rol r
            ON u.rol_id = r.id
        WHERE LOWER(r.nombre) = 'entrenador'
          AND u.estado = 'activo'
        ORDER BY u.nombre ASC
    ");

    $stmt->execute();

} else {

    $stmt = $conexion->prepare("
        SELECT DISTINCT
            u.id,
            u.nombre
        FROM usuario u
        WHERE (
            u.id = ?
            OR u.usuario = ?
            OR u.nombre = ?
        )
        AND u.estado = 'activo'
    ");

    $stmt->execute([
        $usuario_logueado_id,
        $nombre_usuario_sesion,
        $nombre_usuario_sesion
    ]);
}

$entrenadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- =========================================================
     CSS DEL MÓDULO
     ========================================================= -->

<link
    rel="stylesheet"
    href="<?= $url_base ?>/<?= $css_base ?>/asistencia.css"
>


<!-- =========================================================
     BARRA SUPERIOR
     ========================================================= -->

<div class="entrenadores-toolbar mb-3">

    <div class="entrenadores-acciones">

        <a
            href="<?= $url_base ?>/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >
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
            value="<?= htmlspecialchars($fecha_consulta) ?>"
            onchange="cargarAsistencia()"
        >

    </div>

</div>


<!-- =========================================================
     CONTENEDOR DE ENTRENADORES
     ========================================================= -->

<div id="contenedor_entrenadores">
    <!-- Se genera dinámicamente con JavaScript -->
</div>


<script>

// =========================================================
// LISTA DE ENTRENADORES
// =========================================================

const entrenadoresLista =
    <?= json_encode(
        $entrenadores,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>
    || [];


// =========================================================
// CARGAR ASISTENCIA
// =========================================================

function cargarAsistencia() {

    const fecha =
        document.getElementById("fecha").value;

    const contenedor =
        document.getElementById("contenedor_entrenadores");

    contenedor.innerHTML = "";


    // -----------------------------------------------------
    // SIN ENTRENADORES
    // -----------------------------------------------------

    if (
        !entrenadoresLista ||
        entrenadoresLista.length === 0
    ) {

        contenedor.innerHTML = `
            <div class="alert alert-warning text-center">
                No se encontraron entrenadores activos para mostrar.
            </div>
        `;

        return;
    }


    // -----------------------------------------------------
    // CONSULTAR ASISTENCIA
    // -----------------------------------------------------

    fetch(
        "obtener_asistencia_entrenador.php?fecha="
        + encodeURIComponent(fecha)
    )

    .then(res => res.json())

    .then(data => {

        entrenadoresLista.forEach(entrenador => {

            let marcaciones = data.filter(
                m =>
                    parseInt(m.usuario_id) ===
                    parseInt(entrenador.id)
            );


            // -------------------------------------------------
            // ORDENAR TURNOS
            // MÁS NUEVO → MÁS ANTIGUO
            // -------------------------------------------------

            marcaciones.sort((a, b) => {

                const horaA =
                    a.hora_entrada || '00:00:00';

                const horaB =
                    b.hora_entrada || '00:00:00';

                return horaB.localeCompare(horaA);
            });


            // -------------------------------------------------
            // GENERAR TARJETA
            // -------------------------------------------------

            contenedor.innerHTML +=
                generarTarjetasEntrenador(
                    entrenador,
                    marcaciones
                );

        });

    })

    .catch(err => {

        console.error(
            "Error al obtener asistencia:",
            err
        );

        contenedor.innerHTML = `
            <div class="alert alert-danger text-center">
                Error al cargar la información de asistencia.
            </div>
        `;

    });
}


// =========================================================
// GENERAR TARJETA DE ENTRENADOR
// =========================================================

function generarTarjetasEntrenador(
    entrenador,
    marcaciones
) {


    // -----------------------------------------------------
    // TOTAL HORAS DEL DÍA
    // -----------------------------------------------------

    let totalHorasDia =
        marcaciones.reduce(
            (acc, m) =>
                acc +
                (parseFloat(m.horas_trabajadas) || 0),
            0
        );


    // -----------------------------------------------------
    // BUSCAR TURNO ABIERTO
    // -----------------------------------------------------

    let ultimaMarcacion =
        marcaciones.find(
            m =>
                m.hora_entrada &&
                !m.hora_salida
        ) || null;


    let tieneTurnoAbierto =
        ultimaMarcacion !== null;


    // -----------------------------------------------------
    // ESTADO
    // -----------------------------------------------------

    let estadoBadge =
        '<span class="badge bg-secondary">Sin Registro</span>';

    if (tieneTurnoAbierto) {

        estadoBadge =
            '<span class="badge bg-info text-dark">En Turno</span>';

    } else if (marcaciones.length > 0) {

        estadoBadge =
            '<span class="badge bg-success">Turnos Completados</span>';
    }


    // -----------------------------------------------------
    // ID DEL ACORDEÓN
    // -----------------------------------------------------

    let collapseId =
        `detalles_entrenador_${entrenador.id}`;


    // =====================================================
    // GENERAR FILAS DE TURNOS
    // =====================================================

    let htmlSubFilas = '';


    // -----------------------------------------------------
    // SIN MARCACIONES
    // -----------------------------------------------------

    if (marcaciones.length === 0) {

        htmlSubFilas = `
            <tr>

                <td
                    colspan="6"
                    class="text-muted py-3 text-center"
                >
                    No hay marcaciones para este día.
                    Presiona
                    <b>Marcar Entrada</b>
                    para iniciar jornada.
                </td>

            </tr>
        `;

    }


    // -----------------------------------------------------
    // CON MARCACIONES
    // -----------------------------------------------------

    else {

        marcaciones.forEach((item, index) => {

            htmlSubFilas += `

                <tr>

                    <!-- ===============================
                         TURNO
                         =============================== -->

                    <td
                        class="celda-turno"
                        data-label="Turno"
                    >
                        Turno ${index + 1}
                    </td>


                    <!-- ===============================
                         ENTRADA
                         =============================== -->

                    <td data-label="Entrada">

                        <input
                            type="time"
                            class="form-control form-control-sm text-center mx-auto input-hora"
                            id="entrada_${item.id}"
                            value="${item.hora_entrada || ''}"
                        >

                    </td>


                    <!-- ===============================
                         SALIDA
                         =============================== -->

                    <td data-label="Salida">

                        <input
                            type="time"
                            class="form-control form-control-sm text-center mx-auto input-hora"
                            id="salida_${item.id}"
                            value="${item.hora_salida || ''}"
                        >

                    </td>


                    <!-- ===============================
                         HORAS
                         =============================== -->

                    <td data-label="Horas trabajadas">

                        <span class="badge bg-light text-dark border fs-6">

                            ${
                                parseFloat(
                                    item.horas_trabajadas || 0
                                ).toFixed(2)
                            }

                            hrs

                        </span>

                    </td>


                    <!-- ===============================
                         OBSERVACIONES
                         =============================== -->

                    <td data-label="Observaciones">

                        <input
                            type="text"
                            class="form-control form-control-sm mx-auto input-obs"
                            id="obs_${item.id}"
                            value="${item.observaciones || ''}"
                            placeholder="Opcional..."
                        >

                    </td>


                    <!-- ===============================
                         ACCIONES
                         =============================== -->

                    <td
                        class="celda-acciones"
                        data-label="Acciones"
                    >

                        <div class="turnos-botones">

                            <button
                                type="button"
                                class="btn btn-primary btn-accion-tabla"
                                title="Guardar Cambios"
                                onclick="
                                    guardarFila(
                                        ${entrenador.id},
                                        ${item.id}
                                    )
                                "
                            >

                                <i
                                    class="fa-solid fa-floppy-disk me-1"
                                ></i>

                                <span>
                                    Guardar
                                </span>

                            </button>


                            <button
                                type="button"
                                class="btn btn-danger btn-accion-tabla"
                                title="Eliminar Registro"
                                onclick="
                                    eliminarMarcacion(
                                        ${item.id}
                                    )
                                "
                            >

                                <i
                                    class="fa-solid fa-trash me-1"
                                ></i>

                                <span>
                                    Eliminar
                                </span>

                            </button>

                        </div>

                    </td>

                </tr>

            `;
        });

    }


    // =====================================================
    // TARJETA COMPLETA
    // =====================================================

    return `

        <div class="card mb-3 shadow-sm border">

            <!-- =============================================
                 CABECERA
                 ============================================= -->

            <div class="card-header bg-dark text-white">

                <div class="cabecera-entrenador-superior">


                    <!-- =====================================
                         INFORMACIÓN DEL ENTRENADOR
                         ===================================== -->

                    <div class="entrenador-info">


                        <!-- BOTÓN VER / OCULTAR -->

                        <button
                            type="button"
                            class="btn btn-light btn-sm fw-bold text-dark shadow-sm border"
                            data-bs-toggle="collapse"
                            data-bs-target="#${collapseId}"
                            aria-expanded="true"
                        >

                            <i
                                class="fa-solid fa-chevron-down me-1 text-primary"
                            ></i>

                            <span>
                                Ver / Ocultar Turnos
                            </span>

                        </button>


                        <!-- NOMBRE -->

                        <span
                            class="fw-bold fs-5 entrenador-nombre"
                        >

                            <i
                                class="fa-solid fa-user me-2"
                            ></i>

                            ${entrenador.nombre}

                        </span>


                        <!-- ESTADO -->

                        ${estadoBadge}

                    </div>


                    <!-- =====================================
                         TOTAL HORAS
                         ===================================== -->

                    <div class="entrenador-horas-resumen">

                        Total Día:

                        <b>
                            ${totalHorasDia.toFixed(2)} hrs
                        </b>

                    </div>


                    <!-- =====================================
                         BOTONES
                         ===================================== -->

                    <div class="cabecera-entrenador-botones">


                        <!-- MARCAR ENTRADA / SALIDA -->

                        <button
                            type="button"
                            class="btn btn-success btn-sm"
                            onclick="
                                accionRapidaMarcar(
                                    ${entrenador.id},
                                    ${
                                        tieneTurnoAbierto
                                            ? ultimaMarcacion.id
                                            : 'null'
                                    }
                                )
                            "
                        >

                            <i
                                class="fa-solid fa-clock me-1"
                            ></i>

                            ${
                                tieneTurnoAbierto
                                    ? 'Marcar Salida'
                                    : 'Marcar Entrada'
                            }

                        </button>


                        <!-- NUEVO TURNO -->

                        <button
                            type="button"
                            class="btn btn-outline-light btn-sm"
                            onclick="
                                crearNuevoTurno(
                                    ${entrenador.id}
                                )
                            "
                        >

                            <i
                                class="fa-solid fa-plus me-1"
                            ></i>

                            Nuevo Turno

                        </button>

                    </div>

                </div>

            </div>


            <!-- =============================================
                 DETALLE / ACORDEÓN
                 ============================================= -->

            <div
                class="collapse show"
                id="${collapseId}"
            >

                <div class="card-body p-0">


                    <!-- =====================================
                         TABLA
                         ===================================== -->

                    <table
                        class="table table-sm table-hover text-center align-middle mb-0 tabla-turnos"
                    >

                        <thead class="table-light">

                            <tr>

                                <th style="width: 10%;">
                                    Turno
                                </th>

                                <th style="width: 18%;">
                                    Entrada
                                </th>

                                <th style="width: 18%;">
                                    Salida
                                </th>

                                <th style="width: 14%;">
                                    Horas
                                </th>

                                <th style="width: 22%;">
                                    Observaciones
                                </th>

                                <th style="width: 18%;">
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            ${htmlSubFilas}

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    `;
}


// =========================================================
// MARCAR ENTRADA / SALIDA RÁPIDA
// =========================================================

function accionRapidaMarcar(
    usuario_id,
    idUltimaMarcacion
) {

    let ahora =
        new Date()
            .toTimeString()
            .split(' ')[0]
            .substring(0, 5);


    // -----------------------------------------------------
    // SI HAY TURNO ABIERTO → MARCAR SALIDA
    // -----------------------------------------------------

    if (idUltimaMarcacion) {

        const campoSalida =
            document.getElementById(
                `salida_${idUltimaMarcacion}`
            );

        if (campoSalida) {

            campoSalida.value = ahora;

        }

        guardarFila(
            usuario_id,
            idUltimaMarcacion
        );

    }


    // -----------------------------------------------------
    // SI NO HAY TURNO → CREAR ENTRADA
    // -----------------------------------------------------

    else {

        crearNuevoTurno(
            usuario_id,
            ahora
        );
    }
}


// =========================================================
// CREAR NUEVO TURNO
// =========================================================

function crearNuevoTurno(
    usuario_id,
    horaEntrada = ''
) {

    const fecha =
        document.getElementById("fecha").value;


    const horaInicio =
        horaEntrada ||
        new Date()
            .toTimeString()
            .split(' ')[0]
            .substring(0, 5);


    const params =
        new URLSearchParams({

            usuario_id: usuario_id,

            fecha: fecha,

            hora_entrada: horaInicio,

            hora_salida: '',

            observaciones: ''

        });


    fetch(
        "guardar_asistencia_entrenador.php",
        {
            method: "POST",

            headers: {
                "Content-Type":
                    "application/x-www-form-urlencoded"
            },

            body: params.toString()
        }
    )

    .then(res => res.json())

    .then(res => {

        if (res.status === "ok") {

            cargarAsistencia();

        } else {

            Swal.fire(
                'Error',
                res.mensaje ||
                "Error al crear turno",
                'error'
            );

        }

    })

    .catch(err => {

        console.error(
            "Error al crear turno:",
            err
        );

        Swal.fire(
            'Error',
            'No fue posible crear el turno.',
            'error'
        );

    });
}


// =========================================================
// GUARDAR FILA
// =========================================================

function guardarFila(
    usuario_id,
    idMarcacion
) {

    const fecha =
        document.getElementById("fecha").value;


    const entrada =
        document.getElementById(
            `entrada_${idMarcacion}`
        );


    const salida =
        document.getElementById(
            `salida_${idMarcacion}`
        );


    const observacion =
        document.getElementById(
            `obs_${idMarcacion}`
        );


    if (!entrada || !salida || !observacion) {

        Swal.fire(
            'Error',
            'No se encontraron los campos del turno.',
            'error'
        );

        return;
    }


    const hora_entrada =
        entrada.value;


    const hora_salida =
        salida.value;


    const observaciones =
        observacion.value;


    const params =
        new URLSearchParams({

            id: idMarcacion,

            usuario_id: usuario_id,

            fecha: fecha,

            hora_entrada: hora_entrada,

            hora_salida: hora_salida,

            observaciones: observaciones

        });


    fetch(
        "actualizar_asistencia_entrenador.php",
        {
            method: "POST",

            headers: {
                "Content-Type":
                    "application/x-www-form-urlencoded"
            },

            body: params.toString()
        }
    )

    .then(res => res.json())

    .then(res => {

        if (res.status === "ok") {

            cargarAsistencia();

        } else {

            Swal.fire(
                'Error',
                res.mensaje ||
                "Error al guardar",
                'error'
            );

        }

    })

    .catch(err => {

        console.error(
            "Error al guardar:",
            err
        );

        Swal.fire(
            'Error',
            'No fue posible guardar los cambios.',
            'error'
        );

    });
}


// =========================================================
// ELIMINAR MARCACIÓN
// =========================================================

function eliminarMarcacion(id) {

    Swal.fire({

        title: "¿Eliminar marcación?",

        text:
            "Se borrará este turno específico.",

        icon: "warning",

        showCancelButton: true,

        confirmButtonColor: "#d33",

        confirmButtonText:
            "Sí, eliminar",

        cancelButtonText:
            "Cancelar"

    })

    .then((result) => {

        if (!result.isConfirmed) {
            return;
        }


        fetch(
            "eliminar_asistencia_entrenador.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded"
                },

                body:
                    `id=${encodeURIComponent(id)}`
            }
        )

        .then(res => res.json())

        .then(res => {

            if (res.status === "ok") {

                cargarAsistencia();

            } else {

                Swal.fire(
                    'Error',
                    res.mensaje ||
                    "Error al eliminar",
                    'error'
                );

            }

        })

        .catch(err => {

            console.error(
                "Error al eliminar:",
                err
            );

            Swal.fire(
                'Error',
                'No fue posible eliminar la marcación.',
                'error'
            );

        });

    });
}


// =========================================================
// CARGAR AL INICIAR LA PÁGINA
// =========================================================

document.addEventListener(
    "DOMContentLoaded",
    cargarAsistencia
);

</script>


<?php
include("../../template/footer_modulos.php");
?>