# Guía de continuidad para Codex

Este repositorio contiene el ecommerce mayorista, sistema de pedidos y punto de
venta de Laboratorio Digital. Antes de modificar código, leer este archivo,
`README.md` y `docs/ARQUITECTURA_MVP.md`.

## Estado funcional actual

La implementación activa está en `v1/` y utiliza una única base SQLite:

- Tienda pública: `/v1/`.
- Administración y POS: `/v1/admin/`.
- API compartida: `/api.php`.
- Servicios de negocio: `/app/`.
- Esquema y catálogo inicial: `/database/`.

Funcionan actualmente:

- catálogo público, búsqueda global, productos agrupados y variantes;
- carrito público persistente en el mismo navegador;
- pedido web, transferencia, comprobante y reserva atómica de stock;
- seguimiento del pedido mediante enlace privado;
- administración de productos, variantes, usuarios y configuración;
- historial y edición de pedidos, revisión de pagos e impresión;
- POS con carrito persistente, búsqueda y cantidades múltiples;
- lector de código activo en toda la vista del POS;
- asignación asistida cuando un código todavía no pertenece a una variante;
- prevalidación opcional de comprobantes con IA, siempre sujeta a revisión humana.

No considerar terminados todavía:

- la interfaz completa de caja y arqueos;
- la vinculación obligatoria de cada venta POS con una caja abierta;
- las pantallas finales de reportes y movimientos;
- la interfaz administrativa de creación y restauración de respaldos;
- el envío real de emails en DonWeb;
- la automatización del despliegue desde GitHub;
- la integración fiscal, que sigue fuera de alcance.

Existen servicios o código preliminar para algunas de esas funciones, pero no
equivalen a un flujo completo y probado.

## Reglas de negocio que no deben romperse

1. `stock_on_hand` es stock físico y `stock_reserved` es stock comprometido.
2. Disponibilidad = `stock_on_hand - stock_reserved`.
3. El carrito solo simula el descuento; no escribe stock.
4. El pedido web se crea sin reserva.
5. La reserva se realiza al subir el comprobante y debe ser transaccional.
6. El POS descuenta stock físico únicamente al cobrar.
7. Ningún flujo puede consumir stock reservado por otro pedido.
8. Los parámetros numéricos usados en comparaciones atómicas de SQLite deben
   enlazarse con `PDO::PARAM_INT`. No volver a usar `execute([...])` para esos
   parámetros: produjo falsos errores de falta de stock.
9. La tienda pública no debe mostrar enlaces ni componentes administrativos.
10. La IA nunca aprueba pagos automáticamente.

## Inicio local

Requisitos: PHP 8.1 o superior con `pdo_sqlite` y `fileinfo`.

```powershell
Copy-Item config.example.php config.local.php
php -S 0.0.0.0:8000 -t .
```

Para una prueba local se debe editar `config.local.php`, usar una URL local y
activar `orders_enabled`. Ese archivo es privado y está ignorado por Git.

Abrir:

- `http://127.0.0.1:8000/v1/`
- `http://127.0.0.1:8000/v1/admin/`

En Codespaces usar el mismo comando y publicar el puerto 8000 desde la pestaña
**Ports**. GitHub entregará una URL `https://<codespace>-8000.app.github.dev`.

## Verificación mínima antes de guardar cambios

```powershell
php -l app/OrderService.php
php -l app/StockService.php
php -l v1/index.php
php -l v1/admin/index.php
node --check v1/assets/store.js
node --check v1/admin/assets/admin.js
python tests/test_schema.py
git diff --check
```

Además, probar en navegador el flujo afectado. Para stock, usar una copia de la
base o datos de ensayo y comprobar tanto la reserva web como el cobro POS.

## Seguridad y Git

- No subir `config.local.php`, `.env`, bases SQLite, comprobantes, respaldos,
  contraseñas, claves SMTP ni claves de OpenAI.
- `storage/`, `work/` y `git-history/` son locales e ignorados.
- En un clon normal se usan comandos Git habituales. `git-history/` solo es el
  directorio Git separado de este workspace local.
- Trabajar desde el último `main` de GitHub y revisar `git status` antes de editar.
- No desplegar a DonWeb ni modificar DNS sin autorización explícita del usuario.

## Próximo orden recomendado

1. Completar caja y asociar ventas POS a una sesión abierta.
2. Activar pantallas de reportes, movimientos y respaldos.
3. Ejecutar una prueba integral con comprobante de ensayo y restauración de copia.
4. Configurar email real y tarea programada en DonWeb.
5. Recién después preparar despliegue controlado de la versión nueva.

