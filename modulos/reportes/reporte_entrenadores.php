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

$fecha_inicio  = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin     = $_GET['fecha_fin'] ?? date('Y-m-t'); 
$entrenador_id = $_GET['entrenador_id'] ?? '';

$sql_entrenador = "";
$params = [$fecha_inicio, $fecha_fin];

if (!empty($entrenador_id)) {
    $sql_entrenador = " AND u.id = ? ";
    $params[] = $entrenador_id;
}

// Agrupamos por u.id para evitar duplicados en la tabla principal
$stmt = $conexion->prepare("
    SELECT 
        u.id AS usuario_id,
        u.nombre AS entrenador_nombre,
        COUNT(CASE WHEN a.estado IN ('cerrada', 'abierta') THEN 1 END) AS dias_asistidos,
        COUNT(CASE WHEN a.estado = 'ausente' THEN 1 END) AS dias_ausentes,
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

$stmt_e = $conexion->query("
    SELECT u.id, u.nombre 
    FROM usuario u
    INNER JOIN rol r ON u.rol_id = r.id
    WHERE LOWER(r.nombre) = 'entrenador' AND u.estado = 'activo'
    GROUP BY u.id
    ORDER BY u.nombre ASC
");
$lista_entrenadores = $stmt_e->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="usuarios-toolbar mb-4">
    <div class="usuarios-acciones">
        <a href="<?= $url_base ?>/modulos/entrenador/index.php" class="btn btn-outline-dark me-2">
            <i class="fa-solid fa-clock"></i> Control de Asistencia
        </a>
        <a href="<?= $url_base ?>/modulos/dashboard/index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>
</div>

<!-- FILTROS -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="reporte_entrenadores.php" class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label fw-bold">Fecha Inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fecha_inicio) ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-bold">Fecha Fin:</label>
                <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fecha_fin) ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-bold">Entrenador:</label>
                <select name="entrenador_id" class="form-select">
                    <option value="">-- Todos los Entrenadores --</option>
                    <?php foreach($lista_entrenadores as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $entrenador_id == $e['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                <a href="reporte_entrenadores.php" class="btn btn-light border w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- TABLA PRINCIPAL -->
<div class="tabla-usuarios-contenedor">
    <table class="table table-bordered table-hover text-center align-middle tabla-usuarios">
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
                    <td class="usuario-nombre text-start ps-4">
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

<!-- MODAL DETALLE DE ASISTENCIAS -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="modalDetalleLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalDetalleLabel">
            <i class="fa-solid fa-calendar-days me-2"></i>Detalle de Asistencia: <span id="nombreEntrenadorModal"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
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
                <tbody id="contenidoDetalleModal">
                    <!-- Contenido dinámico -->
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
function verDetalle(usuario_id, nombre) {
    let fechaInicio = document.getElementById("fecha_inicio").value;
    let fechaFin = document.getElementById("fecha_fin").value;
    let modalElement = new bootstrap.Modal(document.getElementById('modalDetalle'));
    let tbody = document.getElementById("contenidoDetalleModal");
    
    document.getElementById("nombreEntrenadorModal").textContent = nombre;
    tbody.innerHTML = '<tr><td colspan="7" class="py-4"><i class="fa-solid fa-spinner fa-spin me-2"></i>Cargando detalle...</td></tr>';
    
    modalElement.show();

    fetch(`../entrenador/obtener_detalle_entrenador.php?usuario_id=${usuario_id}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`)
    .then(async res => {
        let text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error("Respuesta no JSON recibida:", text);
            throw new Error("Respuesta no válida del servidor");
        }
    })
    .then(res => {
        if (res.status === 'ok') {
            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-muted py-4">No hay marcas registradas en este período.</td></tr>';
                return;
            }

            let html = '';
            res.data.forEach(item => {
                let badgeClass = 'bg-secondary';
                if (item.estado === 'cerrada') badgeClass = 'bg-success';
                else if (item.estado === 'abierta') badgeClass = 'bg-info text-dark';
                else if (item.estado === 'ausente') badgeClass = 'bg-danger';

                // Definir etiqueta visual para el pago
                let badgePago = (item.pagado == 1) 
                    ? '<span class="badge bg-success">Pagado</span>' 
                    : '<span class="badge bg-warning text-dark">Pendiente</span>';

                html += `
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
            });
            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-danger py-4">${res.mensaje || 'Error al obtener el detalle.'}</td></tr>`;
        }
    })
    .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger py-4">${err.message}</td></tr>`;
    });
}
</script>

<?php include("../../template/footer_modulos.php"); ?>