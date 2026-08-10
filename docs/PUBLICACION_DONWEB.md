# Preparación para DonWeb

## Objetivo

Publicar primero la página de mantenimiento en `artjet.com.ar` y validar la
aplicación nueva en `/v1/` sin exponer la administración desde la tienda.

## Requisitos del servicio

- PHP 8.1 o superior.
- Extensiones `pdo_sqlite` y `fileinfo`.
- Funciones de sesión habilitadas.
- Carpeta privada con permisos de escritura para SQLite, comprobantes y copias.
- Tarea programada cada cinco minutos.
- Casilla `ventas@laboratorio-digital.com.ar` creada y validada.

## Archivos privados

Copiar `config.example.php` como `config.local.php` en el servidor y completar:

- URL pública.
- ruta pública de la tienda (`/v1` durante la prueba y vacía al lanzarla en la raíz);
- ruta privada de almacenamiento;
- clave larga para crear el primer administrador;
- remitente y credenciales SMTP autenticadas de correo.

La base, los comprobantes, la configuración privada y los respaldos no deben
subirse a GitHub.

## Publicación progresiva

1. Mantener `index.php` en la raíz: muestra la página temporal.
2. Subir el código nuevo sin `storage`, `config.local.php` ni datos reales.
3. Crear una carpeta de almacenamiento fuera de `public_html` cuando el plan lo
   permita.
4. Abrir `/v1/admin/` y crear el primer administrador.
5. Configurar transferencia, plazos, WhatsApp y dirección de retiro.
6. Cargar dos o tres productos de prueba con variantes.
7. Abrir caja y probar una venta de mostrador.
8. Crear un pedido web, subir comprobante, aprobarlo y entregarlo.
9. Rechazar un comprobante de ensayo y verificar que el nuevo enlace recibido
   por email permita cargar otro archivo sin duplicar la reserva.
10. Crear un respaldo desde Reportes.
11. Programar por cron cada minuto para cancelar con precisión las reservas en efectivo:

```text
php /ruta/privada/al/proyecto/tasks/maintenance.php
```

12. Crear y validar la casilla de ventas, SPF y DKIM. Configurar en `config.local.php` el servidor SMTP, puerto, cifrado, usuario y contraseña de la casilla.
13. Activar `mail_enabled` y comprobar recepción real. La misma tarea procesa la cola de correo; para una prueba inmediata se puede ejecutar manualmente `php tasks/maintenance.php`.
14. Activar HTTPS y revisar el sitio desde celular.
15. Recién después mover la tienda desde `/v1/` a la raíz.

## Comprobaciones de aceptación

- La tienda pública no contiene enlaces de administración.
- El POS no puede vender stock reservado.
- Un comprobante reserva el stock una sola vez.
- El enlace de seguimiento no expone datos del cliente ni archivos privados.
- Rechazar un comprobante invalida el enlace anterior y habilita un reintento.
- Editar un pedido reservado ajusta únicamente la diferencia.
- Cancelar o vencer libera exactamente la reserva.
- El comprobante solo abre con sesión interna.
- Una venta de mostrador requiere caja abierta.
- Los correos se registran y reintentan sin bloquear la compra.
- El respaldo contiene base, comprobantes y manifiesto.
