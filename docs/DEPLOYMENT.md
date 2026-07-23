# Deployment — cPanel shared hosting

Target: cPanel shared hosting, no queue workers required for MVP (SPEC §9 point 8
— PDF/CSV generation runs synchronously). This doc is the Phase 11 checklist from
`tasks.md`, expanded into concrete steps.

## 1. SSL/HTTPS — hard requirement, do this first

Camera-based barcode scanning (`getUserMedia`, used by `html5-qrcode`) only works
in a [secure context](https://developer.mozilla.org/en-US/docs/Web/Security/Secure_Contexts).
`localhost` is exempt during local dev; any real domain is not. **The scanning
screen will not be able to request camera access at all over plain HTTP.**

1. In cPanel, go to **SSL/TLS Status** (or **AutoSSL**) for the target domain.
2. Enable AutoSSL if not already active, or install a purchased certificate under
   **SSL/TLS > Install and Manage SSL for your site**.
3. Force HTTPS: either enable **"Force HTTPS Redirect"** if your cPanel offers it,
   or add to `public/.htaccess` (above the existing Laravel rewrite rules):
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```
4. Verify: visit `https://yourdomain.com` in a browser and confirm the padlock
   shows a valid certificate before doing anything else below.

## 2. Upload the code

cPanel shared hosting typically has no shell/git access beyond what's in the
hosting plan — check what your specific plan supports:

- **If Git deploy / SSH is available**: clone the repo directly on the server,
  or use cPanel's **Git Version Control** feature pointed at this repository and
  branch.
- **If only FTP/File Manager is available**: build a deployment archive locally
  (exclude `.git`, `node_modules`, and `vendor` — those get installed on the
  server) and upload/extract it.

**Document root**: point the domain's document root at this project's `public/`
directory, not the project root — cPanel's "Domains" or "Addon Domains" screen
lets you set this. If you can't change the document root (some shared-hosting
setups only let you serve from `public_html`), the common workaround is:
place the Laravel app in a directory above `public_html` (e.g. `~/app`), then
copy `public/index.php` and `public/.htaccess` into `public_html`, editing the
two `require`/`bootstrap` paths in `index.php` to point at `~/app/vendor/autoload.php`
and `~/app/bootstrap/app.php`.

## 3. Server-side setup

Run these from a terminal (SSH or cPanel's **Terminal** feature) in the project
directory:

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build   # only if Node is available server-side; otherwise
                                # build assets locally and upload public/build/
cp .env.example .env           # then edit .env, see below
php artisan key:generate
php artisan migrate --force    # --force skips the production confirmation prompt
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If Node isn't available on the shared host (common), run `npm run build` locally
and upload the resulting `public/build/` directory — it's a static asset bundle,
no server-side Node needed at runtime.

### `.env` for production

```env
APP_NAME="Barcode Inventory"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<cpanel_mysql_db_name>
DB_USERNAME=<cpanel_mysql_user>
DB_PASSWORD=<cpanel_mysql_password>

SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_STORE=database
```

`APP_DEBUG=false` matters beyond convention here — with it `true` in production,
an unhandled exception dumps a full stack trace (file paths, query text, env
values) to any visitor. `QUEUE_CONNECTION=sync` matches SPEC §9's "no queue
workers" decision — PDF/CSV generation and every Filament action run inline on
the request, so nothing needs a worker process cPanel shared hosting typically
can't run anyway.

Create the MySQL database and user via cPanel's **MySQL Databases** tool first
(shared hosting usually prefixes both with your cPanel username, e.g.
`cpaneluser_barcode_inventory`) — put those exact prefixed names in `.env`.

### File permissions

`storage/` and `bootstrap/cache/` must be writable by the web server user:

```bash
chmod -R 775 storage bootstrap/cache
```

## 4. Post-deploy smoke test — do this from an actual phone

This is the one step that cannot be done from a sandbox or a desktop browser's
device emulator — camera behavior, autofocus, and one-handed layout ergonomics
only show up on real hardware. On a phone, over the live HTTPS domain:

1. Log in as each of the three seeded roles (or your real production users) and
   confirm the panel loads.
2. As a Branch Manager: create an inventory session, assign a counter, start it.
3. As the assigned Counter: open **My Sessions → Scan**, grant camera permission
   when prompted, and scan a real printed barcode (print one via **Products →
   Print label** first if you don't have one handy). Confirm:
   - the camera view actually opens (this is the step that silently fails
     without HTTPS — if it doesn't request permission at all, re-check step 1)
   - a known barcode counts and appears in the running list with a beep/flash
   - an unknown barcode prompts the quick-create form
   - the keyboard-wedge text input works as a fallback (useful if a physical
     USB/Bluetooth scanner is in use instead of the camera)
4. Submit → approve → close the session as the manager, then pull the Session
   Detail Report and confirm the CSV downloads correctly on mobile too.
5. Switch the locale to Arabic (topbar link) and repeat a quick pass — confirm
   the layout actually flips to RTL and stays usable one-handed, not just that
   the strings translate.

If any of this fails only on the phone and not in desktop testing, the usual
suspects are: HTTPS not actually enforced (check for mixed-content warnings),
the document root serving the wrong directory, or `public/build/` assets missing
because they weren't uploaded (see step 3 above).

## Rollback

Since there's no queue and no long-running processes to drain, rollback is just:
restore the previous code (git checkout / re-upload the prior archive), run
`php artisan migrate:rollback` **only if** the deploy included a migration you
need to undo (check `php artisan migrate:status` first — rolling back further
than intended will drop data), then re-run the `*:cache` commands from step 3.
