<?php
/**
 * ================================================================
 * CONFIGURACIÓN WHATSAPP - BELLAVISTA FC
 * ================================================================
 *
 * Archivo:
 * /modulos/notificaciones/config_whatsapp.php
 *
 * Función:
 * Centralizar la configuración necesaria para conectarse con
 * WhatsApp Cloud API de Meta.
 *
 * ACTUALMENTE:
 * Se utiliza el número de PRUEBA proporcionado por Meta.
 *
 * IMPORTANTE:
 * - NO compartir el Access Token.
 * - NO colocar el token en otros archivos PHP.
 * - Cuando Bellavista FC pase a producción, aquí podremos
 *   actualizar las credenciales correspondientes.
 * ================================================================
 */


/* ================================================================
   ACCESS TOKEN
   ================================================================ */

/*
 * Pega aquí el NUEVO token seguro que generaste en Meta.
 *
 * NO me envíes este valor.
 *
 * Ejemplo:
 *
 * $whatsapp_token = 'EAABxxxxxxxxxxxxxxxx';
 */

$whatsapp_token = 'EAAcEL8YQB00BSGxmOIEIWsv8xuecKtIh9mA2AiYaaOUudZCU851E5AjgYOZABlQ3HZCo2y46p0BAVPrYDa1qkBZBUoMKIpgtAWQ2et6nUK8CiSyPQZA1AYvp7FIweJii6HV3Sk1ZBZC69uhQ7IiZAuZCgg4oQOyK8fSwZB3dbjsQRogMRtpOuKYpUBByigybewlwZDZD';


/* ================================================================
   PHONE NUMBER ID
   ================================================================ */

/*
 * ID del número de teléfono de prueba proporcionado por Meta.
 *
 * Número de prueba:
 * +1 555 661 5607
 *
 * Phone Number ID:
 * 1226269583909066
 */

$whatsapp_phone_number_id = '1226269583909066';


/* ================================================================
   WHATSAPP BUSINESS ACCOUNT ID
   ================================================================ */

/*
 * ID de la cuenta de WhatsApp Business.
 *
 * WABA ID:
 * 2949087842135861
 *
 * Actualmente lo dejamos documentado porque posteriormente
 * lo necesitaremos para otras operaciones de la API.
 */

$whatsapp_business_account_id = '2949087842135861';


/* ================================================================
   VERSIÓN DE GRAPH API
   ================================================================ */

/*
 * Versión de Graph API utilizada por nuestra integración.
 *
 * Si Meta cambia posteriormente la versión recomendada,
 * podremos actualizar únicamente este valor.
 */

$whatsapp_api_version = 'v25.0';


/* ================================================================
   URL DE LA API
   ================================================================ */

/*
 * URL utilizada para enviar mensajes mediante WhatsApp Cloud API.
 *
 * No modificar esta URL manualmente.
 */

$whatsapp_api_url =
    'https://graph.facebook.com/' .
    $whatsapp_api_version .
    '/' .
    $whatsapp_phone_number_id .
    '/messages';


/* ================================================================
   CONFIGURACIÓN GENERAL
   ================================================================ */

/*
 * Tiempo máximo de espera de una solicitud hacia Meta.
 *
 * Está expresado en segundos.
 */

$whatsapp_timeout = 30;


/*
 * Indicador para identificar que estamos trabajando
 * actualmente con el número de prueba.
 *
 * true  = demostración/pruebas
 * false = producción
 *
 * Por ahora DEBE permanecer en true.
 */

$whatsapp_modo_prueba = true;


/* ================================================================
   FIN DE LA CONFIGURACIÓN
   ================================================================ */