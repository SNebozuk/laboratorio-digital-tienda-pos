# Conexión de Codex con el admin

El servidor MCP `laboratorio-digital-admin` ejecuta operaciones del admin por
la conexión SSH existente `donweb-cloud`. Reutiliza los servicios PHP y SQLite
de producción. No abre puertos ni crea una API pública o credenciales nuevas.

## Operaciones

- Buscar productos con paginación y leer sus variantes, fichas y estado MeLi.
- Consultar categorías, medidas, configuración general y requisitos de MeLi.
- Guardar hasta 50 fichas por lote, sin publicar ni alterar stock.
- Calcular y guardar precios con comisiones vigentes.
- Validar hasta 10 productos, incluyendo cada talle activo con stock.
- Publicar hasta 5 productos por lote cuando el usuario lo pida expresamente.
- Mostrar/ocultar productos y reconciliar stock usando los servicios existentes.

Para editar, consultar primero `ld_products_get` y usar el `draft_revision`
devuelto como `expected_revision`. Si una ficha cambió, todo el lote de escritura
se rechaza. Se conservan los campos no incluidos en el patch, reemplazando listas
como fotos y combinando mapas como atributos. Las escrituras dejan una auditoría
en SQLite con origen `codex_ssh`. No inventar equivalencias o datos faltantes.

Ante un corte de conexión, consultar productos y publicaciones antes de volver
a solicitar una escritura. El transporte nunca repite solicitudes automáticamente.

## Uso y registro

Requiere Python 3 y el acceso SSH existente. No necesita paquetes adicionales.
Registrar con `codex mcp add laboratorio-digital-admin -- python RUTA/tools/admin-mcp.py`.
Los nuevos chats pueden cargar las herramientas MCP. En un chat ya abierto,
usar el mismo conector por consola, enviando argumentos JSON por stdin:

```powershell
'{"query":"remera infantil negro","limit":10}' | python tools/admin-mcp.py --call ld_products_search
```

El backend `tasks/admin-connector.php` acepta exclusivamente CLI. Ni el MCP ni
sus respuestas contienen tokens de MeLi, contraseñas, configuración privada o
datos de clientes. La autorización procede del acceso SSH ya configurado y
requiere un administrador activo en la base. No ofrece SQL ni comandos arbitrarios.
