# Employee Profile System - Backend

**Framework**: CodeIgniter 4  
**PHP Version**: 8.2 LTS  
**Status**: Phase 1 - Foundation & Setup

---

## 📁 Backend Folder Structure

```
backend/
├── app/
│   ├── Controllers/          # API endpoints
│   ├── Models/               # Database models
│   ├── Middleware/           # Permission, auth, etc.
│   ├── Libraries/            # Custom libraries (JWT, Encryption, etc.)
│   ├── Database/
│   │   ├── Migrations/       # Database schema migrations
│   │   └── Seeds/            # Test data seeders
│   ├── Config/               # Application configuration
│   └── Exceptions/           # Custom exceptions
├── config/                   # Framework configuration
├── public/                   # Web server root
├── tests/                    # Unit & integration tests
├── writable/
│   ├── logs/                 # Application logs
│   ├── uploads/              # File uploads
│   └── cache/                # Cache files
├── docs/
│   ├── api/                  # API documentation
│   └── database/             # Database schema docs
├── .env.example              # Environment template
├── .gitignore                # Git ignore rules
├── composer.json             # PHP dependencies
├── composer.lock             # Dependency lock file
├── README.md                 # This file
└── PHASE_1_DETAILS.md        # Phase 1 implementation plan
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2 LTS
- MySQL 8.0
- Redis (for caching & sessions)
- Composer
- Git

### Installation

1. **Clone or navigate to backend folder**
   ```bash
   cd backend
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

4. **Configure .env**
   - Database credentials
   - HRMS endpoints
   - Encryption keys
   - AWS S3 access
   - Redis connection

5. **Create database**
   ```bash
   php spark db:create employee_profile_db
   ```

6. **Run migrations** (Phase 1)
   ```bash
   php spark migrate
   ```

7. **Start development server**
   ```bash
   php spark serve
   ```

Server running at: `http://localhost:8080`

---

## 📦 Phase 1: What's Included

### Database Schema (30+ Tables)
- ✅ Employee identity & personal information
- ✅ Health & medical records (encrypted)
- ✅ Family & dependents
- ✅ Job & organization structure (HRMS synced)
- ✅ Performance & goals
- ✅ Skills & competencies
- ✅ Learning & training
- ✅ Audit & security logs

### Authentication & Authorization
- ✅ HRMS SSO integration (JWT RS256)
- ✅ Permission middleware (RBAC)
- ✅ Field-level masking
- ✅ Session management (Redis)

### Security & Encryption
- ✅ AES-256 encryption for sensitive data
- ✅ Government IDs encrypted
- ✅ Bank details encrypted
- ✅ Health records encrypted
- ✅ Secure key management

### HRMS Integration
- ✅ SSO authentication
- ✅ Employee master data sync
- ✅ Job information sync
- ✅ Organization hierarchy sync
- ✅ Manager relationships sync

---

## 🛠️ Phase 1 Tasks

### Week 1: Foundation
- [ ] Database schema creation (30+ tables)
- [ ] CodeIgniter 4 project setup
- [ ] Environment configuration
- [ ] Dependency installation

### Week 2: Auth & Security
- [ ] HRMS SSO integration
- [ ] Permission middleware
- [ ] Encryption system
- [ ] HRMS data sync
- [ ] Testing & validation

---

## 📝 Key Files for Phase 1

| File | Purpose |
|------|---------|
| `.env.example` | Environment configuration template |
| `composer.json` | PHP dependencies |
| `PHASE_1_DETAILS.md` | Detailed Phase 1 implementation guide |
| `app/Controllers/Auth.php` | Authentication endpoints (to create) |
| `app/Libraries/JwtHandler.php` | JWT handling (to create) |
| `app/Libraries/HrmsClient.php` | HRMS API integration (to create) |
| `app/Middleware/PermissionMiddleware.php` | Permission checks (to create) |
| `app/Libraries/Encryptor.php` | Encryption/decryption (to create) |
| `app/Database/Migrations/*.php` | Database schema (to create) |

---

## 🔗 API Endpoints (Phase 1)

### Authentication
```
POST   /auth/sso-login        - SSO login with HRMS JWT
POST   /auth/refresh          - Refresh token
POST   /auth/logout           - Logout
GET    /auth/verify           - Verify current token
```

### HRMS Sync
```
POST   /sync/trigger          - Manually trigger sync
GET    /sync/logs             - View sync logs
```

---

## 📊 Technology Stack

### Backend
- **Framework**: CodeIgniter 4
- **Language**: PHP 8.2 LTS
- **API**: REST + JSON

### Database & Cache
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Session**: Redis

### Libraries
- **Authentication**: Firebase JWT
- **AWS**: AWS SDK for PHP
- **HTTP Client**: Guzzle
- **Testing**: PHPUnit

### Deployment
- **Web Server**: Nginx
- **Container**: Docker
- **CI/CD**: GitHub Actions (configurable)

---

## 🔐 Security Features

- ✅ JWT (RS256) authentication
- ✅ AES-256 encryption for PII
- ✅ RBAC with field-level masking
- ✅ CSRF protection
- ✅ XSS prevention
- ✅ SQLi prevention (parameterized queries)
- ✅ Rate limiting
- ✅ Comprehensive audit logging
- ✅ Session hijack prevention

---

## 📈 Performance Targets

- **API Response Time**: < 500ms (p95)
- **Profile Load Time**: < 1.5 seconds (p95)
- **Database Queries**: Indexed & optimized
- **Cache Hit Rate**: > 80%
- **Uptime SLA**: 99.9%

---

## 🧪 Testing

### Run tests
```bash
composer test
```

### Code quality
```bash
composer lint
```

### Database migrations
```bash
php spark migrate
php spark migrate:rollback
```

---

## 📚 Documentation

- [PHASE_1_DETAILS.md](PHASE_1_DETAILS.md) - Detailed Phase 1 implementation
- [docs/api/](docs/api/) - API documentation (Swagger coming soon)
- [docs/database/](docs/database/) - Database schema documentation

---

## 🤝 Contributing

1. Create feature branch: `git checkout -b feature/module-name`
2. Commit changes: `git commit -m "Add feature"`
3. Push to branch: `git push origin feature/module-name`
4. Submit pull request

---

## 📞 Support

For issues or questions, refer to:
- Phase 1 Details: [PHASE_1_DETAILS.md](PHASE_1_DETAILS.md)
- Implementation Plan: [IMPLEMENTATION_PLAN.md](../IMPLEMENTATION_PLAN.md)
- Design Summary: [DESIGN_MODULES_SUMMARY.md](../DESIGN_MODULES_SUMMARY.md)

---

**Status**: Phase 1 - Ready for Development

**Last Updated**: February 24, 2026
