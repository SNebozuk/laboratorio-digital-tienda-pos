# Laboratorio Digital — Tienda online y POS

Sistema centralizado para venta mayorista, pedidos online con retiro en el local, ventas de mostrador y control de stock.

## Estado actual

**Versión 0.3 — MVP funcional en validación local.** La versión activa utiliza
una base centralizada, servicios transaccionales y pantallas separadas para
clientes y administración. Los cambios más recientes todavía no fueron
desplegados a DonWeb.

### Aplicación activa

La implementación que se debe continuar es:

- `v1/`: tienda pública conectada a datos reales.
- `v1/admin/`: administración y POS separados.
- `api.php`: acciones de catálogo, pedidos, productos, POS y pagos.
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
- Carrito persistente al cerrar o recargar el mismo navegador.
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
- Lector activo en toda la vista del POS: agrega códigos conocidos y abre el
  buscador de asignación para códigos nuevos.
- Carrito POS persistente por usuario en el mismo navegador.
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
- Usuarios administradores y vendedores.
- Configuración de transferencia, plazos, WhatsApp y retiro desde administración.

La infraestructura inicial de caja, reportes y respaldos existe parcialmente,
pero sus pantallas y su integración operativa todavía deben completarse. Ver
`AGENTS.md` para el estado exacto y el orden recomendado.

## Regla central de stock

Existe una sola fuente de stock para tienda pública, pedidos web y POS. Agregar al carrito solo descuenta visualmente la disponibilidad de esa sesión. Al subir un comprobante se valida nuevamente y se reserva el stock. La venta de mostrador descuenta el stock al cobrar. Una cancelación libera las unidades comprometidas.

## Arquitectura prevista

Primera versión monolítica en PHP 8.1+ con SQLite, preparada para DonWeb Cloud.
Los comprobantes y respaldos se almacenan fuera del acceso público y solo
pueden administrarse con sesión. Los correos se encolan con cada cambio de
estado y se procesan mediante una tarea programada; deben activarse únicamente
después de validar la casilla y la entrega desde DonWeb.

La facturación fiscal permanece fuera del alcance hasta que se confirme su integración.

## Prevalidación opcional de comprobantes

El sistema puede leer JPG, PNG o PDF con OpenAI y comparar importe,
destinatario, fecha y referencia con el pedido. El resultado es solamente una
ayuda para la administración: nunca aprueba el pago ni reemplaza la verificación
de la acreditación bancaria.

La integración queda desactivada si no hay credenciales. En el servidor se
configura con `APP_RECEIPT_AI_ENABLED=true`, `OPENAI_API_KEY` y, de forma
opcional, `APP_RECEIPT_AI_MODEL`. Estas credenciales no deben subirse a Git.

## Estructura del repositorio

```text
app/        Servicios de negocio, seguridad y acceso a datos.
database/   Esquema versionado de SQLite.
storage/    Datos privados ignorados por Git.
tasks/      Mantenimiento periódico para vencimientos y correos.
v1/         Tienda pública funcional.
v1/admin/   Productos, POS, pedidos, usuarios y configuración.
outputs/    Modelos navegables y documentación funcional.
index.php   Página temporal de mantenimiento de artjet.com.ar.
preview.html Acceso local a los modelos.
```

## Continuar desde otra computadora

1. Iniciar sesión en GitHub y clonar este repositorio.
2. Leer `AGENTS.md`, que contiene el estado técnico para otra cuenta de Codex.
3. Copiar `config.example.php` como `config.local.php` y completar solo datos de
   ensayo; nunca subir ese archivo.
4. Ejecutar `php -S 0.0.0.0:8000 -t .` desde la raíz.
5. Abrir `http://127.0.0.1:8000/v1/` y
   `http://127.0.0.1:8000/v1/admin/`.
6. Ejecutar las comprobaciones indicadas en `AGENTS.md` antes de cada commit.

## Seguridad del repositorio

No deben subirse bases de datos reales, comprobantes, claves SMTP, contraseñas ni archivos `.env`. Las reglas de `.gitignore` ya excluyen esos elementos. Consultar `SECURITY.md` antes de conectar datos reales.
