# Historial de versiones

Los cambios relevantes del proyecto se registran en este archivo.

## 0.2.0 — 2026-07-30

### Incluido

- El catálogo público elimina por completo el menú y las pantallas de administración y POS.
- Menú lateral importado de la estructura pública de Tiendanube.
- Títulos de productos normalizados en mayúsculas.
- El buscador se limpia después de seleccionar un producto.
- Confirmación final “Su pedido ha sido enviado” con acceso a WhatsApp.
- El mensaje de WhatsApp comparte solamente el detalle del pedido; el comprobante queda cargado y protegido dentro de la tienda.
- El buscador cierra sus sugerencias al seleccionar un producto.
- La edición de órdenes permite agregar y eliminar productos.
- El aviso por WhatsApp abre el chat de Laboratorio Digital con el pedido completo.
- La lista de ventas permite visualizar los comprobantes de pago cargados.
- Base SQLite normalizada para productos, variantes, pedidos, caja y usuarios.
- Reserva atómica de stock al subir el comprobante.
- Edición de pedidos activos con ajuste de la reserva por diferencia.
- Administración de usuarios con roles administrador y vendedor.
- Configuración bancaria, plazos y contacto desde administración.
- Reportes de ventas, estados, stock bajo y movimientos.
- Respaldos privados de base y comprobantes con manifiesto y hash.
- Cola de correos y tarea programada para vencimientos y reintentos.
- Seguimiento público de pedidos con enlace personal enviado por email.
- Rotación del enlace al rechazar un comprobante, con reintento dentro del
  plazo configurado y reserva de stock mantenida.
- La raíz de `artjet.com.ar` queda preparada para mostrar la página temporal sin
  exponer la versión nueva.

### Pendiente de puesta en marcha

- Validar sintaxis y funcionamiento con PHP 8.1+ y `pdo_sqlite` en DonWeb.
- Crear la casilla de ventas y validar la entrega de correos.
- Programar la tarea periódica en DonWeb.
- Ejecutar prueba integral y restaurar un respaldo de ensayo.
- Importar un lote controlado de productos e imágenes.

## 0.1.0 — 2026-07-29

### Incluido

- Modelo navegable de la tienda pública.
- Modelo navegable de administración y punto de venta.
- Productos agrupados con múltiples variantes.
- Edición directa de precio y stock por variante.
- Búsqueda predictiva con miniaturas.
- Carritos con múltiples productos y resumen lateral.
- Simulación visual de disponibilidad durante el armado del pedido.
- Flujo de transferencia bancaria y comprobante.
- Impresión y edición de órdenes de venta.
- Base funcional prevista para PHP 8.1+ y SQLite en DonWeb Cloud.

### Pendiente

- Persistencia real en base de datos.
- Autenticación y permisos por usuario.
- Envío SMTP real.
- Tareas automáticas para vencimiento de reservas.
- Respaldos automáticos y pruebas de recuperación.
- Integración fiscal, únicamente si se confirma su alcance.
