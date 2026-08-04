# Plan de trabajo

## Etapa 1 — Base estable

Estado: completada en código; pendiente de validación en el PHP de DonWeb.

- Esquema de productos y variantes.
- Stock físico y reservado.
- Pedidos, comprobantes, eventos y movimientos.
- Usuarios y permisos.
- Caja y cola de emails.
- Servicios transaccionales.
- Pruebas del contrato de datos.

## Etapa 2 — Flujos principales

Estado: completada en código; pendiente de prueba integral con datos de ensayo.

- Catálogo público real conectado a la base.
- Carrito y creación de pedido.
- Transferencia y carga de comprobante.
- Reserva al informar el pago.
- Administración de productos y variantes.
- POS con lector, búsqueda y recibo.
- Revisión de pagos y estados.

## Etapa 3 — Operación diaria

Estado: parcialmente completada. La lógica interna no debe confundirse con una
pantalla administrativa terminada.

- [x] Edición de órdenes con alta, baja y cambio de cantidades antes de aprobar.
- [ ] Completar y activar las pantallas de reportes, stock bajo y movimientos.
- [x] Vencimiento automático de pedidos y reintentos.
- [x] Cola de correo desde `ventas@laboratorio-digital.com.ar`.
- [x] Seguimiento público seguro y reintento de comprobantes rechazados.
- [ ] Exponer y probar la creación de respaldos desde administración.
- [ ] Programar respaldo automático externo y probar restauración.
- [ ] Completar caja, arqueos y la asociación obligatoria entre POS y caja abierta.
- [ ] Importación controlada de productos e imágenes.
- [x] Usuarios administradores y vendedores.
- [x] Configuración operativa desde administración.

## Etapa 4 — Puesta en marcha

Estado: en preparación. La versión local más reciente no está desplegada en
DonWeb y no debe publicarse sin una prueba integral y autorización explícita.

- Validar PHP y SQLite en DonWeb.
- Configurar almacenamiento privado.
- Crear administrador inicial.
- Configurar cuenta bancaria y datos de retiro.
- Configurar SMTP, SPF y DKIM.
- Probar HTTPS, celular, impresión y lector.
- Cargar un lote pequeño de productos.
- Ejecutar prueba completa con stock real.
- Publicar en la raíz de `artjet.com.ar`.

## Criterios mínimos para lanzar

- No puede venderse una unidad ya reservada.
- Cancelar o vencer un pedido libera exactamente su reserva.
- Entregar descuenta una sola vez el stock físico.
- El comprobante no es accesible sin sesión.
- El catálogo no contiene administración.
- Toda venta de mostrador queda asociada a una caja abierta.
- Los recibos detallan variantes, cantidades, precios y total.
- Existe una copia restaurable de la base y los comprobantes.
