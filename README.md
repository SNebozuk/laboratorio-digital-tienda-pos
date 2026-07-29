# Laboratorio Digital — Tienda online y POS

Sistema centralizado para venta mayorista, pedidos online con retiro en el local y ventas de mostrador.

## Experiencia del cliente

- Tienda pública separada de la administración.
- Lista continua de productos, orientada a precio, stock y practicidad.
- Búsqueda predictiva con imágenes y coincidencias por producto, talle o código interno.
- Variantes con stock visible y selector directo de cantidad.
- Descripción disponible únicamente al abrir el detalle del producto.
- El SKU se utiliza internamente, pero no se muestra públicamente.
- Carrito con múltiples productos y variantes.
- Pago web por transferencia bancaria.
- Carga de comprobante JPG, PNG o PDF.
- El stock se reserva al subir el comprobante, después de una nueva validación.
- Confirmaciones por email desde `ventas@laboratorio-digital.com.ar`.
- Retiro exclusivo en el local, sin envíos.

## Administración y punto de venta

- Menú lateral separado de la tienda pública.
- POS con búsqueda predictiva y lector de código de barras.
- Ventas con múltiples productos y comprobante interno imprimible.
- Alta, edición y duplicación de productos.
- Cada producto admite múltiples variantes con precio, stock y código propios.
- Órdenes web y ventas de mostrador en un historial único.
- Edición e impresión de órdenes de venta.
- Estados: pendiente de comprobante, pago informado, pagado/preparar, listo para retirar, entregado y cancelado.
- Aprobación manual de transferencias y auditoría.
- Caja, movimientos de stock, reportes básicos y respaldos.

## Regla central de stock

Existe una sola fuente de stock para tienda pública, pedidos web y POS. Agregar al carrito no reserva mercadería. Al subir un comprobante se valida nuevamente la disponibilidad y se reserva el stock. La venta de mostrador descuenta el stock al cobrar. Una cancelación libera las unidades comprometidas.

## Arquitectura prevista

Primera versión monolítica en PHP 8.1+ con SQLite, preparada para DonWeb Cloud. Los comprobantes se almacenan fuera del acceso público y solo pueden abrirse desde administración. Los correos reales requieren SMTP autenticado, SPF y DKIM para `ventas@laboratorio-digital.com.ar`.

La facturación fiscal permanece fuera del alcance hasta que se confirme su integración.

## Modelos navegables

- `outputs/modelo-navegable.html`: tienda pública.
- `outputs/modelo-administracion.html`: administración y POS.
