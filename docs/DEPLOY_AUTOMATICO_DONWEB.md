# Deploy automático a DonWeb

La fuente oficial es GitHub. El workflow `.github/workflows/deploy.yml` publica los cambios de la rama principal mediante FTP y no requiere subir archivos desde una computadora.

## Datos que hay que obtener en DonWeb

En **Mi Cuenta DonWeb → tu servicio de Hosting → Accesos / FTP** buscá los datos de una cuenta FTP o FTPS. No uses la clave de la casilla de email: es una cuenta distinta.

Antes de activar el deploy, confirmá con el Administrador de archivos qué carpeta contiene actualmente `index.php`, `api.php`, `app/` y `v1/`. Esa ruta exacta es la carpeta remota. En muchos planes es `/public_html/`, pero no debe suponerse.

## Secrets de GitHub

En el repositorio abrir **Settings → Secrets and variables → Actions → New repository secret**. Crear estos cinco secretos, sin comillas:

| Secret | Valor |
| --- | --- |
| `DONWEB_FTP_SERVER` | servidor FTP/FTPS informado por DonWeb |
| `DONWEB_FTP_USERNAME` | usuario FTP/FTPS |
| `DONWEB_FTP_PASSWORD` | contraseña FTP/FTPS |
| `DONWEB_FTP_PORT` | puerto FTP informado por DonWeb (21) |
| `DONWEB_FTP_REMOTE_DIR` | carpeta remota exacta, terminada en `/` |

DonWeb informa que su Web Hosting utiliza FTP por el puerto 21. El workflow usa ese protocolo para ser compatible con el servicio actual.

## Datos persistentes protegidos

El deploy es incremental y nunca usa una limpieza total del servidor. Además, excluye la configuración privada (`config.local.php`), base SQLite, comprobantes, imágenes cargadas, subidas de productos, respaldos, logs y directorios de trabajo.

No se ejecutan migraciones, imports ni restauraciones de base durante el deploy. El archivo `database/schema.sql` se publica porque la aplicación lo necesita al iniciar, pero `database/catalog_seed.sql` queda excluido y no se importa una base de datos local.

## Qué ocurre con cada push

Al hacer push a la rama principal, GitHub Actions primero verifica que existan los cinco Secrets. Si falta alguno, finaliza sin conectar ni cambiar nada en DonWeb. Cuando están cargados, sincroniza únicamente los archivos de código modificados hacia la carpeta remota configurada.

Para volver a una versión anterior, restaurá o revertí el commit correspondiente y hacé push: GitHub Actions volverá a publicar esa versión.
