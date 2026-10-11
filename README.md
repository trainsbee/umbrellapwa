# UmbrellaApp

PWA (Progressive Web App) en PHP, instalable en iPhone, iPad, Android, Windows y macOS.

## Requisitos

- PHP 8.1+ con la extension GD (solo para regenerar iconos y splash)
- Apache con `mod_rewrite`, `mod_headers`, `mod_mime` y `mod_deflate` (XAMPP ya los trae)

## Correr en local

Con XAMPP encendido, abre <http://localhost/umbrellapwa/>.

`localhost` cuenta como origen seguro, asi que el service worker funciona sin HTTPS.
Para probar desde un telefono en la red local **se necesita HTTPS**: usa un tunel
(por ejemplo `cloudflared tunnel --url http://localhost` o `ngrok http 80`) o un
certificado local con `mkcert`.

## Estructura

```
umbrellapwa/
├── index.php               Entrada: redirige al login
├── login.php               Login (base visual, sin autenticacion aun)
├── manifest.webmanifest    Web App Manifest (nombre, iconos, colores)
├── sw.js                   Service worker (cache y modo offline)
├── offline.html            Pagina que se muestra sin conexion
├── .htaccess               MIME, cache, compresion y seguridad
├── includes/               Partes PHP (bloqueado desde el navegador)
│   ├── config.php          Nombre, version y colores de la app
│   ├── icons.php           Iconos SVG: icon('nombre', tamano)
│   ├── head.php            <head> con todos los meta tags PWA
│   ├── footer.php          Scripts y cierre del documento
│   ├── install-sheet.php   Hoja "Instalar app"
│   └── splash-links.php    Generado: enlaces de splash de iOS
├── assets/
│   ├── css/app.css         Estilos base
│   ├── js/app.js           JS de la app
│   ├── js/pwa/             Registro del SW e instalacion
│   ├── icons/              Favicon, iconos Apple/Android/Windows
│   ├── splash/             Splash screens de iOS / iPadOS
│   ├── img/                Imagenes
│   └── fonts/              Fuentes
└── tools/                  Generadores (bloqueado desde el navegador)
```

## Diseno

- **Paleta**: la del ecosistema Umbrella (igual que Umbrella Streaming). Variables en `:root` de `assets/css/app.css`: `--fondo`, `--panel`, `--superficie`, `--superficie-2`, `--acento`, etc.
- **Controles**: 28px de alto y 16px de padding lateral (tamanos estilo Supabase), radio de 6px.
- **Botones**: `.btn`, `.btn--primario`, `.btn--peligro`, `.btn--fantasma`, `.btn--bloque`, `.btn--icono`. Acabado "raised": borde de 1px, brillo interior y hover con `brightness`.
- **Campos**: `.campo` > `label` + `.input`. Con icono: envolver en `.input-grupo`.
- **Iconos**: `<?= icon('mail') ?>`. Mismo trazo que el logo (stroke 2, puntas redondeadas). Se agregan en `includes/icons.php`.
- **Logo**: el paraguas (`icon('logo')`). Los PNG se generan desde `tools/brand.php`.

## Soporte por plataforma

| Plataforma | Como se instala | Que usa |
|---|---|---|
| Android (Chrome, Edge, Samsung) | Boton "Instalar" (dialogo nativo) | manifest, iconos maskable |
| Windows (Edge, Chrome) | Boton "Instalar" o icono en la barra de direcciones | manifest, `msapplication-*` |
| macOS (Chrome, Edge) | Boton "Instalar" | manifest |
| macOS (Safari 17+) | Instrucciones: Archivo -> Agregar al Dock | `apple-touch-icon` |
| iPhone / iPad | Instrucciones: Compartir -> Agregar a inicio | `apple-*` meta, splash screens |

## Publicar una nueva version

1. Sube `APP_VERSION` en `includes/config.php` (rompe el cache de css/js con `?v=`).
2. Sube `CACHE_VERSION` en `sw.js`.
3. Si agregas archivos que deben funcionar offline, agregalos a `PRECACHE` en `sw.js`.

Los usuarios veran el aviso **"Hay una nueva version disponible"** y al tocar
**Actualizar** se carga la version nueva.

## Regenerar iconos y splash

El logo esta dibujado en `tools/brand.php`. Despues de cambiarlo:

```bash
php tools/generate-icons.php
php tools/generate-splash.php
```

`favicon.svg` se edita a mano.

## Secretos

Nunca pongas API keys ni contrasenas en archivos versionados. Usa
`includes/config.local.php` (esta en `.gitignore`); `config.php` lo carga
automaticamente si existe.

## Produccion

- URL: <https://pwa.umbrellasync.com>
- VPS Hostinger con aaPanel + nginx, PHP 8.4. Sitio en `/www/wwwroot/pwa.umbrellasync.com`
  (es un clon de este repo; dueno de los archivos: `www`).
- SSL: Let's Encrypt desde aaPanel (se renueva solo).
- nginx ignora el `.htaccess`. Sus reglas equivalentes estan en
  [`deploy/nginx-pwa.conf`](deploy/nginx-pwa.conf), instalado en
  `/www/server/panel/vhost/nginx/extension/pwa.umbrellasync.com/pwa.conf`.
  Si lo cambias, copialo al servidor y ejecuta `nginx -t && nginx -s reload`.

### Desplegar

Haz push a `main` y luego:

```bash
ssh root@<VPS> 'cd /www/wwwroot/pwa.umbrellasync.com && git pull --ff-only && chown -R www:www .git assets includes tools deploy *.php *.html sw.js manifest.webmanifest'
```

Recuerda subir `APP_VERSION` y `CACHE_VERSION` para que los usuarios reciban la actualizacion.
