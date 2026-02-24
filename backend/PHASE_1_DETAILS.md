# PHASE 1: Foundation & Setup
## Week 1-2 Development Sprint

**Status**: Ready to Start
**Duration**: 2 weeks (10 business days)
**Priority**: CRITICAL - Backend blocker for all modules

---

## 📋 Phase 1 Overview

This is the foundation phase where we establish the core infrastructure. **ALL other modules depend on successful completion of Phase 1.**

### Phase 1 Deliverables (Must Complete Before Phase 2)
1. ✅ Production-ready MySQL database with complete schema
2. ✅ CodeIgniter 4 project fully configured
3. ✅ HRMS SSO authentication working
4. ✅ Permission middleware functional
5. ✅ Encryption system operational

---

## 🛠️ Week 1: Database & Project Setup

### Task 1.1: Database Schema Creation (2-3 days)
**Owner**: Database Engineer

**Deliverable**: Complete MySQL 8.0 database with 30+ tables

**Tables to Create**:

#### Core Identity (Encrypt sensitive fields):
- `employees` - Employee master data
- `personal_details` - Personal information
- `health_records` - Medical data (encrypted)
- `govt_ids` - Government ID documents (encrypted)
- `bank_details` - Banking information (encrypted)
- `family_dependents` - Family records
- `emergency_contacts` - Emergency contact details
- `addresses` - Address information
- `contact_preferences` - Communication preferences
- `social_profiles` - Social media links

#### Job & Organization (HRMS Synced):
- `job_information` - Current job details
- `employment_history` - Employment timeline
- `org_hierarchy` - Department structure
- `team_reports` - Reporting relationships

#### Compliance & Documents:
- `compliance_documents` - Compliance records
- `nda_agreements` - NDA tracking
- `background_verification` - Background check records
- `recruitment_journey` - Recruitment tracking
- `onboarding_checklist` - Onboarding tasks

#### Performance & Goals:
- `performance_reviews` - Performance records
- `performance_goals` - Goal tracking
- `performance_feedback` - Feedback records
- `ratings_9box` - 9-box matrix data

#### Talent Management:
- `skills` - Skill master
- `employee_skills` - Employee skill inventory
- `competencies` - Competency framework
- `employee_competencies` - Employee competencies
- `career_development` - Career plans
- `promotions` - Promotion history
- `transfers` - Transfer records
- `succession_planning` - Succession data

#### Learning & Development:
- `courses` - Course catalog
- `course_enrollments` - Course enrollments
- `certifications` - Certification records
- `individual_development_plan` - IDP records
- `training_history` - Training records
- `mentor_relationships` - Mentoring data

#### Awards & Recognition:
- `awards_recognition` - Awards records
- `patents` - Patent records

#### Audit & Security:
- `audit_logs` - Change management audit
- `sensitive_access_logs` - Sensitive data access log
- `api_audit` - API access log
- `field_change_history` - Field-level change tracking
- `users` - Application users
- `sync_logs` - HRMS sync tracking
- `system_configurations` - Configuration management

**SQL Script**: Create `PHASE1_DATABASE_SCHEMA.sql`

**Key Features**:
- Soft delete enabled (deleted_at column)
- Timestamps on all tables (created_at, updated_at)
- Foreign key relationships enforced
- Indexes on frequently queried fields
- Encryption fields marked for AES-256
- Audit logging structure in place

**Validation Checklist**:
- [ ] All 30+ tables created
- [ ] Foreign keys configured
- [ ] Indexes created (16+ critical indexes)
- [ ] Soft delete column added
- [ ] Sample data inserted for testing
- [ ] Database backed up

---

### Task 1.2: CodeIgniter 4 Project Setup (1-2 days)
**Owner**: Backend Lead

**Deliverable**: Fully configured CI4 project ready for development

**Setup Steps**:

1. **Initialize CodeIgniter 4**
   ```bash
   composer create-project codeigniter4/appstarter employee-profile-backend
   cd employee-profile-backend
   ```

2. **Configure Environment Files**
   - Copy `.env.example` to `.env`
   - Set database credentials
   - Configure HRMS endpoints
   - Set encryption keys
   - Configure AWS S3 access
   - Set Redis connection details

3. **Directory Structure**
   ```
   backend/
   ├── app/
   │   ├── Controllers/
   │   ├── Models/
   │   ├── Middleware/
   │   ├── Libraries/
   │   └── Database/
   │       ├── Migrations/
   │       └── Seeds/
   ├── config/
   ├── public/
   ├── tests/
   ├── writable/
   ├── docs/
   └── composer.json
   ```

4. **Install Dependencies**
   - PHP 8.2 LTS
   - Composer packages:
     - `firebase/php-jwt` - JWT handling
     - `aws/aws-sdk-php` - AWS S3
     - `predis/predis` - Redis client
     - `phpunit/phpunit` - Testing framework
     - `guzzlehttp/guzzle` - HTTP client

5. **Enable Modules**
   - Database library
   - Session library (Redis-based)
   - Encryption library
   - File upload library

**Validation Checklist**:
- [ ] CI4 installed and running on `localhost:8080`
- [ ] `.env` configured with dummy credentials
- [ ] Database connection tested
- [ ] Cache (Redis) connection tested
- [ ] All dependencies installed
- [ ] Directory structure verified

---

## 🔐 Week 2: Authentication & Permissions

### Task 2.1: HRMS SSO Integration (2-3 days)
**Owner**: Backend Lead + Security Lead

**Deliverable**: Working SSO authentication with JWT token handling

**Implementation**:

1. **Create Auth Controller** (`app/Controllers/Auth.php`)
   ```php
   - POST /auth/sso-login - Receive JWT from HRMS
   - POST /auth/refresh - Refresh expired token
   - POST /auth/logout - Invalidate session
   - GET /auth/verify - Verify current token
   ```

2. **JWT Token Handling Library** (`app/Libraries/JwtHandler.php`)
   ```php
   - validateToken($token) - Validate RS256 signature
   - extractClaims($token) - Extract user claims
   - checkTokenExpiry($token) - Verify < 60 sec expiry
   - refreshToken($token) - Generate new token
   ```

3. **HRMS API Integration** (`app/Libraries/HrmsClient.php`)
   ```php
   - getSsoEndpoint() - Get HRMS SSO URL
   - validateJwtSignature($token) - Verify token signature
   - fetchUserPermissions($hrms_id) - Get user permissions
   - syncEmployeeData($hrms_id) - Sync employee master data
   ```

4. **Session Management**
   - Store JWT in Redis session
   - Set expiry < 60 seconds
   - Refresh token before expiry
   - Invalidate on logout

**Flow**:
1. User visits Employee Profile
2. System detects no session
3. Redirects to HRMS SSO endpoint
4. HRMS validates credentials
5. HRMS sends signed JWT (RS256) to Profile
6. Profile validates JWT signature
7. Profile extracts claims (user_id, role, permissions)
8. Profile creates session in Redis
9. User auto-logged in

**Validation Checklist**:
- [ ] SSO endpoint integrates with HRMS
- [ ] JWT signature validation working
- [ ] Token expiry < 60 seconds enforced
- [ ] Users can log in via SSO
- [ ] Sessions created in Redis
- [ ] Token refresh working
- [ ] Logout clears session
- [ ] Token validation error handling

---

### Task 2.2: Permission Middleware (1-2 days)
**Owner**: Security Lead

**Deliverable**: RBAC middleware enforced on all API endpoints

**Implementation**:

1. **Permission Middleware** (`app/Middleware/PermissionMiddleware.php`)
   ```php
   - Validate JWT on every request
   - Check module access from HRMS permissions
   - Validate action permissions (view/edit/create/delete/approve)
   - Apply field-level masking
   - Enforce data scope (self/team/org)
   - Log access attempts
   - Return 403 Forbidden on unauthorized access
   ```

2. **Permission Check Service** (`app/Libraries/PermissionChecker.php`)
   ```php
   - hasModuleAccess($module) - Check module access
   - hasActionAccess($action, $resource) - Check action permission
   - getDataScope($employee_id) - Get visible data scope
   - maskSensitiveFields($data) - Apply field masking
   - logAccessAttempt($details) - Log for audit
   ```

3. **Permission Levels Enforced**
   - **Admin**: Full access to all modules and data
   - **Manager**: Own profile + team profiles + team reports
   - **Employee**: Own profile only
   - **HR**: Designated scope (e.g., talent, learning)

4. **Field-Level Masking**
   - Bank account numbers: Last 4 digits only
   - Government IDs: Encrypted, no display unless authorized
   - Health records: Not displayed unless authorized
   - Sensitive searches: Require explicit permission

**Validation Checklist**:
- [ ] Permission middleware attached to routes
- [ ] JWT validation on every request
- [ ] Module access enforced
- [ ] Action permissions checked
- [ ] Field masking applied
- [ ] Data scope enforced
- [ ] Unauthorized requests return 403
- [ ] Access attempts logged

---

### Task 2.3: Encryption System Setup (1 day)
**Owner**: Security Lead

**Deliverable**: AES-256 encryption for all sensitive fields

**Implementation**:

1. **Encryption Library** (`app/Libraries/Encryptor.php`)
   ```php
   - encrypt($data, $key) - Encrypt data AES-256
   - decrypt($encrypted, $key) - Decrypt data
   - hashField($field) - Hash for uniqueness checks
   - rotateKeys() - Key rotation logic
   ```

2. **Fields to Encrypt** (in database):
   - government_ids.id_number_encrypted
   - bank_details.account_number_encrypted
   - bank_details.ifsc_encrypted
   - personal_details.passport_number_encrypted
   - health_records.medical_alerts
   - health_records.allergies
   - compliance_documents.document_url

3. **Key Management**
   - Store keys in secure vault (AWS KMS recommended)
   - Never commit keys to Git
   - Rotate keys annually
   - Environment-specific keys (dev, staging, prod)
   - Key versioning for decryption of old data

4. **Testing**
   - Encrypt/decrypt sample data
   - Verify data integrity
   - Test key rotation

**Validation Checklist**:
- [ ] Encryption library created
- [ ] All sensitive fields encrypted at rest
- [ ] Decryption working correctly
- [ ] Key storage secure
- [ ] Key rotation tested
- [ ] No keys in source code

---

### Task 2.4: HRMS Data Sync Engine (1-2 days)
**Owner**: Database Engineer

**Deliverable**: Bi-directional sync with HRMS

**Implementation**:

1. **Sync Service** (`app/Libraries/HrmsSyncService.php`)
   ```php
   - syncEmployeesMaster() - Fetch employee master data
   - syncJobInformation() - Fetch job details
   - syncOrgHierarchy() - Fetch org structure
   - syncManagerRelationships() - Fetch manager info
   - handleSyncErrors() - Retry logic with exponential backoff
   ```

2. **Sync Schedule**
   - Real-time: Employee login, role change
   - Hourly batch: Job changes, org updates
   - Daily batch: Full sync for validation

3. **Sync Data Mappings**:
   - HRMS employee_id → Profile employee.hrms_employee_id
   - HRMS job_id → Profile job_information.hrms_job_id
   - HRMS dept_id → Profile org_hierarchy.department_id
   - HRMS manager_id → Profile job_information.manager_id

4. **Error Handling**
   - Log sync errors
   - Retry failed syncs (3 attempts, exponential backoff)
   - Alert on repeated failures
   - Fallback to last known good data

**Sync Log Fields**:
- sync_type (employee_master, job_info, org_hierarchy, manager_relationships)
- status (started, success, failed)
- records_processed
- records_failed
- error_details
- started_at / completed_at

**Validation Checklist**:
- [ ] Employee master sync working
- [ ] Job information sync working
- [ ] Org hierarchy sync working
- [ ] Manager relationships sync working
- [ ] Sync schedule running
- [ ] Error logs created
- [ ] Retry logic working
- [ ] Sync data validated

---

## 🧪 Phase 1 Testing & Validation

### Pre-Production Validation
- [ ] Database schema passes integrity checks
- [ ] All 30+ tables created with correct relationships
- [ ] Indexes created (16+ critical indexes)
- [ ] Soft delete working
- [ ] SSO login flow end-to-end tested
- [ ] JWT token validation working
- [ ] Permission checks enforced
- [ ] Encryption/decryption verified
- [ ] HRMS sync tested
- [ ] Error handling in place
- [ ] Logs captured correctly
- [ ] Performance baseline established

---

## 📊 Phase 1 Success Criteria

| Criteria | Target | Status |
|----------|--------|--------|
| Database Ready | All 30+ tables, indexes, relationships | ⏳ Not Started |
| Auth Working | SSO login, JWT validation, session management | ⏳ Not Started |
| Permission Enforced | RBAC on all endpoints, field masking | ⏳ Not Started |
| Encryption Ready | AES-256 setup, keys secured | ⏳ Not Started |
| HRMS Sync | Employee, Job, Org, Manager data syncing | ⏳ Not Started |
| Error Handling | All failures logged, retry logic | ⏳ Not Started |
| API Response | < 500ms (p95) | ⏳ Not Started |
| Uptime | 99.9% test environment | ⏳ Not Started |

---

## 👥 Phase 1 Team Assignment

| Role | Responsibility | Time |
|------|-----------------|------|
| **Database Engineer** | Schema creation, indexes, sync engine | 5 days |
| **Backend Lead** | CI4 setup, API structure, SSO integration | 5 days |
| **Security Lead** | Encryption, permissions, audit logging | 4 days |
| **DevOps** | Environment setup, Redis, AWS S3, CI/CD | 3 days |

---

## 📅 Phase 1 Timeline

### Week 1 (Days 1-5)
- **Day 1-2**: Database schema creation
- **Day 2-3**: CodeIgniter 4 setup
- **Day 3-4**: HRMS SSO integration
- **Day 5**: Testing & validation

### Week 2 (Days 6-10)
- **Day 6-7**: Permission middleware
- **Day 7-8**: Encryption system
- **Day 8-9**: HRMS sync engine
- **Day 10**: Integration testing & Phase 1 completion

---

## 🚀 Phase 1 → Phase 2 Handoff

**Go/No-Go Checklist** (Before starting Phase 2):
- [ ] All 30+ tables created & tested
- [ ] SSO authentication working
- [ ] Permission middleware enforced
- [ ] Encryption system operational
- [ ] HRMS data sync verified
- [ ] Error handling in place
- [ ] API response times < 500ms
- [ ] Security audit passed
- [ ] Documentation complete
- [ ] Team trained on Phase 1 code

**Phase 2 Start**: Personal Profile module, Identity & Compliance module, HRMS sync finalization

---

**Phase 1 Status**: ⏳ Ready for Development Start

**Last Updated**: February 24, 2026
