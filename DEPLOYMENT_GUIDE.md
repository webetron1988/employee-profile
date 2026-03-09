# Deployment Guide — Employee Profile System (Plesk + nginx)

## Target Server Specs

| Component | Version |
|-----------|---------|
| OS | Ubuntu 22.04 |
| Control Panel | **Plesk** |
| Web Server | nginx 1.28.2 (Plesk-managed) |
| PHP Available | 7.4.33 (default), **8.4.17** (required) |
| Database | MariaDB 10.6.23 |
| DB User | assetuser@localhost |
| phpMyAdmin | 5.2.3 (via Plesk) |
| Confirmed Extensions | mysqli, curl, mbstring |

---

## Architecture

```
                   ┌──────────────────┐
    Browser ──────>│  Plesk / nginx   │
                   │   (SSL/TLS)      │
                   └────┬────────┬────┘
                        │        │
           ┌────────────┘        └────────────┐
           v                                  v
   ┌───────────────┐                ┌─────────────────┐
   │   Frontend    │   JWT/REST     │   CI4 Backend   │
   │  (PHP 8.4)   │ ──────────────>│   (PHP 8.4)     │
   │  /frontend/   │                │  php-fpm direct │
   └───────┬───────┘                └────────┬────────┘
           │                                 │
           v                                 v
   ┌───────────────┐         ┌───────────────────────────┐
   │  HRMS DB      │         │  employee_profile_db      │
   │  (read-only)  │         │  (CI4 managed)            │
   └───────────────┘         └───────────────────────────┘
```

---

## Step 1: Switch PHP Version in Plesk

The server defaults to PHP 7.4.33 — **CI4 requires PHP 8.2+**.

1. Log into **Plesk Panel**
2. Go to **Websites & Domains** → select your domain
3. Click **PHP Settings** (or **Hosting & DNS → PHP**)
4. Change **PHP version** to **8.4.17** (or `8.4.x` in dropdown)
5. Set **PHP handler** to **FPM application served by nginx**
6. Click **Apply**

**If PHP 8.4 is not in the dropdown:**

```bash
# SSH into the server
plesk bin php_handler --list          # See registered versions
plesk bin php_handler --reread        # Re-scan and register new PHP versions
```

**Verify the change:**

```bash
# Should show 8.4.17, NOT 7.4.33
php -v

# If `php` still shows 7.4, use the full path:
/opt/plesk/php/8.4/bin/php -v
```

> **Important:** All `php` commands below assume PHP 8.4. If `php -v` still shows 7.4,
> replace `php` with `/opt/plesk/php/8.4/bin/php` in every command.

---

## Step 2: Enable Required PHP Extensions in Plesk

1. In Plesk → **Websites & Domains** → your domain → **PHP Settings**
2. Scroll to **PHP extensions** and enable these:

| Extension | Needed By | Required? |
|-----------|-----------|-----------|
| mysqli / mysqlnd | CI4 database | **Yes** |
| curl | Guzzle, HRMS API calls | **Yes** |
| mbstring | CI4 core, string handling | **Yes** |
| openssl | JWT RS256 key signing | **Yes** |
| intl | CI4 core (i18n, validation) | **Yes** |
| json | JWT, all API responses | **Yes** (bundled in 8.4) |
| xml / dom | AWS SDK, Guzzle | **Yes** |
| fileinfo | File upload MIME validation | Recommended |
| gd | Image processing | Recommended |

3. Click **Apply**

**Verify via SSH:**

```bash
php -m | grep -iE "intl|mbstring|json|mysqlnd|curl|openssl|xml|dom|fileinfo"
```

If any are missing and not in Plesk's list, install manually:

```bash
sudo apt install php8.4-intl php8.4-mbstring php8.4-curl php8.4-xml php8.4-mysql php8.4-zip php8.4-gd
sudo systemctl restart php8.4-fpm
```

---

## Step 3: Upload / Clone Project Files

### Option A: Git Clone (Recommended)

```bash
# Plesk typically uses /var/www/vhosts/yourdomain.com/
cd /var/www/vhosts/yourdomain.com/
git clone <repo-url> employee-profile
```

### Option B: Upload via Plesk File Manager

1. Plesk → **Files** → navigate to your domain's document root
2. Upload the project as a zip and extract

> **Plesk document root** is typically:
> `/var/www/vhosts/yourdomain.com/httpdocs/`
>
> You can place the project at:
> `/var/www/vhosts/yourdomain.com/employee-profile/`

---

## Step 4: Install Composer Dependencies

```bash
cd /var/www/vhosts/yourdomain.com/employee-profile/backend

# Install composer if not available
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# Install production dependencies
composer install --no-dev --optimize-autoloader
```

---

## Step 5: Configure Backend `.env`

```bash
cd /var/www/vhosts/yourdomain.com/employee-profile/backend
cp .env .env.bak
nano .env
```

```ini
CI_ENVIRONMENT = production

# ── Application ──────────────────────────────────────────
app.baseURL    = https://yourdomain.com/employee-profile/backend/public
app.indexPage  =
app.uriProtocol = REQUEST_URI

# ── Employee Profile Database ────────────────────────────
database.default.hostname = localhost
database.default.database = employee_profile_db
database.default.username = assetuser
database.default.password = YOUR_STRONG_PASSWORD
database.default.DBDriver = MySQLi
database.default.port     = 3306
database.default.charset  = utf8mb4
database.default.DBCollat = utf8mb4_general_ci

# ── HRMS Database (read-only) ───────────────────────────
database.hrms.hostname = localhost
database.hrms.database = hrms-extension-v2
database.hrms.username = assetuser
database.hrms.password = YOUR_STRONG_PASSWORD
database.hrms.DBDriver = MySQLi
database.hrms.port     = 3306
database.hrms.charset  = utf8mb4
database.hrms.DBCollat = utf8mb4_general_ci

# ── Encryption (CHANGE — exactly 32 characters) ─────────
encryption.key       = <run: openssl rand -base64 24 | head -c 32>
encryption.algorithm = AES-256-CBC

# ── JWT ──────────────────────────────────────────────────
jwt.algorithm = RS256

# ── HRMS Integration ────────────────────────────────────
hrms.sso_endpoint   = https://yourdomain.com/hrms_extension_v2/ep-profile
hrms.base_url       = https://yourdomain.com/hrms_extension_v2
hrms.api_base_url   = https://yourdomain.com/hrms_extension_v2
hrms.jwt_secret_key = 'SAME_KEY_AS_HRMS_SYSTEM'
hrms.jwt_algorithm  = HS256
hrms.token_expiry   = 120

# ── Security ────────────────────────────────────────────
security.sessionDriver  = FileHandler
security.cookieHttponly = true
security.cookieSecure   = true

# ── Service API Key ─────────────────────────────────────
EP_API_KEY = <run: openssl rand -hex 32>

# ── Logging (1=ERROR only for production) ────────────────
logging.threshold = 1
```

---

## Step 6: Generate RSA Keys for JWT

```bash
mkdir -p /var/www/vhosts/yourdomain.com/employee-profile/backend/config/keys
cd /var/www/vhosts/yourdomain.com/employee-profile/backend/config/keys

openssl genrsa -out private.pem 2048
openssl rsa -in private.pem -pubout -out public.pem

chmod 600 private.pem
chmod 644 public.pem

# Plesk runs php-fpm as the domain's system user (check with: ps aux | grep php-fpm)
# Common Plesk FPM users: the subscription's system user or www-data
chown $(stat -c '%U' /var/www/vhosts/yourdomain.com/) private.pem public.pem
```

> **Never commit these keys to git.**

---

## Step 7: Configure Frontend `config.php`

Edit `/var/www/vhosts/yourdomain.com/employee-profile/frontend/config.php`:

```php
define('HRMS_BASE', 'https://yourdomain.com/hrms_extension_v2/hrms_extension_v2');
define('API_BASE', 'https://yourdomain.com/employee-profile/backend/public');
define('SITE_URL', 'https://yourdomain.com/employee-profile/frontend');
define('SITE_TITLE', 'Employee Profile System');
define('HRMS_API_KEY', 'your-production-EP_API_KEY-from-step-5');

define('HRMS_DB_HOST', '127.0.0.1');
define('HRMS_DB_PORT', '3306');
define('HRMS_DB_NAME', 'hrms-extension-v2');
define('HRMS_DB_USER', 'assetuser');
define('HRMS_DB_PASS', 'YOUR_STRONG_PASSWORD');
```

---

## Step 8: Create Database & Run Migrations

### Via phpMyAdmin (Plesk)

1. Plesk → **Databases** → **phpMyAdmin**
2. Create database: `employee_profile_db` with charset `utf8mb4_general_ci`

### Via SSH

```bash
mysql -u assetuser -p -e "CREATE DATABASE IF NOT EXISTS employee_profile_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

### Run Migrations

```bash
cd /var/www/vhosts/yourdomain.com/employee-profile/backend
php spark migrate --all
php spark db:seed DatabaseSeeder
```

---

## Step 9: Set File Permissions

```bash
PROJECT=/var/www/vhosts/yourdomain.com/employee-profile
PLESK_USER=$(stat -c '%U' /var/www/vhosts/yourdomain.com/)

# Backend writable directory (logs, cache, sessions)
chown -R $PLESK_USER:$PLESK_USER $PROJECT/backend/writable
chmod -R 775 $PROJECT/backend/writable

# Frontend
chown -R $PLESK_USER:$PLESK_USER $PROJECT/frontend
chmod -R 755 $PROJECT/frontend

# HRMS uploads (profile photos) — adjust path as needed
chmod -R 775 /var/www/vhosts/yourdomain.com/hrms_extension_v2/uploads
```

---

## Step 10: Configure nginx via Plesk

Plesk manages nginx config — **do NOT edit `/etc/nginx/` directly**, it will be overwritten.

### Set Document Root

1. Plesk → **Websites & Domains** → your domain → **Hosting & DNS → Hosting Settings**
2. Set **Document root** to: `employee-profile/frontend`
3. Click **OK**

### Add nginx Directives

1. Plesk → **Websites & Domains** → your domain
2. Click **Apache & nginx Settings** (or **nginx Settings**)
3. In the **Additional nginx directives** textarea, add:

```nginx
# ── CI4 Backend API routing ──────────────────────────────
location /employee-profile/backend/public/ {
    alias /var/www/vhosts/yourdomain.com/employee-profile/backend/public/;
    index index.php;

    try_files $uri $uri/ @ci4_backend;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
}

location @ci4_backend {
    rewrite ^/employee-profile/backend/public/(.*)$ /employee-profile/backend/public/index.php/$1 last;
}

# ── Block sensitive files ────────────────────────────────
location ~ /\.env { deny all; return 404; }
location ~ /\.git { deny all; return 404; }
location ~ /writable/ { deny all; return 404; }
location ~ /config/keys/ { deny all; return 404; }
location ~ /\.ht { deny all; return 404; }
```

> **Note:** Check the actual php-fpm socket path on your server:
> ```bash
> ls /run/php/php*-fpm.sock
> # OR for Plesk-managed FPM:
> ls /var/www/vhosts/system/yourdomain.com/php-fpm.sock
> ```
> Update the `fastcgi_pass` line accordingly.

4. Click **OK** — Plesk will reload nginx automatically

### Alternative: Use Plesk Proxy (Simpler)

If direct php-fpm routing is complex, you can run CI4 as a service and proxy:

In Plesk nginx directives:

```nginx
location /api/ {
    proxy_pass http://127.0.0.1:8080/;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

Then create a systemd service (see Step 12 below).

---

## Step 11: SSL Setup via Plesk

Plesk makes SSL easy — no manual certbot needed.

1. Plesk → **Websites & Domains** → your domain
2. Click **SSL/TLS Certificates**
3. Click **Install** → choose **Let's Encrypt**
4. Check: ✅ Secure the domain, ✅ Include www subdomain
5. Click **Get it Free**
6. After installed, go back to **Hosting Settings**
7. Enable: ✅ **Permanent SEO-safe 301 redirect from HTTP to HTTPS**

> Plesk handles auto-renewal automatically.

---

## Step 12: Create systemd Service (Optional — for proxy setup only)

Only needed if using the proxy approach (not direct php-fpm):

```bash
sudo nano /etc/systemd/system/ep-backend.service
```

```ini
[Unit]
Description=Employee Profile CI4 Backend
After=network.target mariadb.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/vhosts/yourdomain.com/employee-profile/backend
ExecStart=/opt/plesk/php/8.4/bin/php spark serve --host=127.0.0.1 --port=8080
Restart=always
RestartSec=5
Environment=CI_ENVIRONMENT=production

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable ep-backend
sudo systemctl start ep-backend
sudo systemctl status ep-backend
```

---

## Step 13: Update CORS for Production

Edit `backend/app/Config/Cors.php`:

```php
'allowedOrigins' => [
    'https://yourdomain.com',
],

'allowedOriginsPatterns' => [],
```

---

## Step 14: Set Up Cron Jobs via Plesk

1. Plesk → **Websites & Domains** → **Scheduled Tasks** (or **Cron Jobs**)
2. Add these tasks:

| Schedule | Command | Description |
|----------|---------|-------------|
| Every 15 min | `cd /var/www/vhosts/yourdomain.com/employee-profile/backend && /opt/plesk/php/8.4/bin/php spark hrms:sync` | Sync employees from HRMS |
| Daily 2:00 AM | `cd /var/www/vhosts/yourdomain.com/employee-profile/backend && /opt/plesk/php/8.4/bin/php spark job:sync` | Sync job data |
| Daily 3:00 AM | `cd /var/www/vhosts/yourdomain.com/employee-profile/backend && /opt/plesk/php/8.4/bin/php spark org:sync` | Sync org hierarchy |

> **Important:** Use the full PHP 8.4 path (`/opt/plesk/php/8.4/bin/php`) in Plesk cron —
> the default `php` may still point to 7.4.

---

## Post-Deployment Checklist

- [ ] **PHP 8.4 selected** in Plesk → PHP Settings (not 7.4)
- [ ] **PHP extensions** enabled: mysqli, curl, mbstring, openssl, intl, xml, json
- [ ] **SSL certificate** installed and HTTP→HTTPS redirect enabled
- [ ] **CI_ENVIRONMENT = production** in `.env`
- [ ] **Database credentials** — using `assetuser`, strong password
- [ ] **RSA keys generated** — `backend/config/keys/private.pem` and `public.pem` exist
- [ ] **EP_API_KEY set** — unique random key in `.env`
- [ ] **HRMS_API_KEY set** — matching key in `frontend/config.php`
- [ ] **Migrations run** — `php spark migrate --all` completed
- [ ] **CORS updated** — only production domain in `Cors.php`
- [ ] **cookieSecure = true** in `.env`
- [ ] **Sensitive files blocked** — `.env`, `.git`, `writable/`, `config/keys/` return 403/404
- [ ] **Writable directory** — `backend/writable/` owned by Plesk system user, mode 775
- [ ] **Cron jobs** — configured in Plesk with PHP 8.4 path
- [ ] **Test login** — both email/password and SSO login work
- [ ] **Test all 7 tabs** — render without JS console errors
- [ ] **Test photo upload** — profile picture upload and display
- [ ] **HRMS sync** — `php spark hrms:sync` completes successfully

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| PHP still shows 7.4 | Switch in Plesk → PHP Settings; for SSH use `/opt/plesk/php/8.4/bin/php` |
| 502 Bad Gateway | Check php-fpm: `systemctl status php8.4-fpm`; check Plesk php-fpm socket path |
| 500 error on backend | Check `backend/writable/logs/`; also check Plesk domain error logs |
| `Class 'intl' not found` | Enable in Plesk PHP Settings or: `apt install php8.4-intl` |
| JWT token invalid | Verify RSA keys exist with correct permissions (600/644) |
| CORS errors | Verify `Cors.php` has production domain; check browser console |
| Database connection failed | Test: `mysql -u assetuser -p employee_profile_db`; verify `.env` |
| Photo upload fails | Check uploads directory permissions (775) |
| SSO login fails | Verify `hrms.jwt_secret_key` in `.env` matches HRMS system |
| Frontend shows blank | Browser console → verify `API_BASE` in `config.php` uses HTTPS |
| nginx directives ignored | Plesk rewrites nginx conf — only use Plesk's nginx directives box |
| Cron not running | Verify full PHP 8.4 path in Plesk cron; check Plesk task logs |
| `composer: command not found` | Install or use full path: `/usr/local/bin/composer` |
| Permission denied on writable/ | `chown -R <plesk-user> backend/writable/` |

---

## Security Reminders

1. **Never commit** `.env`, RSA keys, or `writable/` contents to git
2. **HTTPS only** — JWT tokens are sent in Authorization headers
3. **Rotate keys** periodically — both JWT RSA keys and EP_API_KEY
4. **Database user** — `assetuser` should have minimal privileges:
   - `employee_profile_db`: SELECT, INSERT, UPDATE, DELETE
   - `hrms-extension-v2`: SELECT only
5. **File uploads** — only JPEG/PNG/WebP/GIF, max 2MB, MIME validated server-side
6. **Rate limiting** — enable `RateLimitMiddleware` for `/auth/*` endpoints
7. **Plesk firewall** — Plesk → **Tools & Settings → Firewall**: allow only 80, 443, 22
8. **MariaDB** — should bind to `127.0.0.1` only (Plesk default)

---

## Quick Reference — Key Paths on Plesk Server

| What | Path |
|------|------|
| Plesk domain root | `/var/www/vhosts/yourdomain.com/` |
| Project root | `/var/www/vhosts/yourdomain.com/employee-profile/` |
| Backend `.env` | `.../employee-profile/backend/.env` |
| Frontend config | `.../employee-profile/frontend/config.php` |
| RSA keys | `.../employee-profile/backend/config/keys/` |
| CI4 logs | `.../employee-profile/backend/writable/logs/` |
| Plesk nginx conf | Plesk Panel → Apache & nginx Settings → Additional directives |
| Plesk domain logs | `/var/www/vhosts/system/yourdomain.com/logs/` |
| PHP 8.4 binary | `/opt/plesk/php/8.4/bin/php` |
| php-fpm socket | `/run/php/php8.4-fpm.sock` or `/var/www/vhosts/system/yourdomain.com/php-fpm.sock` |
