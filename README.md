# UmbrellaApp

A PHP Progressive Web App (PWA), installable on iPhone, iPad, Android, Windows and macOS.

Everything is in English: code, comments, documentation and the user interface.

## Requirements

- PHP 8.1+ with the GD extension (only to regenerate icons and splash screens)
- Apache with `mod_rewrite`, `mod_headers`, `mod_mime` and `mod_deflate` (XAMPP ships them)

## Running locally

With XAMPP running, open <http://localhost/umbrellapwa/>.

`localhost` counts as a secure origin, so the service worker works without HTTPS.
Testing from a phone on the local network **requires HTTPS**: use a tunnel
(for example `cloudflared tunnel --url http://localhost` or `ngrok http 80`) or a
local certificate with `mkcert`.

## Structure

```
umbrellapwa/
├── index.php               Public landing page: install the app and everything Umbrella can do; the installed app opens login.php
├── login.php               Sign in (visual base; for now it goes straight to home.php); links back to the landing page
├── home.php, sales.php, opportunities.php, reports.php   App sections
├── manifest.webmanifest    Web App Manifest (name, icons, colors)
├── sw.js                   Service worker (caching and offline mode)
├── offline.php             Offline view (menu and dark mode, no data)
├── .htaccess               MIME types, caching, compression and security
├── includes/               PHP partials (blocked from the browser)
│   ├── config.php          App name, version and colors
│   ├── icons.php           Feather icons: icon('name', size)
│   ├── charts.php          SVG line charts (landing page)
│   ├── nav.php             Menu sections (NAV_ITEMS)
│   ├── shell-top.php       Top bar + sidebar (internal pages)
│   ├── shell-bottom.php    Bottom navigation bar + closing tags
│   ├── head.php            <head> with every PWA meta tag
│   ├── footer.php          Scripts and document closing
│   ├── install-sheet.php   "Install app" sheet
│   └── splash-links.php    Generated: iOS splash links
├── assets/
│   ├── css/app.css         Base styles
│   ├── css/landing.css     Landing page styles
│   ├── js/app.js           App JavaScript
│   ├── js/landing.js       Landing page JavaScript (sticky bar, animations, live feed)
│   ├── js/pwa/             Service worker registration and install flow
│   ├── js/vendor/          Third-party libraries (Feather Icons)
│   ├── icons/              Favicon, Apple/Android/Windows icons
│   ├── splash/             iOS / iPadOS splash screens
│   ├── img/                Images
│   └── fonts/              Fonts
├── deploy/                 Server configuration (nginx)
└── tools/                  Generators (blocked from the browser)
```

## Design

- **Palette**: the Umbrella ecosystem palette (same as Umbrella Streaming). Variables in `:root` of `assets/css/app.css`: `--bg`, `--panel`, `--surface`, `--surface-2`, `--accent`, `--text`, `--text-2`, `--text-3`, `--border`, etc.
- **Controls**: 28px tall with 16px horizontal padding (Supabase-style sizes), 6px radius.
- **Buttons**: `.btn`, `.btn--primary`, `.btn--danger`, `.btn--ghost`, `.btn--block`, `.btn--icon`, `.btn--large`. Flat finish: 1px border, subtle gradient and a `brightness` hover. No shadows.
- **Fields**: `.field` > `label` + `.input`. With an icon: wrap it in `.input-group`.
- **Icons**: [Feather Icons](https://feathericons.com) 4.29.2, stored in `assets/js/vendor/` (works offline). In PHP `<?= icon('mail') ?>`, in HTML `<i data-feather="mail"></i>`.
- **Themes**: dark (default) and light via `<html data-theme="light">`. The "Dark mode" switch in the sidebar toggles it and the browser remembers the choice.
- **Layout**: top bar (circular button + centered title), settings-style sidebar (`.group` > `.row`) and a bottom bar on phones. On desktop (>= 1024px) the sidebar is pinned to the left.
- **New section**: add it to `NAV_ITEMS` in `includes/nav.php` and create its file with `$pageTitle`, `$activeNav` and `shell-top.php` / `shell-bottom.php`.
- **Logo**: the umbrella (`icon('logo')`). PNGs are generated from `tools/brand.php`.

## Platform support

| Platform | How it installs | What it uses |
|---|---|---|
| Android (Chrome, Edge, Samsung) | "Install" button (native dialog) | manifest, maskable icons |
| Windows (Edge, Chrome) | "Install" button or the address bar icon | manifest, `msapplication-*` |
| macOS (Chrome, Edge) | "Install" button | manifest |
| macOS (Safari 17+) | Instructions: File -> Add to Dock | `apple-touch-icon` |
| iPhone / iPad | Instructions: Share -> Add to Home Screen | `apple-*` meta tags, splash screens |

## Shipping a new version

- **CSS and JS**: nothing to do. `asset()` appends `?v=<last modified time>` to every
  file, so changing a file changes its URL and everyone gets the new version on the
  next load (the service worker cannot serve the old one).
- **PHP pages**: always fetched from the network; never cached.
- **Files without `?v=`** (icons, manifest): refreshed in the background.
- **`sw.js`**: if you change it, users see the **"A new version is available"**
  toast and it activates when they tap **Update**. Only bump `CACHE_VERSION` when
  you change the `PRECACHE` list or want to wipe the cache.
- `APP_VERSION` in `includes/config.php` is only the number shown in the app.

During development, enable **Update on reload** in Chrome DevTools -> Application ->
Service Workers so the cache never gets in the way.

## Regenerating icons and splash screens

The logo is drawn in `tools/brand.php`. After changing it:

```bash
php tools/generate-icons.php
php tools/generate-splash.php
```

`favicon.svg` is edited by hand.

## Secrets

Never put API keys or passwords in versioned files. Use `includes/config.local.php`
(git-ignored); `config.php` loads it automatically when it exists.

## Production

- URL: <https://pwa.umbrellasync.com>
- Hostinger VPS with aaPanel + nginx, PHP 8.4. Site at `/www/wwwroot/pwa.umbrellasync.com`
  (a clone of this repo; files owned by `www`).
- SSL: Let's Encrypt from aaPanel (renews automatically).
- nginx ignores `.htaccess`. The equivalent rules live in
  [`deploy/nginx-pwa.conf`](deploy/nginx-pwa.conf), installed at
  `/www/server/panel/vhost/nginx/extension/pwa.umbrellasync.com/pwa.conf`.
  If you change it, copy it to the server and run `nginx -t && nginx -s reload`.

### Deploying

Push to `main`, then:

```bash
ssh root@<VPS> 'cd /www/wwwroot/pwa.umbrellasync.com && git pull --ff-only && chown -R www:www .git assets includes tools deploy *.php sw.js manifest.webmanifest'
```
