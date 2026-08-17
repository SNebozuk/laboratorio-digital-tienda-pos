<?php
declare(strict_types=1);

/*
 * Copiar este archivo en el servidor como config.mail.local.php.
 * Ese nombre está ignorado por Git: nunca publicar este archivo ni sus claves.
 *
 * SES envía; la casilla ventas@laboratorio-digital.com.ar sigue recibiendo y
 * respondiendo normalmente donde ya está alojada. No hay cambios de MX aquí.
 */
return [
    // Primero dejar false, guardar y usar “Prueba de Amazon SES” en el admin.
    // Cambiar a true únicamente cuando la prueba llegue correctamente.
    'mail_enabled' => false,
    'mail_transport' => 'ses_smtp',
    'mail_from' => 'ventas@laboratorio-digital.com.ar',
    'mail_from_name' => 'Laboratorio Digital',
    'mail_reply_to' => 'ventas@laboratorio-digital.com.ar',
    'sales_notification_email' => 'ventas@laboratorio-digital.com.ar',

    // Datos que entrega Amazon SES > SMTP settings, todos de la misma región.
    'mail_smtp_host' => 'email-smtp.sa-east-1.amazonaws.com',
    'mail_smtp_port' => 587,
    'mail_smtp_encryption' => 'tls',
    'mail_smtp_username' => 'PEGAR-USUARIO-SMTP-SES',
    'mail_smtp_password' => 'PEGAR-CONTRASENA-SMTP-SES',
];
