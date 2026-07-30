# Laboratorio Digital — Tienda online y POS

Sistema centralizado para venta mayorista, pedidos online con retiro en el local, ventas de mostrador y control de stock.

## Estado actual

**Versión 0.2 — base funcional en validación.** El modelo navegable aprobado se
conserva como referencia y la nueva versión ya utiliza una base de datos
centralizada, servicios transaccionales y pantallas separadas para clientes y
administración.

### Base productiva

La implementación estable se está construyendo en paralelo, sin reemplazar el
modelo publicado:

- `v1/`: tienda pública conectada a datos reales.
- `v1/admin/`: administración y POS separados.
- `api.php`: acciones de catálogo, pedidos, productos, POS, pagos y caja.
- `database/schema.sql`: fuente única de productos, variantes y stock.
- `app/`: servicios transaccionales y seguridad.
- `tests/`: contratos automáticos de integridad.
- `tasks/maintenance.php`: vencimiento de pedidos y envío de correos por tarea programada.

El stock físico y el reservado se almacenan por separado. Un pedido web no
reserva al confirmar el carrito: la reserva se ejecuta de manera atómica cuando
el cliente sube el comprobante. Consultar `docs/ARQUITECTURA_MVP.md` y
`docs/PLAN_DE_TRABAJO.md`.

## Abrir los modelos

- `outputs/modelo-navegable.html`: tienda pública para clientes.
- `outputs/modelo-administracion.html`: administración y punto de venta.
- `preview.html`: acceso rápido a los modelos.

Los archivos HTML pueden abrirse directamente. Para probar la entrada PHP se necesita PHP 8.1 o superior.

## Experiencia del cliente

- Tienda pública completamente separada de la administración: el archivo del catálogo no incluye el menú ni las pantallas administrativas.
- Menú lateral basado en la estructura actual de Tiendanube.
- Lista continua de productos orientada a precio, stock y practicidad.
- Búsqueda predictiva con imágenes y coincidencias por producto, talle o código interno.
- El buscador queda vacío al seleccionar un producto para facilitar la siguiente búsqueda.
- Títulos de productos en mayúsculas.
- Variantes con stock visible y selector directo de cantidad.
- Descripción disponible únicamente al abrir el detalle del producto.
- El SKU se utiliza internamente, pero no se muestra públicamente.
- Carrito con múltiples productos y variantes.
- Pago web por transferencia bancaria.
- Carga de comprobante JPG, PNG o PDF.
- Página personal de seguimiento para retomar la carga desde el email.
- Reserva de stock al informar el pago, después de validar nuevamente la disponibilidad.
- Si un comprobante es rechazado, el enlace anterior se invalida y se envía uno
  nuevo para reintentar dentro del plazo configurado.
- Confirmaciones por email desde `ventas@laboratorio-digital.com.ar`.
- Confirmación final con el mensaje “Su pedido ha sido enviado” y acceso a WhatsApp para compartir únicamente el detalle del pedido.
- Retiro exclusivo en el local, sin envíos.

## Administración y punto de venta

- Menú lateral separado de la tienda pública.
- POS con búsqueda predictiva y lector de código de barras.
- Ventas con múltiples productos y comprobante interno imprimible.
- Alta, edición y duplicación de productos.
- Múltiples variantes por producto, cada una con precio, stock y código propios.
- Órdenes web y ventas de mostrador en un historial único.
- Edición de órdenes con alta, eliminación y cambio de cantidad de productos.
- Impresión de órdenes de venta.
- Acceso directo al comprobante de pago desde la lista de ventas.
- Aviso por WhatsApp al `+54 9 341 569-9338` con el detalle completo del pedido.
- Estados: pendiente de pago, pago informado, pagado/preparar, listo para retirar, entregado y cancelado.
- Aprobación manual de transferencias y auditoría.
- Caja, movimientos de stock, reportes básicos y respaldos.
- Usuarios administradores y vendedores.
- Configuración de transferencia, plazos, WhatsApp y retiro desde administración.
- Respaldo consistente de base y comprobantes desde el reporte operativo.

## Regla central de stock

Existe una sola fuente de stock para tienda pública, pedidos web y POS. Agregar al carrito solo descuenta visualmente la disponibilidad de esa sesión. Al subir un comprobante se valida nuevamente y se reserva el stock. La venta de mostrador descuenta el stock al cobrar. Una cancelación libera las unidades comprometidas.

## Arquitectura prevista

Primera versión monolítica en PHP 8.1+ con SQLite, preparada para DonWeb Cloud.
Los comprobantes y respaldos se almacenan fuera del acceso público y solo
pueden administrarse con sesión. Los correos se encolan con cada cambio de
estado y se procesan mediante una tarea programada; deben activarse únicamente
después de validar la casilla y la entrega desde DonWeb.

La facturación fiscal permanece fuera del alcance hasta que se confirme su integración.

## Estructura del repositorio

```text
app/        Servicios de negocio, seguridad y acceso a datos.
database/   Esquema versionado de SQLite.
storage/    Datos privados ignorados por Git.
tasks/      Mantenimiento periódico para vencimientos y correos.
v1/         Tienda pública funcional.
v1/admin/   Administración, POS, caja y reportes.
outputs/    Modelos navegables y documentación funcional.
index.php   Página temporal de mantenimiento de artjet.com.ar.
preview.html Acceso local a los modelos.
```

## Continuar desde otra computadora

1. Iniciar sesión en GitHub con la cuenta propietaria.
2. Abrir este repositorio privado.
3. Usar **Code → Open with GitHub Desktop** o clonar el repositorio.
4. Trabajar sobre una rama nueva cuando comience una función grande.
5. Integrar en `main` únicamente versiones revisadas y estables.

## Seguridad del repositorio

No deben subirse bases de datos reales, comprobantes, claves SMTP, contraseñas ni archivos `.env`. Las reglas de `.gitignore` ya excluyen esos elementos. Consultar `SECURITY.md` antes de conectar datos reales.
