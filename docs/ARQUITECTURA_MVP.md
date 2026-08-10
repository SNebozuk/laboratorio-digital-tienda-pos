# Arquitectura del MVP

> Este documento describe la arquitectura objetivo. Para distinguir lo que ya
> funciona de los flujos todavía parciales, consultar primero `AGENTS.md`.

## Objetivo

La primera versión productiva debe resolver catálogo, pedidos web, POS y stock
con la menor cantidad posible de componentes. La prioridad es que una operación
de stock no pueda duplicarse ni quedar a mitad de camino.

La aplicación se implementa como un monolito modular en PHP 8.1 o superior con
SQLite. Es una arquitectura adecuada para un solo comercio, una sucursal y un
volumen inicial moderado. Si el uso crece, el diseño permite migrar la conexión
de datos a MariaDB sin cambiar la experiencia de usuario.

## Superficies separadas

### Tienda pública

Ruta de prueba: `/v1/`

- No contiene navegación, enlaces ni componentes de administración.
- Catálogo continuo con categorías como atajos laterales.
- Búsqueda predictiva con miniatura.
- Producto mostrado una sola vez y variantes dentro de la misma fila.
- Stock disponible por variante.
- Cantidad editable con botones `+`, `−` o escritura manual.
- Resumen del pedido fijo a la derecha y adaptable a celular.
- El SKU no se expone al cliente.
- La descripción aparece únicamente al abrir el producto.
- Cada pedido tiene un enlace personal de seguimiento para retomar la carga del
  comprobante sin exponer datos del cliente ni comprobantes anteriores.

### Administración y POS

Ruta de prueba: `/v1/admin/`

- Inicio de sesión independiente.
- Menú lateral.
- Productos agrupados con edición directa de precio y stock por variante.
- Alta de un producto con varias variantes.
- Duplicación segura con stock cero y códigos nuevos.
- POS con el mismo criterio de búsqueda que el catálogo.
- Venta con varios productos, cantidades editables y comprobante imprimible.
- Pedidos web y ventas de mostrador en un historial único.
- Edición de productos de un pedido antes de aprobar el pago, con ajuste
  transaccional de la reserva.
- Acceso protegido a comprobantes.
- Caja con apertura, ingresos, egresos y arqueo.
- Reportes de ventas, pedidos, stock bajo y movimientos.
- Usuarios con roles administrador y vendedor.
- Configuración operativa y respaldos privados.

## Fuente única de stock

Cada variante guarda dos cantidades:

- `stock_on_hand`: unidades físicas.
- `stock_reserved`: unidades reservadas por pedidos web.

La disponibilidad mostrada es:

```text
disponible = stock_on_hand - stock_reserved
```

Agregar un artículo al carrito solo cambia la simulación visual de esa sesión.
No modifica la base de datos.

Al subir un comprobante:

1. Se valida nuevamente todo el pedido.
2. Se inicia una transacción de escritura inmediata.
3. Se verifica que cada variante conserve disponibilidad suficiente.
4. Se incrementa `stock_reserved`.
5. Se registra el comprobante y el movimiento de stock.
6. El pedido pasa a `payment_reported`.
7. Todo se confirma junto o todo se revierte.

El POS descuenta `stock_on_hand` al cobrar y nunca puede usar unidades que estén
reservadas para pedidos web.

## Estados del pedido

| Estado técnico | Vista para el usuario | Efecto sobre stock |
|---|---|---|
| `pending_payment` | Pendiente de pago | Sin reserva |
| `payment_reported` | Pago informado | Reservado |
| `paid_prepare` | Pagado / preparar | Reservado |
| `ready_pickup` | Listo para retirar | Reservado |
| `delivered` | Entregado | Descuenta físico y reserva |
| `rejected` | Comprobante rechazado | Reserva durante el reintento |
| `cancelled` | Cancelado | Libera la reserva |

## Datos y seguridad

- Contraseñas almacenadas con `password_hash`.
- Cookies de sesión `HttpOnly` y `SameSite=Lax`.
- Token CSRF para todas las acciones de escritura.
- Limitación básica de intentos de acceso.
- Comprobantes validados por tipo real y tamaño.
- Archivos privados con nombres aleatorios fuera del acceso directo.
- El token público de carga se guarda únicamente como hash.
- Al rechazar un comprobante se rota el token: el enlace anterior deja de ser
  válido y el nuevo se entrega por email.
- Auditoría de pedidos y movimientos de stock.
- Consultas preparadas y restricciones en la propia base de datos.
- Base, comprobantes, configuración local y copias excluidos de Git.

## Procesos programados

`tasks/maintenance.php` se ejecuta por línea de comandos cada minuto:

1. Cancela pedidos cuyo plazo de pago venció.
2. Libera reservas rechazadas cuando vence el reintento.
3. Procesa la cola de correos.
4. Reintenta temporalmente los mensajes fallidos y deja trazabilidad.

Los correos salen desde `ventas@laboratorio-digital.com.ar` únicamente cuando
`mail_enabled` está activo en la configuración privada del servidor.

## Respaldos

La administración puede crear un respaldo consistente mediante `VACUUM INTO`.
Cada copia contiene:

- base SQLite completa;
- comprobantes privados;
- manifiesto con fecha, tamaño y hash SHA-256 de la base.

Los respaldos quedan en `storage/backups`, fuera de Git y protegidos del acceso
web. Además se debe conservar una copia externa al servidor.

## Estructura

```text
app/                 Servicios de negocio y acceso a datos
database/schema.sql  Esquema versionado
storage/             Base y comprobantes privados
tests/               Contratos automáticos del esquema
v1/                  Nueva tienda pública
v1/admin/            Nueva administración y POS
api.php              API única para ambas superficies
config.example.php   Configuración segura de referencia
outputs/             Modelos navegables aprobados
```

## Publicación progresiva

La versión nueva se mantiene en `/v1/` mientras se valida. El `index.php`
existente y los modelos no se reemplazan. Cuando las pruebas funcionales estén
aprobadas:

1. Se crea una copia de seguridad.
2. Se publica `/v1/` en un entorno protegido.
3. Se cargan pocos productos reales.
4. Se prueban pedido, comprobante, reserva, aprobación, POS y arqueo.
5. Se configura correo SMTP.
6. Se mueve la nueva tienda a la raíz.

## Límites conscientes de la primera versión

- Una sola sucursal y una sola fuente de stock.
- Sin facturación fiscal hasta confirmar el alcance.
- Sin envíos: retiro en el local.
- Sin cobro automático: la transferencia se verifica manualmente.
- Sin integración directa con Tiendanube en producción inicial; la importación
  se realizará como proceso controlado.
