<?php
/*
==========================================================
 MÓDULO: FINANCIERO
 SUBMÓDULO: REPORTES FINANCIEROS
 ARCHIVO: index.php
==========================================================
*/

require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");

if (!tiene_permiso('financiero_reportes')) {
    header("Location: " . $url_base . "/index.php");
    exit;
}

$modulo_actual = 'Financiero';
$submodulo_actual = 'Reportes financieros';

include("../../../template/header_modulos.php");
?>

<style>
    :root {
        --dclub-primary: #2563eb;   /* Azul oficial */
        --dclub-orange: #f97316;    /* Naranja alusivo al club */
        --dclub-dark: #0f172a;
    }

    .saas-card {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.05);
        min-height: 580px;
    }

    /* FONDO DE DASHBOARD SIMULADO */
    .dashboard-preview-bg {
        filter: blur(6px);
        opacity: 0.3;
        pointer-events: none;
        user-select: none;
        padding: 35px;
    }

    .preview-box {
        background: #f8fafc;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        padding: 20px;
    }

    /* OVERLAY GLASSMORPHISM */
    .teaser-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at 50% 30%, rgba(255, 255, 255, 0.88) 0%, rgba(248, 250, 252, 0.98) 100%);
        backdrop-filter: blur(8px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 24px;
        text-align: center;
    }

    /* BADGE CLUB */
    .badge-dclub {
        background: #fff7ed;
        color: #f97316;
        border: 1px solid #ffedd5;
        font-size: 12px;
        font-weight: 800;
        padding: 6px 16px;
        border-radius: 30px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 16px;
    }

    /* PISTA / CANCHA CON CR7 */
    .field-progress-container {
        width: 100%;
        max-width: 540px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 20px 24px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04);
        margin: 20px 0 28px 0;
    }

    .pitch-track {
        position: relative;
        height: 72px;
        background: #f1f5f9;
        border-radius: 16px;
        border: 1px solid #cbd5e1;
        overflow: hidden;
        margin-top: 10px;
        display: flex;
        align-items: center;
    }

    .pitch-fill {
        height: 100%;
        width: 80%; /* Porcentaje de avance de la barra */
        background: linear-gradient(90deg, #2563eb 0%, #f97316 100%);
        border-radius: 14px 0 0 14px;
    }

    .pitch-line-center {
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        width: 2px;
        background: rgba(255, 255, 255, 0.6);
        z-index: 1;
    }

    /* CONTENEDOR DE CR7 Y EL BALÓN */
    .runner-sprite-wrapper {
        position: absolute;
        left: 80%; /* Sincronizado con el avance de la barra */
        bottom: 2px;
        transform: translateX(-60%);
        z-index: 3;
        display: flex;
        align-items: flex-end;
        gap: 2px;
    }

    /* ANIMACIÓN DE FÍSICA Y SPRINT DE CR7 (HACIA LA DERECHA) */
    .cr7-runner-svg {
        width: 58px;
        height: 64px;
        animation: cr7TorsoBounce 0.32s ease-in-out infinite alternate;
    }

    /* ANIMACIÓN DE ZANCADA POTENTE TIPO CR7 */
    .leg-back-cr7 {
        transform-origin: 24px 28px;
        animation: cr7LegBack 0.32s ease-in-out infinite alternate;
    }

    .leg-front-cr7 {
        transform-origin: 24px 28px;
        animation: cr7LegFront 0.32s ease-in-out infinite alternate;
    }

    .arm-back-cr7 {
        transform-origin: 24px 18px;
        animation: cr7ArmBack 0.32s ease-in-out infinite alternate;
    }

    .arm-front-cr7 {
        transform-origin: 24px 18px;
        animation: cr7ArmFront 0.32s ease-in-out infinite alternate;
    }

    @keyframes cr7TorsoBounce {
        0% { transform: translateY(0px) rotate(8deg); }
        100% { transform: translateY(-6px) rotate(11deg); }
    }

    @keyframes cr7LegBack {
        0% { transform: rotate(-52deg); }
        100% { transform: rotate(42deg); }
    }

    @keyframes cr7LegFront {
        0% { transform: rotate(42deg); }
        100% { transform: rotate(-52deg); }
    }

    @keyframes cr7ArmBack {
        0% { transform: rotate(50deg); }
        100% { transform: rotate(-50deg); }
    }

    @keyframes cr7ArmFront {
        0% { transform: rotate(-50deg); }
        100% { transform: rotate(50deg); }
    }

    /* BALÓN OFICIAL RODANDO */
    .ball-svg {
        width: 20px;
        height: 20px;
        margin-bottom: 6px;
        animation: ballSpinCR7 0.38s linear infinite;
    }

    @keyframes ballSpinCR7 {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .goal-flag-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 26px;
        z-index: 2;
    }

    /* CHIPS DE FUNCIONES */
    .features-teaser-grid {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 12px;
        max-width: 600px;
    }

    .feature-chip {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    /* BOTÓN DCLUB NARANJA */
    .btn-dclub-orange {
        background-color: #f97316;
        color: #ffffff;
        border: none;
    }
    .btn-dclub-orange:hover {
        background-color: #ea580c;
        color: #ffffff;
    }
</style>

<!-- ENCABEZADO -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-chart-line text-primary me-2"></i> Reportes Financieros
        </h2>
        <p class="text-muted mb-0">
            Módulo centralizado de informes e indicadores de tesorería
        </p>
    </div>
</div>

<!-- CONTENEDOR PRINCIPAL CON CRISTIANO RONALDO (CR7) CON UNIFORME AZUL Y NARANJA -->
<div class="saas-card mb-5">
    
    <!-- FONDO SIMULADO DE DASHBOARD DEPORTIVO -->
    <div class="dashboard-preview-bg">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="preview-box">
                    <small class="text-muted fw-bold">RECAUDO MENSUAL</small>
                    <h3 class="fw-bold text-dark mt-1">$14.250.000 COP</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="preview-box">
                    <small class="text-muted fw-bold">PENSIONES EN MORA</small>
                    <h3 class="fw-bold text-danger mt-1">$1.800.000 COP</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="preview-box">
                    <small class="text-muted fw-bold">UTILIDAD OPERATIVA</small>
                    <h3 class="fw-bold text-success mt-1">$8.450.000 COP</h3>
                </div>
            </div>
        </div>
        <div class="preview-box py-5 text-center">
            <div class="d-flex justify-content-around">
                <div class="bg-secondary-subtle rounded-3 p-4 w-25"></div>
                <div class="bg-secondary-subtle rounded-3 p-4 w-25"></div>
                <div class="bg-secondary-subtle rounded-3 p-4 w-25"></div>
            </div>
        </div>
    </div>

    <!-- OVERLAY CON CONTENIDO PRINCIPAL -->
    <div class="teaser-overlay">
        
        <!-- BADGE CLUB -->
        <span class="badge-dclub">
            <i class="fa-solid fa-fire me-1"></i> DCLUB v2.0 • Modo CR7 Activo
        </span>

        <!-- TÍTULOS -->
        <h3 class="fw-extrabold text-dark fs-2 mb-2" style="letter-spacing: -0.5px;">
            Inteligencia Financiera de Alto Rendimiento
        </h3>
        <p class="text-muted fs-6 mx-auto mb-3" style="max-width: 500px; line-height: 1.5;">
            Estamos finalizando el desarrollo del nuevo tablero de control de tesorería, auditoría de recaudos y balances exportables.
        </p>

        <!-- CHIPS CON CARACTERÍSTICAS QUE VENDRÁN -->
        <div class="features-teaser-grid">
            <div class="feature-chip">
                <i class="fa-solid fa-file-excel text-success"></i> Exportación a Excel y PDF
            </div>
            <div class="feature-chip">
                <i class="fa-solid fa-chart-pie text-primary"></i> Análisis por Categoría
            </div>
            <div class="feature-chip">
                <i class="fa-solid fa-bell text-warning"></i> Alertas de Morosidad
            </div>
        </div>

        <!-- BARRA Y CR7 EN CANCHA CORRIENDO A MÁXIMA VELOCIDAD -->
        <div class="field-progress-container">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark fs-7">
                    ⚡ CR7 Sprint en Proceso
                </span>
                <span class="fw-extrabold text-primary fs-6">80% SIUUU!</span>
            </div>

            <!-- CAMPO CON CR7 EN SPRINT -->
            <div class="pitch-track">
                <div class="pitch-fill"></div>
                <div class="pitch-line-center"></div>
                
                <!-- SILUETA Y ROPA DE CRISTIANO RONALDO (CR7 - DORSAL 7) EN COLORES DEL CLUB -->
                <div class="runner-sprite-wrapper">
                    
                    <svg class="cr7-runner-svg" viewBox="0 0 54 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        
                        <!-- PIERNA TRASERA FLEXIONADA EN POTENCIA -->
                        <g class="leg-back-cr7">
                            <path d="M24 30 L14 44 L4 56" stroke="#0f172a" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <!-- BOTA AZUL TRASERA -->
                            <path d="M4 56 L10 57" stroke="#2563eb" stroke-width="5" stroke-linecap="round"/>
                        </g>

                        <!-- BRAZO TRASERO EN IMPULSO -->
                        <g class="arm-back-cr7">
                            <path d="M24 18 L12 28 L5 22" stroke="#f97316" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>

                        <!-- TORSO DE CR7 CON CAMISETA NARANJA Y EL NÚMERO 7 -->
                        <path d="M18 16 L30 16 L26 32 L20 32 Z" fill="#f97316" stroke="#0f172a" stroke-width="1.8" stroke-linejoin="round"/>
                        <text x="21" y="27" fill="#ffffff" font-size="9" font-weight="900" font-family="sans-serif">7</text>

                        <!-- CABEZA CON CORTE DE CABELLO ESTILO CR7 -->
                        <circle cx="28" cy="10" r="5.5" fill="#0f172a"/>
                        <!-- PEINADO DEGRADADO DERECHO -->
                        <path d="M25 5 Q31 3 33 8" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>

                        <!-- PIERNA DELANTERA ATLÉTICA EN MÁXIMA EXTENSIÓN -->
                        <g class="leg-front-cr7">
                            <path d="M24 30 L35 42 L42 56" stroke="#0f172a" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                            <!-- BOTA AZUL DELANTERA -->
                            <path d="M42 56 L49 56" stroke="#2563eb" stroke-width="5.5" stroke-linecap="round"/>
                        </g>

                        <!-- BRAZO DELANTERO ERGUIDO TIPO CR7 -->
                        <g class="arm-front-cr7">
                            <path d="M24 18 L36 26 L42 18" stroke="#f97316" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                    </svg>

                    <!-- BALÓN OFICIAL CON ACCENTOS NARANJA -->
                    <svg class="ball-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                        <polygon points="12,7 15,9 14,13 10,13 9,9" fill="#f97316"/>
                        <line x1="12" y1="2" x2="12" y2="7" stroke="#0f172a" stroke-width="1.5"/>
                        <line x1="22" y1="12" x2="15" y2="9" stroke="#0f172a" stroke-width="1.5"/>
                        <line x1="18" y1="20" x2="14" y2="13" stroke="#0f172a" stroke-width="1.5"/>
                        <line x1="6" y1="20" x2="10" y2="13" stroke="#0f172a" stroke-width="1.5"/>
                        <line x1="2" y1="12" x2="9" y2="9" stroke="#0f172a" stroke-width="1.5"/>
                    </svg>

                </div>

                <!-- META CON BANDERA A CUADROS -->
                <span class="goal-flag-icon" title="Meta 100%">🏁</span>
            </div>
        </div>

        <!-- ACCIONES -->
        <div class="d-flex gap-3 flex-wrap justify-content-center">
<a href="<?= htmlspecialchars($url_base) ?>/modulos/dashboard/index.php" class="btn btn-outline-dark btn-lg rounded-pill px-4 fw-bold fs-6">
    <i class="fa-solid fa-arrow-left me-2"></i> Volver al Dashboard
</a>
            <button class="btn btn-dclub-orange btn-lg rounded-pill px-4 fw-bold fs-6 shadow-sm" onclick="alert('¡SIUUU! Te notificaremos tan pronto el módulo de Reportes Financieros esté listo.');">
                <i class="fa-solid fa-bell me-2"></i> Notificarme al Lanzar
            </button>
        </div>

    </div>
</div>

<?php
include("../../../template/footer_modulos.php");
?>