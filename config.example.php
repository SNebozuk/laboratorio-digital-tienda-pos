<?php
declare(strict_types=1);

/*
 * Copiar como config.local.php y completar únicamente en el servidor.
 * config.local.php está ignorado por Git y nunca debe subirse.
 */
return [
    'environment' => 'production',
    'timezone' => 'America/Argentina/Buenos_Aires',
    'base_url' => 'https://artjet.com.ar',
    // Durante la prueba queda en /v1. Al publicar en la raíz, usar ''.
    'public_store_path' => '/v1',

    // Conviene ubicar esta carpeta fuera de public_html cuando DonWeb lo permita.
    'storage_path' => __DIR__ . '/storage',
    'database_path' => __DIR__ . '/storage/app.sqlite',

    // Cadena larga y aleatoria usada una sola vez para crear el primer administrador.
    'setup_token' => 'CAMBIAR-POR-UNA-CLAVE-LARGA-Y-ALEATORIA',

    // SHA-256 de la clave privada usada por la tarea programada de DonWeb.
    'maintenance_token_hash' => 'CAMBIAR-POR-UN-HASH-SHA256',

    'session_name' => 'laboratorio_digital_session',
    'debug' => false,
    // Mantener en false hasta cargar banco, retiro, correo y stock final.
    'orders_enabled' => false,

    // Activar únicamente después de crear y validar la casilla en DonWeb.
    // Aviso interno por cada venta. No se envían emails al cliente.
    'mail_enabled' => false,
    // SMTP autenticado: las credenciales quedan solo en config.local.php.
    'mail_transport' => 'smtp',
    'mail_from' => 'pedidos@laboratoriodigital.com.ar',
    'mail_from_name' => 'Laboratorio Digital',
    'mail_reply_to' => 'ventas@laboratorio-digital.com.ar',
    'mail_smtp_host' => 'CAMBIAR-POR-EL-SERVIDOR-SMTP',
    'mail_smtp_port' => 465,
    'mail_smtp_encryption' => 'ssl',
    'mail_smtp_username' => 'pedidos@laboratoriodigital.com.ar',
    'mail_smtp_password' => 'CAMBIAR-POR-LA-CONTRASENA-DE-LA-CASILLA',
    'sales_notification_email' => 'ventas@laboratorio-digital.com.ar',

    // Ayuda a revisar comprobantes; nunca aprueba pagos automáticamente.
    // Guardar la clave solo en config.local.php o en OPENAI_API_KEY del servidor.
    'receipt_ai_enabled' => false,
    'openai_api_key' => '',
    'receipt_ai_model' => 'gpt-5.6-sol',
];
