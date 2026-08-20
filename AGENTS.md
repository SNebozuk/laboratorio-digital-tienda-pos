# Laboratorio Digital — contexto vigente

## Reglas para futuros chats de Codex

- El código actual es la fuente de verdad. Ante cualquier diferencia con conversaciones, documentos o recuerdos anteriores, prevalece el código.
- Leer este archivo al comenzar una tarea.
- Trabajar solamente sobre lo solicitado.
- No volver sobre funcionalidades ya confirmadas y funcionando salvo pedido explícito.
- No recuperar comportamientos ni requisitos antiguos.
- No implementar ideas provenientes del historial.
- Leer únicamente los archivos necesarios para la tarea.
- Evitar recorrer todo el repositorio para cambios localizados.
- Hacer el cambio mínimo necesario.
- No realizar refactors no solicitados.
- No realizar mejoras adicionales por iniciativa propia.
- Evitar análisis y verificaciones redundantes.
- Ejecutar únicamente las pruebas relevantes al cambio.
- Evitar subagentes salvo necesidad real.
- Mantener las respuestas finales breves.
- No modificar secretos, DNS, credenciales ni configuración privada del servidor sin autorización explícita.
- Antes de publicar, revisar el diff y preservar los archivos locales/ignorados que no sean parte de la tarea.

## Flujo permanente de trabajo

Para cada pedido seguir este orden:

1. Leer `AGENTS.md`.
2. Trabajar únicamente sobre lo solicitado y leer solo los archivos relacionados.
3. Hacer el cambio mínimo necesario, sin refactors ni mejoras no pedidas.
4. Validar únicamente los archivos modificados con las comprobaciones relevantes.
5. Si la tarea terminó y la validación fue correcta, revisar solo sus archivos, crear un commit corto y descriptivo, y ejecutar `git push` al remoto y rama actualmente configurados.
6. No hacer commit ni push si la tarea quedó incompleta, hubo un error de validación importante o el usuario pidió explícitamente no hacerlo.
7. No cambiar de rama ni de remoto salvo pedido explícito.
8. Responder muy brevemente indicando qué se modificó, si validó, si se hizo commit y si se hizo push.

Regla permanente: **cambio terminado + validación correcta = commit + push automático**.

Antes del commit, incluir exclusivamente los archivos de la tarea actual. El árbol de trabajo puede contener cambios ajenos del usuario y deben preservarse sin incorporarlos, modificarlos ni descartarlos.

## Modelo preferido

- Usar normalmente `gpt-5.6-terra` con razonamiento `low`; el proyecto también lo fija en `.codex/config.toml` para repositorios de confianza.
- Aumentar el razonamiento solo cuando la tarea realmente lo necesite.
- No usar `gpt-5.6-sol` por defecto. Si una tarea parece requerirlo imprescindiblemente, avisar primero y explicar brevemente por qué.
- Si la interfaz ignora la configuración del proyecto o aplica una selección explícita al chat, elegir manualmente **Terra** y **Low/Bajo** en el control de modelo y razonamiento situado debajo del cuadro de mensaje al iniciar el chat.

## Propósito y URLs

Laboratorio Digital es un catálogo mayorista con pedidos web, administración interna, punto de venta y una planilla de preparación de entregas.

- Tienda pública de producción: `https://laboratoriodigital.com.ar/`
- Administración: `https://laboratoriodigital.com.ar/v1/admin/`
- Punto de venta: `https://laboratoriodigital.com.ar/v1/admin/pos.php`

El código público vive físicamente en `v1/`. En producción `public_store_path` puede ser vacío para servir la tienda desde la raíz; no asumir rutas a partir de `config.example.php`.

## Arquitectura

- PHP 8.1+, sin framework, con SQLite (`pdo_sqlite`) y JavaScript/CSS sin compilación.
- `app/bootstrap.php` inicializa sesión, configuración, CSRF y base de datos.
- `app/container.php` registra servicios y es el punto común de entrada para páginas y API.
- `api.php` es la API JSON principal; `v1/api.php` la reexpone desde la ruta pública.
- `database/schema.sql` define una base nueva. `app/Database.php` aplica migraciones y usa WAL con transacciones inmediatas para operaciones críticas.
- La información operativa persistente vive en SQLite, fuera de Git; las fotos de producto se guardan bajo `v1/uploads/products/` en el hosting.

## Módulos principales

| Área | Archivos principales |
| --- | --- |
| Tienda | `v1/index.php`, `v1/assets/store.js`, `v1/assets/light.css` |
| Admin | `v1/admin/index.php`, `v1/admin/assets/admin.js`, `v1/admin/assets/admin.css` |
| PDV | `v1/admin/pos.php`, `v1/admin/pos-products.php` |
| Impresión | `v1/admin/receipt.php`, `v1/admin/assets/receipt.css`, `v1/admin/assets/receipt.js` |
| Pedidos y stock | `app/OrderService.php`, `app/StockService.php` |
| Productos y fotos | `app/ProductService.php`, `app/ProductImageService.php`, `app/CategoryService.php` |
| Entregas | `app/DeliveryService.php` |
| Configuración de tienda | `app/SettingsService.php` |
| Usuarios | `app/Auth.php` |
| Correo | `app/MailService.php` |
| Respaldo y tarea programada | `app/BackupService.php`, `tasks/maintenance.php` |

## Tienda pública

- Catálogo con búsqueda global, categorías y subcategorías desplegables, variantes, precios, stock y carrito persistente en el navegador.
- La portada usa logo, textos, imágenes y destacados configurables desde **Diseño** y **Productos** del admin.
- El checkout solicita nombre y apellido real, WhatsApp y email opcional. La venta web se confirma por transferencia; el cliente recibe los datos bancarios y puede continuar por WhatsApp.
- El carrito puede pausarse desde **Mantenimiento**: se puede navegar, pero no agregar ni confirmar productos.
- Contacto, horario, WhatsApp, ubicación, tabla de talles y logo se obtienen de configuración/ajustes actuales.

## Administración

El admin requiere sesión. Roles: `admin` y `seller`; las secciones de configuración y mantenimiento son administrativas.

Secciones vigentes:

- **Lista de Ventas:** búsqueda, filtros actuales, detalle, impresión, WhatsApp, archivar, cancelar y reabrir. El contador lateral muestra ventas abiertas.
- **Entrega de pedidos:** planilla fija de 100 filas. Una venta se mueve desde Lista de Ventas a una fila, queda archivada allí y no debe duplicarse entre ambas vistas. Las filas pueden contener varias órdenes separadas por `/`; el contenido actual de la fila es la única fuente para imprimir desde Entregas. Vaciar una fila solo borra la planilla, no reabre ni altera la venta original.
- **Productos:** listado, filtros, visibilidad, destacados, edición directa de precio/stock por variante, alta/edición/duplicado/borrado y enlace público.
- **Categorías:** árbol editable con orden y subcategorías.
- **Tabla de Talles, Contacto, Diseño, WhatsApp, Usuarios, Configuración y Mantenimiento:** ajustes editables del negocio y de la experiencia pública.
- **Punto de Venta:** pantalla independiente. El carrito es persistente para el usuario; la búsqueda admite nombre, variante, SKU y código de barras. Una venta de `CONSUMIDOR FINAL` queda archivada al crearse; una venta con cliente identificado exige WhatsApp y queda disponible en Lista de Ventas.

## Productos y stock

- Producto y variante pueden tener foto, precio, stock, SKU y código de barras propios. SKU y código de barras son opcionales; cuando existen son únicos.
- `stock_on_hand` es la disponibilidad real mostrada y utilizada para vender. Todas las validaciones y descuentos críticos se realizan en transacciones SQLite.
- La creación de pedido web y la venta de PDV descuentan stock de forma atómica. Cancelar una venta permite restaurarlo según la acción elegida.
- No reemplazar operaciones atómicas de stock por actualizaciones en el cliente. Los cambios manuales de stock deben seguir pasando por `ProductService`.

## Ventas, correo y WhatsApp

- Las órdenes usan número público `#…`, guardan cliente, líneas con una instantánea de producto/variante/precio y eventos de estado.
- La impresión se genera en `receipt.php`; las impresiones agrupadas no deben cortar una orden entre hojas.
- Los botones de WhatsApp preparan mensajes, pero no envían mensajes automáticamente.
- Si `mail_enabled` está activo, al crear una venta web se encolan únicamente: un detalle interno a `sales_notification_email` y, si el cliente completó email, una copia para ese cliente. `MailService` intenta enviarlos inmediatamente y `tasks/maintenance.php` procesa reintentos.
- El correo transaccional usa SMTP de Amazon SES cuando está configurado. Credenciales y valores reales permanecen exclusivamente en `config.mail.local.php` del servidor.

## Base de datos y archivos privados

- Base: ruta definida por `database_path` en `config.local.php`; normalmente `storage/app.sqlite` fuera del directorio público cuando el hosting lo permite.
- Tablas de negocio centrales: `users`, `categories`, `products`, `product_variants`, `orders`, `order_items`, `stock_movements`, `order_events`, `delivery_slots`, `settings` y `mail_queue`.
- `delivery_slots` tiene filas físicas 1–100 y control de revisión para evitar sobreescrituras concurrentes.
- Los respaldos copian SQLite y fotos de producto; la tarea programada puede crear uno diario y conserva las últimas 30 copias automáticas.
- Nunca versionar `config.local.php`, `config.mail.local.php`, bases SQLite, respaldos, archivos de `storage/` ni fotos cargadas por usuarios.

## Configuración e integraciones actuales

- **DonWeb/Ferozo:** hosting PHP, SQLite y archivos persistentes.
- **GitHub Actions:** `.github/workflows/ci.yml` valida; `.github/workflows/deploy.yml` publica `main` por FTPS usando los secretos `DONWEB_FTP_*`. No borra archivos remotos existentes (`dangerous-clean-slate: false`).
- **Amazon SES SMTP:** envío transaccional opcional, configurado solo en el servidor. Mantener MX de recepción fuera de este repositorio.
- **WhatsApp:** enlaces `wa.me` y mensajes preparados desde el admin; no hay API de envío automático.

## Validación mínima

PHP 8.4 NTS x64 está instalado de forma persistente en `C:\Users\Sergio\AppData\Local\Programs\PHP-8.4` y agregado al `PATH` del usuario. No reinstalarlo ni volver a comprobar mecanismos de instalación en tareas futuras. Si un proceso ya abierto todavía no recibió el `PATH` actualizado, usar temporalmente la ruta absoluta indicada abajo o abrir un chat nuevo.

Después de modificar archivos PHP, ejecutar únicamente una comprobación sintáctica por cada archivo PHP modificado:

```powershell
php -l ruta/al/archivo-modificado.php
# Alternativa para procesos cuyo PATH aún no se actualizó:
& 'C:\Users\Sergio\AppData\Local\Programs\PHP-8.4\php.exe' -l ruta/al/archivo-modificado.php
```

Para otros tipos de archivo, ejecutar solo la comprobación directamente relevante a lo modificado, por ejemplo `node --check` para cada JavaScript modificado. Usar `python tests/test_schema.py` únicamente cuando el cambio afecte el esquema o sus migraciones. Ejecutar `git diff --check` limitado a los archivos de la tarea cuando corresponda. No validar todo el proyecto por rutina.

## Despliegue

1. Revisar el diff de los archivos de la tarea; no incluir archivos ajenos.
2. Confirmar las validaciones mínimas relevantes.
3. Si el cambio está completo y validado, hacer commit y ejecutar `git push` sin fijar manualmente otra rama o remoto.
4. No hacer push en los casos de excepción definidos en el flujo permanente.
5. Cuando el push active el workflow **Desplegar en DonWeb**, verificar su resultado si las herramientas disponibles lo permiten. Un reintento FTPS puede ser necesario ante un timeout de red.
