# Los Animales

Sitio estatico (`index.html`) con un CMS propio en PHP bajo `cms/`.

## Despliegue (CapRover)

`captain-definition` usa el `Dockerfile` de la raiz, basado en `php:8.2-apache`, porque el CMS
necesita PHP (con `nginx:alpine` los `.php` se servian como texto plano).

1. **Persistent directory**: monta un volumen en `/var/www/data` (paginas editadas, respaldos,
   sesiones y archivos subidos). Sin el volumen todo se pierde en cada deploy.
2. **Variables de entorno**: `CMS_ADMIN_USER`, `CMS_ADMIN_PASSWORD_HASH`
   (`php -r 'echo password_hash("tu-clave", PASSWORD_BCRYPT);'`) y `CMS_DATA_DIR`.

## Desarrollo local

    CMS_DATA_DIR=/tmp/cmsdata php -S 127.0.0.1:8099
