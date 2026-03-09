# How It Runs on Server — Local vs Production

## Local Setup (WAMP — Windows)

```
Browser
  │
  ├──► http://localhost/employee-profile/frontend/login.php
  │        → WAMP Apache → PHP 7.4 → serves frontend pages
  │
  └──► http://localhost:8080/auth/login  (API calls from JS)
           → php spark serve → PHP 8.2+ → CI4 backend
```

- You **manually start** WAMP and run `php spark serve --port=8080`
- Frontend and backend run on **two separate PHP runtimes**
- MySQL: port 3307 (employee_profile_db), port 3306 (hrms-extension-v2)

---

## Production Setup (Plesk + nginx — Ubuntu)

```
Browser
  │
  ├──► https://yourdomain.com/  (frontend)
  │        → nginx → php-fpm 8.4 → serves frontend pages
  │
  └──► https://yourdomain.com/api/auth/login  (API calls from JS)
           → nginx → php-fpm 8.4 → CI4 backend (public/index.php)
```

- **Nothing to start manually** — nginx + php-fpm are always running as system services
- Both frontend and backend use **one PHP runtime** (8.4 via php-fpm)
- `php spark serve` is **NOT used** on production
- MySQL (MariaDB): port 3306 for both databases

---

## Why No `php spark serve` on Server?

| | `php spark serve` | nginx + php-fpm |
|--|---|---|
| Purpose | Development convenience | Production serving |
| Concurrency | Single-threaded, one request at a time | Multi-process, handles many requests |
| Stability | Crashes = downtime, no auto-restart | Auto-managed by OS, auto-restarts |
| Performance | Slow, not optimized | Fast, optimized with opcache |
| SSL | Not supported | Full HTTPS support |

nginx + php-fpm does the **same job** as `php spark serve` but is production-grade.

---

## What Replaces What

| Local Component | Server Replacement |
|---|---|
| WAMP Apache | nginx (managed by Plesk) |
| PHP 7.4 (WAMP) | PHP 8.4 php-fpm (Plesk) |
| `php spark serve :8080` | nginx → php-fpm → `backend/public/index.php` |
| Manual startup | Automatic (system services) |
| `http://localhost:8080` | `https://yourdomain.com/api` |
| `http://localhost/employee-profile/frontend/` | `https://yourdomain.com/` |
| MySQL ports 3306 + 3307 | MariaDB port 3306 (both DBs) |

---

## How Requests Flow on Server

### Frontend Request (page load)

```
1. User visits: https://yourdomain.com/login.php
2. nginx receives the request
3. Matches *.php → passes to php-fpm (PHP 8.4)
4. php-fpm executes /var/www/vhosts/yourdomain.com/employee-profile/frontend/login.php
5. PHP renders HTML → nginx sends response to browser
```

### Backend API Request (AJAX from JS)

```
1. JavaScript calls: fetch('https://yourdomain.com/api/profile/family')
2. nginx receives the request
3. Matches /api/ location block
4. Routes to backend/public/index.php (CI4 entry point)
5. php-fpm executes CI4 → controller → model → MariaDB
6. JSON response → nginx → browser
```

### Compared to Local

```
Local Frontend:  Browser → Apache (WAMP) → PHP 7.4 → frontend/*.php
Server Frontend: Browser → nginx          → PHP 8.4 → frontend/*.php

Local Backend:   Browser → php spark serve (:8080) → PHP 8.2 → CI4
Server Backend:  Browser → nginx (/api/)  → PHP 8.4 → CI4 (same index.php)
```

---

## Plesk Configuration — Step by Step

### 1. Set PHP Version

Plesk → **Websites & Domains** → your domain → **PHP Settings**
- Change PHP version: **7.4.33 → 8.4.17**
- PHP handler: **FPM application served by nginx**
- Click **Apply**

### 2. Set Document Root (Frontend)

Plesk → **Hosting Settings** → **Document root**:
```
employee-profile/frontend
```

This makes `https://yourdomain.com/` serve the frontend — same as `http://localhost/employee-profile/frontend/` locally.

### 3. Add Backend API Route (nginx Directive)

Plesk → **Apache & nginx Settings** → **Additional nginx directives**:

```nginx
# ── CI4 Backend API (/api/ → backend/public/index.php) ──
location /api/ {
    alias /var/www/vhosts/yourdomain.com/employee-profile/backend/public/;
    try_files $uri $uri/ @ci4;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        include fastcgi_params;
    }
}

location @ci4 {
    rewrite ^/api/(.*)$ /api/index.php/$1 last;
}

# ── Block sensitive files ────────────────────────────────
location ~ /\.env { deny all; return 404; }
location ~ /\.git { deny all; return 404; }
location ~ /writable/ { deny all; return 404; }
location ~ /config/keys/ { deny all; return 404; }
```

> **Check your php-fpm socket path first:**
> ```bash
> ls /run/php/php*-fpm.sock
> # OR Plesk-managed:
> ls /var/www/vhosts/system/yourdomain.com/php-fpm.sock
> ```

### 4. Enable SSL

Plesk → **SSL/TLS Certificates** → **Let's Encrypt** → **Get it Free**

Then: **Hosting Settings** → ✅ **Permanent 301 redirect from HTTP to HTTPS**

### 5. Update Application URLs

**Frontend** — `frontend/config.php`:
```php
// LOCAL VALUES:
// define('HRMS_BASE', 'http://localhost/hrms_extension_v2/hrms_extension_v2');
// define('API_BASE', 'http://localhost:8080');
// define('SITE_URL', 'http://localhost/employee-profile/frontend');

// PRODUCTION VALUES:
define('HRMS_BASE', 'https://yourdomain.com/hrms_extension_v2/hrms_extension_v2');
define('API_BASE', 'https://yourdomain.com/api');
define('SITE_URL', 'https://yourdomain.com');
```

**Backend** — `backend/.env`:
```ini
# LOCAL:
# app.baseURL = http://localhost:8080

# PRODUCTION:
app.baseURL = https://yourdomain.com/api
```

**CORS** — `backend/app/Config/Cors.php`:
```php
// LOCAL:
// 'allowedOrigins' => ['http://localhost', 'http://localhost:8080', ...],

// PRODUCTION:
'allowedOrigins' => ['https://yourdomain.com'],
'allowedOriginsPatterns' => [],
```

---

## Quick Verification After Deploy

```bash
# 1. Check PHP version is 8.4
php -v

# 2. Check frontend loads
curl -I https://yourdomain.com/login.php
# Should return: HTTP/2 200

# 3. Check backend API responds
curl https://yourdomain.com/api/
# Should return JSON (CI4 welcome or 404 JSON)

# 4. Check sensitive files are blocked
curl -I https://yourdomain.com/api/../.env
# Should return: 404

# 5. Check SSL
curl -I https://yourdomain.com/
# Should show: strict-transport-security header
```

---

## TL;DR

| Question | Answer |
|----------|--------|
| Do I run `php spark serve` on server? | **No** — nginx + php-fpm replaces it |
| Do I need to start anything manually? | **No** — services run automatically |
| What serves the frontend? | nginx → php-fpm → `frontend/*.php` |
| What serves the backend API? | nginx `/api/` → php-fpm → `backend/public/index.php` |
| Where do I configure nginx? | **Plesk panel** → Apache & nginx Settings (not `/etc/nginx/`) |
| What PHP version? | **8.4** for both frontend and backend |
