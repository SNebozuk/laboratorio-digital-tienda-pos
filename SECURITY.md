# Seguridad y datos privados

Este repositorio contiene código y modelos de interfaz. No debe utilizarse para almacenar información privada del comercio o de sus clientes.

## Nunca subir

- Bases de datos reales.
- Comprobantes de transferencia.
- Contraseñas o claves de acceso.
- Credenciales SMTP.
- Tokens de servicios externos.
- Archivos `.env`.
- Copias de respaldo con datos reales.

## Almacenamiento previsto

Los comprobantes deben guardarse fuera del acceso público y entregarse únicamente después de validar la sesión y los permisos del usuario administrador. La aplicación debe comprobar extensión, tipo real, tamaño y nombre seguro para cada archivo.

El enlace público de seguimiento contiene un token aleatorio personal. La base
solo guarda su hash, la página no expone identidad del cliente ni archivos
anteriores, y el token se reemplaza cuando un comprobante es rechazado.

Las claves y credenciales deben configurarse como variables de entorno en DonWeb Cloud. Nunca deben escribirse directamente en el código.

## Antes de publicar

- Activar HTTPS.
- Configurar copias de seguridad automáticas y probar su restauración.
- Cambiar las claves iniciales.
- Limitar intentos de inicio de sesión.
- Registrar accesos, aprobaciones de pagos y movimientos de stock.
- Verificar permisos de archivos y carpetas.
