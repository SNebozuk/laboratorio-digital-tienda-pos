# Diseño funcional — pagos por transferencia

## Decisión de arquitectura

El POS conserva su cobro inmediato y comprobante interno. El catálogo web usa exclusivamente **transferencia bancaria**. Ambos canales comparten las tablas de productos, reservas y movimientos de stock; por eso nunca existe un stock “web” separado.

La aplicación conserva el enfoque PHP + SQLite de la primera versión. Para esta ampliación se incorporan un directorio privado de comprobantes (fuera de `public_html`), una tabla de configuración y tablas de evidencias/auditoría. En DonWeb, los archivos se guardan en una ruta no pública y se entregan solo mediante un endpoint autenticado de administración.

## Recorrido del cliente

1. El cliente confirma el carrito web. El sistema valida y reserva cada unidad en una transacción.
2. Se crea el pedido con estado **Pendiente de pago**, vencimiento de reserva (valor inicial: 2 horas) y un enlace privado de carga de comprobante de un solo pedido.
3. La pantalla de confirmación muestra número de pedido, total exacto, plazo restante, alias/CBU/titular y un botón “Subir comprobante”.
4. Puede subir JPG, PNG o PDF desde celular o PC. Opcionalmente, abre WhatsApp con el número de pedido ya redactado.
5. Tras una carga válida, el pedido pasa a **Pago informado**. Esto no modifica el stock ni marca el pago como confirmado.
6. Administración revisa el comprobante protegido, agrega una observación y aprueba o rechaza.
7. Al aprobar pasa a **Pagado / preparar**; luego puede pasar a **Listo para retirar** y **Entregado**. Al rechazar, vuelve a permitir una nueva carga hasta el plazo definido.

## Estados y reglas de stock

| Estado | Significado | Stock |
| --- | --- | --- |
| Pendiente de pago | Pedido creado; sin evidencia | Reservado hasta vencimiento |
| Pago informado | Comprobante recibido | Permanece reservado |
| Pagado / preparar | Administración aprobó | Permanece reservado |
| Listo para retirar | Preparado en el local | Permanece reservado |
| Entregado | Retiro completado | Descontado definitivamente |
| Cancelado | Vencimiento, rechazo final o cancelación | Se libera de inmediato |

El proceso automático de vencimientos corre cada pocos minutos mediante una tarea programada de DonWeb. Cancela los pedidos que sigan pendientes una vez excedido el plazo. Los rechazos tienen un segundo plazo configurable para reemplazar el comprobante; cuando termina, el pedido se cancela y el stock vuelve a estar disponible.

## Administración

Pantalla “Pagos a verificar”, ordenada por antigüedad y vencimiento, con: pedido, cliente, total, comprobante, historial, observación, Aprobar/Rechazar y cambio de estado de preparación/retiro. La configuración permite editar:

- Datos bancarios visibles (titular, banco, CBU/alias).
- Plazo de pago/reserva (inicialmente 2 horas).
- Plazo para reemplazar un comprobante rechazado.
- Número de WhatsApp y texto del aviso.

Cada acción guarda auditoría: fecha, usuario, acción, estado anterior/nuevo, observación y referencia del archivo. Solo los roles administrativos pueden abrir archivos, aprobar, rechazar, cambiar plazos o descargar evidencias.

## Validación y seguridad de archivos

- Aceptar únicamente JPG, PNG y PDF; validar MIME real con `finfo`, no solo extensión.
- Límite recomendado: 8 MB; renombrar con identificador aleatorio, sin conservar el nombre enviado.
- Guardar fuera del directorio público; no servir rutas directas.
- Enlace de carga firmado, de duración limitada y asociado a un único pedido.
- Proteger contra reenvío: cada carga crea una evidencia versionada; nunca sobrescribe la anterior.
- Registrar tamaño, tipo, hash SHA-256, fecha y origen de la carga.

## Modelo de datos adicional

`settings(key, value)`: datos bancarios y plazos.

`payment_proofs(id, order_id, storage_key, original_name, mime, bytes, sha256, uploaded_at, status, reviewer_id, reviewed_at, observation)`: una o varias evidencias por pedido.

`order_events(id, order_id, actor_id, event, from_status, to_status, note, created_at)`: auditoría inmutable de pagos, reservas y entregas.

`orders`: agrega `payment_deadline`, `retry_deadline`, `public_upload_token` y los estados enumerados.

## Fuera de alcance por ahora

No se incorpora pasarela de pagos ni facturación fiscal. El comprobante impreso del POS sigue siendo interno y la transferencia web requiere verificación humana.
