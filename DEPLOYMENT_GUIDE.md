# Deployment Guide — Employee Profile System

## Architecture

```
                   ┌──────────────────┐
    Browser ──────>│  Apache / Nginx  │
                   │  (PHP 7.4/8.2+)  │
                   └────┬────────┬────┘
                        │        │
           ┌────────────┘        └────────────┐
           v                                  v
   ┌───────────────┐                ┌─────────────────┐
   │   Frontend    │   JWT/REST     │   CI4 Backend   │
   │  (PHP 7.4+)  │ ──────────────>│   (PHP 8.2+)    │
   │  /frontend/   │                │   :8080 or vhost│
   └───────┬───────┘                └────────┬────────┘
           │                                 │
           v                                 v
   ┌───────────────┐         ┌───────────────────────────┐
   │  HRMS DB      │         │  employee_profile_db      │
   │  (read-only)  │         │  (CI4 managed)            │
   └───────────────┘         └───────────────────────────┘
```

---

## Prerequisites

| Requirement | Version |
|-------------|---------|
| PHP (backend) | 8.2+ with extensions: intl, mbstring, json, mysqlnd, curl, openssl |
| PHP (frontend) | 7.4+ (or same 8.2 if single server) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Composer | 2.x |
| Apache / Nginx | Latest stable |
| OpenSSL | For RSA key generation |

---

## Step-by-Step Deployment

### 1. Clone & Install Dependencies

```bash
git clone <repo-url> /var/www/employee-profile
cd /var/www/employee-profile/backend
composer install --no-dev --optimize-autoloader
```

### 2. Configure Backend Environment

```bash
cd /var/www/employee-profile/backend
cp .env.example .env
```

Edit `.env` with your production values:

```ini
CI_ENVIRONMENT = production

# App URL (the public URL of the CI4 backend)
app.baseURL = https://yourdomain.com/api

# Employee Profile database
database.default.hostname = localhost
database.default.database = employee_profile_db
database.default.username = ep_user
database.default.password = YOUR_STRONG_PASSWORD
database.default.port     = 3306

# HRMS database (read-only access)
database.hrms.hostname = localhost
database.hrms.database = hrms-extension-v2
database.hrms.username = ep_readonly_user
database.hrms.password = YOUR_STRONG_PASSWORD
database.hrms.port     = 3306

# Encryption key (exactly 32 random characters)
encryption.key = $(openssl rand -base64 24 | head -c 32)

# HRMS SSO integration
hrms.sso_endpoint   = https://yourdomain.com/hrms_extension_v2/ep-profile
hrms.base_url       = https://yourdomain.com/hrms_extension_v2
hrms.jwt_secret_key = SAME_KEY_AS_HRMS_SYSTEM
hrms.jwt_algorithm  = HS256

# Service API key (generate a unique key)
EP_API_KEY = $(openssl rand -hex 32)

# Logging — errors only in production
logging.threshold = 1
```

### 3. Generate RSA Keys for JWT

```bash
mkdir -p /var/www/employee-profile/backend/config/keys
cd /var/www/employee-profile/backend/config/keys

openssl genrsa -out private.pem 2048
openssl rsa -in private.pem -pubout -out public.pem

chmod 600 private.pem
chmod 644 public.pem
```

> These keys are used for RS256 JWT signing. Never commit them to git.

### 4. Configure Frontend

Edit `frontend/config.php` — update the constants at the top:

```php
define('HRMS_BASE', 'https://yourdomain.com/hrms_extension_v2/hrms_extension_v2');
define('API_BASE', 'https://yourdomain.com/api');
define('SITE_URL', 'https://yourdomain.com/employee-profile/frontend');
define('SITE_TITLE', 'Employee Profile System');
define('HRMS_API_KEY', 'your-production-api-key');

// HRMS Database
define('HRMS_DB_HOST', '127.0.0.1');
define('HRMS_DB_PORT', '3306');
define('HRMS_DB_NAME', 'hrms-extension-v2');
define('HRMS_DB_USER', 'ep_readonly_user');
define('HRMS_DB_PASS', 'YOUR_STRONG_PASSWORD');
```

### 5. Set Up Database

```bash
cd /var/www/employee-profile/backend

# Run all migrations
php spark migrate --all

# Seed initial data (skills, competencies, courses, system config)
php spark db:seed DatabaseSeeder
```

### 6. Set File Permissions

```bash
# Backend writable directory
chmod -R 775 /var/www/employee-profile/backend/writable
chown -R www-data:www-data /var/www/employee-profile/backend/writable

# HRMS uploads directory (for profile photos)
chmod -R 775 /path/to/hrms_extension_v2/uploads
```

### 7. Apache Virtual Host Configuration

#### Frontend (PHP 7.4+)

```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/employee-profile/frontend

    # SSL
    SSLEngine on
    SSLCertificateFile    /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem

    <Directory /var/www/employee-profile/frontend>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Backend (PHP 8.2+ — CI4)

```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/employee-profile/backend/public

    # This handles /api/* requests
    Alias /api /var/www/employee-profile/backend/public

    <Directory /var/www/employee-profile/backend/public>
        AllowOverride All
        Require all granted

        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php/$1 [L]
    </Directory>
</VirtualHost>
```

**Alternative (single vhost with proxy):**

```apache
# Frontend served directly
DocumentRoot /var/www/employee-profile/frontend

# Backend behind reverse proxy
ProxyPass /api http://127.0.0.1:8080
ProxyPassReverse /api http://127.0.0.1:8080
```

### 8. Nginx Configuration (Alternative)

```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;

    ssl_certificate     /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # Frontend
    root /var/www/employee-profile/frontend;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Backend API (proxy to CI4)
    location /api/ {
        proxy_pass http://127.0.0.1:8080/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

---

## Post-Deployment Checklist

- [ ] **HTTPS enabled** — both frontend and backend must use HTTPS
- [ ] **CORS** — update `backend/app/Config/Cors.php` to allow only your production domain
- [ ] **CI_ENVIRONMENT = production** — verified in `.env`
- [ ] **Database credentials** — strong passwords, not root
- [ ] **RSA keys generated** — `backend/config/keys/private.pem` and `public.pem` exist
- [ ] **EP_API_KEY set** — unique key in backend `.env`
- [ ] **HRMS_API_KEY set** — matching key in `frontend/config.php`
- [ ] **Migrations run** — `php spark migrate --all` completed successfully
- [ ] **Writable directory** — `backend/writable/` is writable by web server
- [ ] **Error display off** — no PHP errors shown to users (production boot config handles this)
- [ ] **Test login** — verify both email/password and SSO login work
- [ ] **Test profile load** — all 7 tabs render without JS errors
- [ ] **Test photo upload** — profile picture upload and display works
- [ ] **HRMS sync** — run `php spark hrms:sync` to verify data sync

---

## Cron Jobs (Optional)

```bash
# Sync employee data from HRMS every 15 minutes
*/15 * * * * cd /var/www/employee-profile/backend && php spark hrms:sync >> /var/log/ep-sync.log 2>&1

# Sync job information daily
0 2 * * * cd /var/www/employee-profile/backend && php spark job:sync >> /var/log/ep-job-sync.log 2>&1

# Sync org hierarchy daily
0 3 * * * cd /var/www/employee-profile/backend && php spark org:sync >> /var/log/ep-org-sync.log 2>&1
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 500 error on backend | Check `backend/writable/logs/` for CI4 error logs |
| JWT token invalid | Verify RSA keys exist and have correct permissions |
| CORS errors in browser | Update allowed origins in `backend/app/Config/Cors.php` |
| Database connection failed | Verify `.env` credentials and MySQL is running |
| Photo upload fails | Check HRMS uploads directory permissions |
| SSO login fails | Verify `hrms.jwt_secret_key` matches HRMS system |
| Frontend shows blank | Check browser console; verify `API_BASE` in config.php |

---

## Security Reminders

1. **Never commit** `.env`, RSA keys, or `writable/` contents
2. **Use HTTPS** in production — JWT tokens are sent in headers
3. **Rotate keys** periodically — both JWT RSA keys and EP_API_KEY
4. **Database users** — use dedicated users with minimal privileges (not root)
5. **File uploads** — only JPEG/PNG/WebP/GIF allowed, max 2MB, MIME validated server-side
6. **Rate limiting** — consider enabling `RateLimitMiddleware` for auth endpoints
