<?php
/*
=========================================================
MÓDULO: REPORTES - ENTRENADORES
SISTEMA: BELLAVISTA FC
ARCHIVO: reporte_entrenadores.php
Ubicación: modulos/reportes/reporte_entrenadores.php
=========================================================
*/

require_once(__DIR__ . "/../../includes/config.php");
require_once(__DIR__ . "/../../includes/verificar_roles.php");

permitirRoles(['admin', 'entrenador']);

include(__DIR__ . "/../conexion_modulos.php");

$modulo_actual = 'Reportes';
$submodulo_actual = 'Entrenadores';
$menu_modulo = [];

include("../../template/header_modulos.php");

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin    = $_GET['fecha_fin'] ?? date('Y-m-t');

$usuario_actual_id = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0;
$rol_actual_id     = $_SESSION['rol_id'] ?? 0;

$sql_entrenador = "";
$params = [$fecha_inicio, $fecha_fin];

if ($rol_actual_id == 3) {
    $sql_entrenador = " AND u.id = ? ";
    $params[] = $usuario_actual_id;
}

$stmt = $conexion->prepare("
    SELECT 
        u.id AS usuario_id,
        u.nombre AS entrenador_nombre,
        COUNT(DISTINCT CASE WHEN a.estado IN ('cerrada', 'abierta') THEN a.fecha END) AS dias_asistidos,
        COUNT(DISTINCT CASE WHEN a.estado = 'ausente' THEN a.fecha END) AS dias_ausentes,
        IFNULL(SUM(a.horas_trabajadas), 0) AS total_horas
    FROM usuario u
    INNER JOIN rol r ON u.rol_id = r.id
    LEFT JOIN asistencia_entrenador a ON u.id = a.usuario_id AND a.fecha BETWEEN ? AND ?
    WHERE LOWER(r.nombre) = 'entrenador' AND u.estado = 'activo' {$sql_entrenador}
    GROUP BY u.id
    ORDER BY u.nombre ASC
");
$stmt->execute($params);
$reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="<?= $url_base ?>/<?= $css_base ?>/asistencia.css">

<!-- BOTONES SUPERIORES -->
<div class="reporte-toolbar mb-4">
    <div class="reporte-acciones">
        <a href="<?= $url_base ?>/modulos/entrenador/index.php" class="btn btn-outline-dark">
            <i class="fa-solid fa-clock me-1"></i> Control de Asistencia
        </a>
        <a href="<?= $url_base ?>/modulos/dashboard/index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
    </div>
</div>

<!-- FILTROS -->
<div class="card mb-4 shadow-sm reporte-filtros">
    <div class="card-body">
        <form method="GET" action="reporte_entrenadores.php" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label fw-bold">Fecha Inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fecha_inicio) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-bold">Fecha Fin:</label>
                <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fecha_fin) ?>">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-filter me-1"></i> Filtrar
                </button>
                <a href="reporte_entrenadores.php" class="btn btn-light border w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- VISTA ESCRITORIO (TABLA) -->
<div class="reporte-entrenadores-desktop table-responsive">
    <table class="table table-bordered table-hover text-center align-middle">
        <thead class="table-dark">
            <tr>
                <th>Entrenador</th>
                <th>Días Trabajados</th>
                <th>Ausencias</th>
                <th>Total Horas Acumuladas</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($reporte)): ?>
            <tr>
                <td colspan="5" class="text-muted py-4">No se encontraron registros en el rango seleccionado.</td>
            </tr>
            <?php else: ?>
                <?php foreach($reporte as $row): ?>
                <tr>
                    <td class="text-start ps-4 fw-bold">
                        <i class="fa-solid fa-user me-2 text-secondary"></i>
                        <?= htmlspecialchars($row['entrenador_nombre']) ?>
                    </td>
                    <td>
                        <span class="badge bg-success fs-6"><?= $row['dias_asistidos'] ?> días</span>
                    </td>
                    <td>
                        <span class="badge bg-danger fs-6"><?= $row['dias_ausentes'] ?> días</span>
                    </td>
                    <td>
                        <span class="badge bg-primary fs-6 px-3 py-2">
                            <?= number_format($row['total_horas'], 2) ?> hrs
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-info btn-sm text-white" 
                                onclick="verDetalle(<?= $row['usuario_id'] ?>, '<?= htmlspecialchars(addslashes($row['entrenador_nombre'])) ?>')" 
                                title="Ver Detalle de Días">
                            <i class="fa-solid fa-eye me-1"></i> Ver Detalle
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- VISTA MÓVIL (TARJETAS CENTRADAS) -->
<div class="reporte-entrenadores-movil">
    <?php if(empty($reporte)): ?>
        <div class="alert alert-warning text-center">
            No se encontraron registros en el rango seleccionado.
        </div>
    <?php else: ?>
        <?php foreach($reporte as $row): ?>
            <div class="reporte-entrenador-card">
                <div class="reporte-entrenador-card-header text-center">
                    <i class="fa-solid fa-user me-1"></i> <?= htmlspecialchars($row['entrenador_nombre']) ?>
                </div>
                <div class="reporte-entrenador-card-body">
                    <div class="reporte-dato">
                        <span class="reporte-dato-label">Días Trabajados:</span>
                        <span class="badge bg-success fs-6"><?= $row['dias_asistidos'] ?> días</span>
                    </div>
                    <div class="reporte-dato">
                        <span class="reporte-dato-label">Ausencias:</span>
                        <span class="badge bg-danger fs-6"><?= $row['dias_ausentes'] ?> días</span>
                    </div>
                    <div class="reporte-dato">
                        <span class="reporte-dato-label">Total Horas:</span>
                        <span class="badge bg-primary fs-6 px-3 py-2"><?= number_format($row['total_horas'], 2) ?> hrs</span>
                    </div>
                    <button class="btn btn-info btn-sm text-white reporte-btn-detalle" 
                            onclick="verDetalle(<?= $row['usuario_id'] ?>, '<?= htmlspecialchars(addslashes($row['entrenador_nombre'])) ?>')">
                        <i class="fa-solid fa-eye me-1"></i> Ver Detalle
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- MODAL DETALLE DE ASISTENCIAS -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="modalDetalleLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalDetalleLabel">
            <i class="fa-solid fa-calendar-days me-2"></i>Detalle: <span id="nombreEntrenadorModal"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-2 p-md-3">
        <!-- VISTA TABLA DESKTOP -->
        <div class="detalle-asistencia-desktop table-responsive">
            <table class="table table-striped table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Horas</th>
                        <th>Estado</th>
                        <th>Pago</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody id="contenidoDetalleModalDesktop">
                    <!-- Contenido dinámico -->
                </tbody>
            </table>
        </div>

        <!-- VISTA MÓVIL MODAL -->
        <div class="detalle-asistencia-movil" id="contenidoDetalleModalMovil">
            <!-- Contenido dinámico -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary w-100 w-md-auto" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
function verDetalle(usuario_id, nombre) {
    let fechaInicio = document.getElementById("fecha_inicio").value;
    let fechaFin = document.getElementById("fecha_fin").value;
    let modalElement = new bootstrap.Modal(document.getElementById('modalDetalle'));
    
    let tbodyDesktop = document.getElementById("contenidoDetalleModalDesktop");
    let containerMovil = document.getElementById("contenidoDetalleModalMovil");
    
    document.getElementById("nombreEntrenadorModal").textContent = nombre;
    
    tbodyDesktop.innerHTML = '<tr><td colspan="7" class="py-4"><i class="fa-solid fa-spinner fa-spin me-2"></i>Cargando detalle...</td></tr>';
    containerMovil.innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-2"></i>Cargando detalle...</div>';
    
    modalElement.show();

    fetch(`../entrenador/obtener_detalle_entrenador.php?usuario_id=${usuario_id}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`)
    .then(async res => {
        let text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error("Respuesta no JSON:", text);
            throw new Error("Respuesta no válida del servidor");
        }
    })
    .then(res => {
        if (res.status === 'ok') {
            if (res.data.length === 0) {
                tbodyDesktop.innerHTML = '<tr><td colspan="7" class="text-muted py-4">No hay marcas registradas en este período.</td></tr>';
                containerMovil.innerHTML = '<div class="alert alert-warning text-center">No hay marcas registradas en este período.</div>';
                return;
            }

            let htmlDesktop = '';
            let htmlMovil = '';

            res.data.forEach(item => {
                let badgeClass = 'bg-secondary';
                if (item.estado === 'cerrada') badgeClass = 'bg-success';
                else if (item.estado === 'abierta') badgeClass = 'bg-info text-dark';
                else if (item.estado === 'ausente') badgeClass = 'bg-danger';

                let badgePago = (item.pagado == 1) 
                    ? '<span class="badge bg-success">Pagado</span>' 
                    : '<span class="badge bg-warning text-dark">Pendiente</span>';

                // Fila Desktop
                htmlDesktop += `
                    <tr>
                        <td class="fw-bold">${item.fecha_formateada}</td>
                        <td>${item.hora_entrada ? item.hora_entrada : '--'}</td>
                        <td>${item.hora_salida ? item.hora_salida : '--'}</td>
                        <td><span class="badge bg-light text-dark border">${parseFloat(item.horas_trabajadas || 0).toFixed(2)} hrs</span></td>
                        <td><span class="badge ${badgeClass}">${item.estado.toUpperCase()}</span></td>
                        <td>${badgePago}</td>
                        <td class="text-start">${item.observaciones ? item.observaciones : '<span class="text-muted small">Sin obs.</span>'}</td>
                    </tr>
                `;

                // Tarjeta Móvil
                htmlMovil += `
                    <div class="detalle-marcacion-card">
                        <div class="detalle-marcacion-header">
                            <span class="detalle-marcacion-fecha"><i class="fa-solid fa-calendar me-1"></i> ${item.fecha_formateada}</span>
                            <span class="badge ${badgeClass}">${item.estado.toUpperCase()}</span>
                        </div>
                        <div class="detalle-marcacion-body">
                            <div class="detalle-marcacion-dato">
                                <span class="detalle-marcacion-label">Entrada / Salida:</span>
                                <span class="detalle-marcacion-valor fw-bold">${item.hora_entrada || '--'} / ${item.hora_salida || '--'}</span>
                            </div>
                            <div class="detalle-marcacion-dato">
                                <span class="detalle-marcacion-label">Horas trabajadas:</span>
                                <span class="badge bg-light text-dark border">${parseFloat(item.horas_trabajadas || 0).toFixed(2)} hrs</span>
                            </div>
                            <div class="detalle-marcacion-dato">
                                <span class="detalle-marcacion-label">Estado de Pago:</span>
                                <span>${badgePago}</span>
                            </div>
                            ${item.observaciones ? `
                                <div class="detalle-observacion">
                                    <strong>Observaciones:</strong>
                                    ${item.observaciones}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            tbodyDesktop.innerHTML = htmlDesktop;
            containerMovil.innerHTML = htmlMovil;
        } else {
            let errorMsg = res.mensaje || 'Error al obtener el detalle.';
            tbodyDesktop.innerHTML = `<tr><td colspan="7" class="text-danger py-4">${errorMsg}</td></tr>`;
            containerMovil.innerHTML = `<div class="alert alert-danger text-center">${errorMsg}</div>`;
        }
    })
    .catch(err => {
        console.error(err);
        tbodyDesktop.innerHTML = `<tr><td colspan="7" class="text-danger py-4">${err.message}</td></tr>`;
        containerMovil.innerHTML = `<div class="alert alert-danger text-center">${err.message}</div>`;
    });
}
</script>

<?php include("../../template/footer_modulos.php"); ?>