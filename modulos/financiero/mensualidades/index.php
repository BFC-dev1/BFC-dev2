<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . "/../../../includes/config.php");
require_once(__DIR__ . "/../../../includes/conexion.php");

// Asignar automáticamente la conexión si viene con otro nombre desde config.php
if (!isset($conexion)) {
    if (isset($conn)) {
        $conexion = $conn;
    } elseif (isset($pdo)) {
        $conexion = $pdo;
    } elseif (isset($db)) {
        $conexion = $db;
    }
}

// Verificar permisos
if (!function_exists('tiene_permiso') || (!tiene_permiso('pagos') && ($_SESSION['rol'] ?? '') !== 'Administrador')) {
    header("Location: " . $url_base . "/index.php");
    exit;
}

$mes_actual = date('m');
$anio_actual = date('Y');

// Filtros de búsqueda
$filtro_mes = $_GET['mes'] ?? $mes_actual;
$filtro_anio = $_GET['anio'] ?? $anio_actual;
$filtro_estado = $_GET['estado'] ?? '';

// ==========================================
// 1. CONSULTAS DEL DASHBOARD FINANCIERO
// ==========================================
$sql_cobrado = "SELECT COALESCE(SUM(t.monto_pagado), 0) AS total 
                FROM transacciones_pago t 
                JOIN cuotas_mensuales c ON t.id_cuota = c.id 
                WHERE MONTH(t.fecha_pago) = ? AND YEAR(t.fecha_pago) = ?";
$stmt = $conexion->prepare($sql_cobrado);
$stmt->execute([$filtro_mes, $filtro_anio]);
$total_cobrado = $stmt->fetchColumn();

$sql_pendiente = "SELECT COALESCE(SUM(monto), 0) AS total 
                  FROM cuotas_mensuales 
                  WHERE mes = ? AND anio = ? AND estado = 'pendiente'";
$stmt = $conexion->prepare($sql_pendiente);
$stmt->execute([$filtro_mes, $filtro_anio]);
$total_pendiente = $stmt->fetchColumn();

$sql_vencidos = "SELECT COUNT(DISTINCT id_deportista) FROM cuotas_mensuales WHERE estado = 'vencido'";
$total_vencidos = $conexion->query($sql_vencidos)->fetchColumn();

// ==========================================
// 2. CONSULTA DE TABLA DE CUOTAS (Tabla 'deportista' en singular)
// ==========================================
$query = "SELECT c.*, d.nombre AS deportista_nombre, d.nombre AS deportista_nombre, d.telefono 
          FROM cuotas_mensuales c
          JOIN deportista d ON c.id_deportista = d.id
          WHERE c.mes = :mes AND c.anio = :anio";

if (!empty($filtro_estado)) {
    $query .= " AND c.estado = :estado";
}
$query .= " ORDER BY d.nombre ASC";

$stmt = $conexion->prepare($query);
$params = [':mes' => $filtro_mes, ':anio' => $filtro_anio];
if (!empty($filtro_estado)) {
    $params[':estado'] = $filtro_estado;
}
$stmt->execute($params);
$cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo de Pagos | Bellavista FC</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-light">

<div class="container-fluid p-4">

<!-- ========================================== -->
<!-- BOTÓN VOLVER A FINANCIERO                  -->
<!-- ========================================== -->

<div class="mb-3">

<a
    href="<?= $url_base ?>/modulos/dashboard/index.php"
    class="btn btn-outline-dark"
>
    <i class="fa-solid fa-arrow-left"></i>
    Volver al Dashboard
</a>

</div>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">
    <i class="fa-solid fa-credit-card text-primary"></i>
    Mensualidades
</h2>

<p class="text-muted mb-0">
    Gestión de cuotas mensuales y estado de cartera
</p>
                <p class="text-muted mb-0">Control de mensualidades y estado de cartera</p>
            </div>
            
            <form action="generar_cuotas.php" method="POST" onsubmit="return confirm('¿Deseas generar las cuotas mensuales para todos los deportistas activos?');">
                <input type="hidden" name="mes" value="<?= $filtro_mes ?>">
                <input type="hidden" name="anio" value="<?= $filtro_anio ?>">
                <button type="submit" class="btn btn-primary fw-bold shadow-sm">
                    <i class="fa-solid fa-rotate-right me-1"></i> Generar Cuotas del Mes
                </button>
            </form>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- DASHBOARD CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-success border-4">
                    <div class="card-body">
                        <small class="text-muted fw-bold text-uppercase">Total Cobrado (Mes)</small>
                        <h3 class="fw-bold text-success mb-0">$<?= number_format($total_cobrado, 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-warning border-4">
                    <div class="card-body">
                        <small class="text-muted fw-bold text-uppercase">Total Pendiente</small>
                        <h3 class="fw-bold text-warning mb-0">$<?= number_format($total_pendiente, 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm border-start border-danger border-4">
                    <div class="card-body">
                        <small class="text-muted fw-bold text-uppercase">Deportistas Vencidos</small>
                        <h3 class="fw-bold text-danger mb-0"><?= $total_vencidos ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Mes</label>
                        <select name="mes" class="form-select">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= sprintf('%02d', $m) ?>" <?= $filtro_mes == sprintf('%02d', $m) ? 'selected' : '' ?>>
                                    <?= date("F", mktime(0, 0, 0, $m, 10)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Año</label>
                        <select name="anio" class="form-select">
                            <option value="2026" <?= $filtro_anio == '2026' ? 'selected' : '' ?>>2026</option>
                            <option value="2025" <?= $filtro_anio == '2025' ? 'selected' : '' ?>>2025</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="pendiente" <?= $filtro_estado == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="pagado" <?= $filtro_estado == 'pagado' ? 'selected' : '' ?>>Pagado</option>
                            <option value="vencido" <?= $filtro_estado == 'vencido' ? 'selected' : '' ?>>Vencido</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-dark w-100 fw-bold mt-4">
                            <i class="fa-solid fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABLA -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Deportista</th>
                                <th>Mes / Año</th>
                                <th>Vencimiento</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cuotas)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No hay cuotas registradas para este periodo. Haz clic en "Generar Cuotas del Mes".</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cuotas as $c): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($c['deportista_nombre']) ?></td>
                                        <td><?= sprintf('%02d', $c['mes']) ?>/<?= $c['anio'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($c['fecha_vencimiento'])) ?></td>
                                        <td class="fw-bold">$<?= number_format($c['monto'], 2) ?></td>
                                        <td>
                                            <?php if ($c['estado'] === 'pagado'): ?>
                                                <span class="badge bg-success">Pagado</span>
                                            <?php elseif ($c['estado'] === 'pendiente'): ?>
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Vencido</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($c['estado'] !== 'pagado'): ?>
                                                <button class="btn btn-sm btn-success me-1 fw-bold" 
                                                        onclick="abrirModalPago(
    <?= $c['id'] ?>,
    '<?= htmlspecialchars($c['deportista_nombre'], ENT_QUOTES, 'UTF-8') ?>',
    <?= $c['monto'] ?>
)"
                                                    <i class="fa-solid fa-dollar-sign"></i> Pagar
                                                </button>
                                            <?php endif; ?>

                                            <?php 
                                            $telefono = preg_replace('/[^0-9]/', '', $c['telefono'] ?? '');
                                            $msg_wa = "Hola " . $c['deportista_nombre'] . ", te recordamos el estado de tu cuota mensual ($" . number_format($c['monto'], 2) . "). Estado: " . strtoupper($c['estado']);
                                            $url_wa = !empty($telefono) ? "https://wa.me/" . $telefono . "?text=" . urlencode($msg_wa) : "#";
                                            ?>
<?php if ($c['estado'] !== 'pagado'): ?>

    <?php 
    $telefono = preg_replace('/[^0-9]/', '', $c['telefono'] ?? '');

    $estado_texto = strtoupper($c['estado']);

    $msg_wa = "Hola " . $c['deportista_nombre'] .
        ", te recordamos que tienes una cuota mensual " .
        $estado_texto .
        " por valor de $" .
        number_format($c['monto'], 2) .
        ".";

    $url_wa = !empty($telefono)
        ? "https://wa.me/" . $telefono . "?text=" . urlencode($msg_wa)
        : "#";
    ?>

    <a
        href="<?= $url_wa ?>"
        target="_blank"
        class="btn btn-sm btn-outline-success <?= empty($telefono) ? 'disabled' : '' ?>"
    >
        <i class="fa-brands fa-whatsapp"></i>
        Recordar
    </a>

<?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL PAGO -->
<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog">
        <form action="procesar_pago.php" method="POST" class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-cash-register"></i> Registrar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_cuota" id="modal_id_cuota">
                <div class="mb-3">
                    <label class="form-label fw-bold">Deportista</label>
                    <input type="text" id="modal_deportista" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Monto ($)</label>
                    <input type="number" step="0.01" name="monto_pagado" id="modal_monto" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Método de Pago</label>
                    <select name="metodo_pago" class="form-select" required>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Tarjeta">Tarjeta</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success fw-bold">Confirmar Pago</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function abrirModalPago(idCuota, deportista, monto) {
    document.getElementById('modal_id_cuota').value = idCuota;
    document.getElementById('modal_deportista').value = deportista;
    document.getElementById('modal_monto').value = monto;
    new bootstrap.Modal(document.getElementById('modalPago')).show();
}
</script>
</body>
</html>