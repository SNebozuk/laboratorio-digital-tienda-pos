# WhatsApp en Laboratorio Digital

Acceso administrativo: logo de WhatsApp en el lateral, vista `whatsapp-api`.
La configuración anterior de enlaces y mensajes de pedidos sigue disponible.

El módulo queda desconectado por defecto. No registra números, no modifica la
cuenta de Kauri y no ejecuta cron, análisis, búsquedas ni consultas periódicas.
La actualización es manual. Los borradores se guardan en la misma SQLite privada
de la tienda, en tablas `wa_workspace_*` creadas únicamente al acceder al módulo.

Pestañas: conversaciones, contactos recibidos, plantillas, actividad y conexión.
Soporta respuestas de texto, JPG/PNG, PDF, MP3/OGG, MP4 dentro de la ventana de
24 horas y envío manual de plantillas aprobadas con parámetros del cuerpo.
Las descargas verifican que el archivo pertenece a una conversación y consultan
una URL HTTPS de Meta desde el servidor. No se expone el token al navegador.
Los mensajes y estados se registran por webhook firmado, con deduplicación y
protección contra estados atrasados. No se importa la agenda ni el historial
anterior, ni se ofrecen grupos o llamadas.

Plantillas: categoría Utilidad/Marketing, idiomas es_AR/es/en_US/pt_BR,
encabezado y pie fijos, cuerpo con variables numéricas y ejemplos,
hasta tres respuestas rápidas. El borrador no es una aprobación. El botón
«Enviar a revisión de Meta» hace una solicitud real a `message_templates`;
los estados e identificadores provienen de Meta. Una respuesta incierta se
marca CHECK_META y no se reintenta automáticamente. Consultar Meta antes de
crear otra copia. Autenticación, cabeceras multimedia y botones dinámicos
requieren formatos específicos que este editor no construye.

## Conexión futura — requiere autorización del número nuevo

No se incorporan valores reales ni secretos en esta entrega. El cliente lee
variables exclusivas del servidor de Laboratorio Digital:

- `LD_WHATSAPP_ENABLED=true` (solo cuando se autorice activar la conexión)
- `LD_META_WHATSAPP_TOKEN`
- `LD_META_WHATSAPP_PHONE_NUMBER_ID`
- `LD_META_WHATSAPP_WABA_ID`
- `LD_META_APP_SECRET`
- `LD_META_WEBHOOK_VERIFY_TOKEN`

Callback a registrar en Meta: ruta pública `/admin/whatsapp-webhook.php` o
`/v1/admin/whatsapp-webhook.php`, según `public_store_path` del despliegue.
Suscribir `messages` en la app/WABA y comprobar firma y Phone Number ID.
La API usada es Graph v25.0. No habilitar las variables para el número de Kauri.

## Validación

`php tests/whatsapp_workspace.php` valida payloads, borradores, bloqueo sin
conexión, filtrado por número y deduplicación/orden de estados del webhook.
Se revisan sintaxis PHP/JS y la interfaz con una SQLite local de prueba.
