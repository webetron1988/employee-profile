# Employee Profile System - Production Implementation Plan

## Project Overview

This document outlines the comprehensive production implementation plan for the Employee Profile System—an enterprise-grade, 360° Employee Digital Identity + Talent + Compliance platform, integrated with HRMS.

### System Objective
Build a separate Employee Profile platform connected to HRMS using:
- SSO authentication (JWT/SAML)
- HRMS-driven Roles & Permissions (no duplication)
- Controlled data sync
- Enterprise security
- Full employee lifecycle coverage

The system acts as a 360° Digital Identity layer while HRMS remains the system of record for core employment data.

---

## 1. System Architecture

### Application Model
- **Independent Application**: Separate database and backend infrastructure
- **Integrated with HRMS**: Connected via SSO + APIs for seamless authentication and data sync
- **Modular Architecture**: Microservice-ready design for scalability
- **360° Coverage**: Full employee digital identity, talent, and compliance layer

### Core Components
1. **Profile Core** - Employee profile management
2. **Identity & Compliance** - Government IDs, bank details, compliance documents
3. **Talent & Performance** - Skills, competencies, performance ratings
4. **Learning & Development** - Training, certifications, career development
5. **Career & Mobility** - Promotions, transfers, career roadmap
6. **Sync Engine** - Bi-directional data sync with HRMS
7. **Permission Enforcement Layer** - RBAC based on HRMS
8. **Audit & Security Layer** - Compliance and monitoring

---

## 2. Scope Summary

### Current Development Phase (All Modules - Production Ready)

#### Core Profile
- Personal Profile (Employee summary card)
- Personal & Identity Information
- Health & Medical Details (Encrypted)
- Family & Dependents
- Contact & Social
- Languages, Hobbies & Talents
- Appreciation & Patents

#### Compliance & Employment
- Identity & Compliance (Critical)
- Job & Organization (Synced from HRMS)
- Recruitment & Onboarding
- Work Authorization & Legal Status

#### Talent & Performance Management
- Performance & Goals
- Talent Management (Skills, Competency, 360 Feedback, 9-Box)
- Career & Mobility (Promotions, Transfers, Aspirations)

#### Learning & Development
- Training History
- Certifications
- Individual Development Plan (IDP)
- Mentoring & Coaching
- Skill Gap Analysis

---

## 3. Technology Stack (Production)

### Backend
- **Framework**: CodeIgniter 4
- **PHP Version**: 8.2 LTS
- **API Architecture**: REST + JSON
- **Authentication**: SSO JWT (RS256)
- **Queuing**: Redis / RabbitMQ
- **Caching**: Redis

### Database
- **Primary**: MySQL 8.0
- **Session & Cache**: Redis
- **Optional**: Elasticsearch (for advanced search)

### File Storage & Infrastructure
- **File Storage**: AWS S3
- **Web Server**: Nginx with SSL (mandatory)
- **Deployment**: Docker-ready CI/CD pipeline
- **Logging**: Centralized logging system

### Security & Encryption
- **Algorithm**: AES-256 for sensitive data
- **Transmission**: SSL/TLS enforced
- **Key Management**: Secure vault

---

## 4. HRMS Integration Strategy

### SSO Authentication Flow
1. User initiates login on Employee Profile
2. Redirects to HRMS SSO endpoint
3. HRMS validates credentials
4. HRMS generates signed JWT token (RS256, exp < 60 sec)
5. Employee Profile receives JWT
6. Profile validates token signature
7. Permissions and role loaded from JWT
8. Auto-login established

### Permission Source (Single Source of Truth: HRMS)
- User roles fetched from HRMS
- Module access defined in HRMS
- Data scope (self/team/org) defined in HRMS
- Organization hierarchy inherited from HRMS

### Read-Only Data Sync from HRMS
- Employee Master (ID, Name, DOB)
- Job Information (Title, Designation, Department)
- Organizational Structure (Department, Location, Cost Centre)
- Manager Information
- Employment Status & History
- Work Arrangement Details

**Sync Frequency**: Real-time via API + scheduled batch (hourly)

---

## 5. Data Ownership Model

### Owned by HRMS (Source of Truth)
- Employee ID
- Job information & designation
- Organizational structure
- Manager relationships
- Employment status
- Cost centre & location
- Work arrangement

### Owned by Profile System (Exclusive)
- Personal profile information
- Family & dependents records
- Health data (encrypted)
- Identity & compliance documents
- Achievements & appreciation records
- Talent & performance data
- Career aspirations & roadmap
- Learning & training history
- Skills & competency inventory
- 360 feedback & performance ratings

---

## 6. Functional Scope (Detailed)

### 6.1 Core Profile
- Profile summary card with picture
- Contact information display
- Auto-calculated work experience
- Achievements counter
- Rating & endorsements

### 6.2 Personal & Identity
- Name, Date of Birth, Nationality
- Passport & Visa information
- Religion & Marital status
- Secondary identification

### 6.3 Health (Encrypted)
- Blood group
- Medical alerts & conditions
- Disability information
- Allergy records

### 6.4 Family & Dependents
- Relationship validation rules
- Repeatable records (spouse, children, nominees)
- Contact information per dependent
- Dependency status tracking

### 6.5 Contact & Social
- Emergency contact (mandatory)
- Address validation & storage
- Social media links
- Communication preferences

### 6.6 Languages, Hobbies & Talents
- Repeatable structured records
- Proficiency levels
- Years of experience
- Endorsements capability

### 6.7 Appreciation & Patents
- Approval workflow (3-level mandatory)
- Monetary validation & cap
- Category classification
- Date tracking & visibility controls

### 6.8 Identity & Compliance (Critical Module)
- Government IDs (PAN, Aadhar, DL, Passport) - Encrypted
- Bank details (Account, IFSC) - Encrypted & masked
- NDA & Legal agreements
- Statutory compliance documents
- Background verification status
- Work authorization status
- Legal status & restrictions

### 6.9 Job & Organization (Synced + Extended)
- Reporting structure visualization
- Cost centre & location
- Work arrangement (On-site/Hybrid/Remote)
- Employment type validation
- Historical job records
- Manager profile link

### 6.10 Recruitment & Onboarding
- Applicant lineage tracking
- Offer letter details
- Probation period tracking
- Confirmation status & date
- Joining documentation

### 6.11 Performance & Goals
- Performance ratings history
- Goal tracking & achievement
- Feedback & comments
- Review cycle management
- Rating distribution analytics

### 6.12 Talent Management
- Skills inventory & proficiency levels
- Competency framework alignment
- 360-degree feedback results
- 9-box matrix positioning
- Succession planning status

### 6.13 Career & Mobility
- Promotion history & eligibility
- Career aspirations & preferences
- Transfer records & requests
- Career roadmap visualization
- Growth opportunity mapping

### 6.14 Learning & Development
- Training course enrollment & completion
- Certification records & validity
- Individual Development Plan (IDP)
- Mentoring relationships & sessions
- Skill gap analysis & recommendations
- Learning achievements & badges

---

## 7. Role & Permission Framework

### Permission Source
- **Single Source of Truth**: HRMS only
- **No Duplication**: Profile system enforces, never redefines
- **Inheritance**: All permissions inherited from HRMS roles

### Permission Levels

#### Module Access
- Employee can access assigned modules only
- Manager can access assigned modules + team data
- HR can access their designated scope
- Admin has full system access

#### Action-Level Permissions
- View (Read access)
- Create (Create new records)
- Edit (Update existing records)
- Delete (Soft delete only)
- Approve (Workflow approval access)
- Export (Data export capability)

#### Field-Level Masking
- Sensitive fields masked for unauthorized roles
- Bank account numbers partially masked
- Government IDs encrypted & visibility controlled
- Health records accessible only to authorized personnel
- Email & phone displayed based on role

#### Data Scope
- **Self**: Access to own profile only
- **Team**: Access to direct reports' profile
- **Organization**: Access to entire org structure
- **Custom**: Department/Location-based access

### Mandatory Permission Controls
1. JWT validation on every API call
2. Permission check before data retrieval
3. Sensitive field masking applied during serialization
4. Unauthorized requests rejected with 403 Forbidden
5. Audit log entry for every access attempt
6. Real-time revocation when role changes in HRMS

---

## 8. Validation Rules & Data Integrity

### Email & Contact
- RFC 5322 email format validation
- Mobile number: Country-specific regex
- Phone formatting & validation
- Emergency contact mandatory

### Date & Time
- Date of Birth < Today (age > 18 years for compliance)
- Valid date ranges (start_date < end_date)
- ISO 8601 format enforcement

### Government ID & Banking
- Government ID globally unique (enforced at DB)
- IFSC code valid against RBI list
- Account number format validation
- PAN format: AAAAA0000A
- Aadhar: 12-digit validation
- Passport: Country-specific format

### Percentage & Currency
- Split percentages sum to 100%
- Monetary amounts: ISO 4217 currency codes
- Salary components valid
- Budget allocation validated

### Enums & Controlled Values
- Employment type: Full-time, Part-time, Contract, Temporary
- Marital status: Single, Married, Widowed, Divorced
- Gender: Defined options
- Relationship: Parent, Spouse, Child, Sibling, Guardian
- Status: Active, Inactive, Suspended, On-leave
- Blood group: Valid blood type codes

### Compliance Locked Fields
- Once submitted, compliance documents cannot be edited
- Only approvers can modify locked fields
- Revision requires explicit approval workflow

---

## 9. Security Architecture

### Authentication & Authorization
- **SSO Protocol**: JWT (RS256) with asymmetric signing
- **Token Expiry**: < 60 seconds for access tokens
- **Refresh Tokens**: Secure, long-lived, single-use
- **Token Validation**: Signature, expiry, issuer check mandatory
- **Session Management**: Redis-based, server-side validation

### Encryption Strategy

#### AES-256 Encryption for:
- Government IDs (PAN, Aadhar, DL, Passport)
- Bank Account & IFSC
- Health records & medical alerts
- Sensitive compliance documents

#### Encryption Key Management
- Secure vault for encryption keys
- Key rotation policy (annual minimum)
- Environment-specific keys
- No keys in source code

### Security Controls

#### Network Security
- SSL/TLS mandatory for all communications
- HTTPS only, no HTTP fallback
- Certificate pinning for HRMS endpoints

#### Application Security
- CSRF tokens on all state-changing operations
- XSS protection: Input sanitization + output encoding
- SQLi prevention: Parameterized queries via ORM
- Rate limiting: 100 requests/minute per user
- Session hijack prevention: User-agent + IP binding

#### API Security
- API key authentication for service-to-service calls
- Request signing for sensitive operations
- Input validation on all endpoints
- Output encoding before serialization
- Error messages: Generic (no system details leaked)

#### Audit & Logging (Mandatory)

**Field Change History**:
- Who made the change (user ID)
- When (timestamp, timezone)
- What changed (field name)
- Old value vs New value
- Change reason (if mandatory)

**Sensitive Access Logging**:
- Bank details access: Logged with justification
- Health data access: Logged with context
- Government ID access: All accesses logged
- Compliance document access: Logged per document

**Log Retention**: 7 years for compliance documents, 2 years for general logs

---

## 10. API Scope & Endpoints

### Authentication Endpoints
```
POST   /sso/login                    - Receive JWT from HRMS
POST   /token/refresh                - Refresh expired token
POST   /logout                       - Invalidate session
```

### HRMS Sync Endpoints
```
GET    /employee/basic               - Fetch employee master data
GET    /employee/job                 - Fetch job information
GET    /employee/org                 - Fetch organizational data
GET    /employee/manager             - Fetch manager details
POST   /sync/trigger                 - Manual sync trigger (Admin)
```

### Profile Endpoints
```
GET    /profile/me                   - Get current user profile
PUT    /profile/me                   - Update own profile
GET    /profile/:employee_id         - Get specific profile (with permissions)
DELETE /profile/:employee_id         - Soft delete profile (Admin)
```

### Documents & Compliance Endpoints
```
GET    /documents/list               - List all documents
POST   /documents/upload             - Upload document
GET    /documents/:doc_id/download   - Download document
DELETE /documents/:doc_id            - Archive document
GET    /compliance/status            - Get compliance checklist status
```

### Identity & Compliance Endpoints
```
GET    /identity/details             - Get identity information
PUT    /identity/details             - Update identity
GET    /bank/details                 - Get bank information (masked)
PUT    /bank/details                 - Update bank details
GET    /nda/list                     - List signed NDAs
POST   /nda/sign                     - Sign NDA (workflow)
```

### Talent & Performance Endpoints
```
GET    /skills/list                  - List employee skills
POST   /skills/add                   - Add new skill
PUT    /skills/:skill_id             - Update skill
DELETE /skills/:skill_id             - Remove skill
GET    /career/path                  - Get career roadmap
GET    /performance/reviews          - List performance reviews
POST   /performance/goals            - Create goal
GET    /talent/nineBox               - Get 9-box matrix data
```

### Learning & Development Endpoints
```
GET    /learning/courses             - Available courses
POST   /learning/enroll              - Enroll in course
GET    /learning/progress            - Get learning progress
GET    /certifications/list          - List certifications
POST   /certifications/upload        - Upload certification
GET    /idp/plan                     - Get IDP plan
```

### Admin/Manager Endpoints
```
GET    /team/members                 - Get team members
GET    /team/performance             - Get team performance
POST   /approvals/pending            - Get pending approvals
PUT    /approvals/:approval_id       - Approve/Reject
GET    /audit/logs                   - Access audit logs (limited)
GET    /reports/export               - Export employee data
```

---

## 11. Data Governance

### Data Lifecycle Management
- **Soft Delete Only**: No hard deletion of employee records
- **No Delete for Compliance**: Compliance documents preserved indefinitely
- **Versioning Enabled**: All critical changes tracked with version history

### Data Storage
- **Derived Data**: Never stored; calculated on-the-fly
- **PII Masking**: Applied during storage and retrieval
- **Encryption**: Sensitive data encrypted at rest and in transit

### Data Export & Control
- Export only for authorized roles
- Export audit trail maintained
- Data anonymization for analytics
- Consent tracking for data usage

### Data Retention Policy
- **Active Users**: Full retention during employment + 6 months post-exit
- **Compliance Documents**: 7-year retention (regulatory)
- **Audit Logs**: 2-year retention (7 years for sensitive access)
- **Backups**: 30-day retention with point-in-time restore

### Backup & Disaster Recovery
- **Frequency**: Daily encrypted backups
- **Storage**: Off-site redundant storage
- **Recovery**: Point-in-time restore capability
- **RTO**: < 4 hours
- **RPO**: < 1 hour

---

## 12. Non-Functional Requirements

### Performance
- **Profile Load Time**: < 1.5 seconds (p95)
- **API Response Time**: < 500ms (p95)
- **Module Lazy Loading**: Progressive loading for heavy modules
- **Caching Strategy**: 15-min cache for profile, 1-hour for org data
- **Database Indexing**: On foreign keys, email, employee_id, created_at

### Availability & Reliability
- **Uptime SLA**: 99.9% (8.76 hours downtime/year)
- **Failover**: Automatic failover to standby instance
- **Retry Logic**: 3 retries with exponential backoff for API calls
- **Rate Limiting**: 100 req/min per user, 1000 req/min per IP
- **Circuit Breaker**: For HRMS API calls

### Scalability
- **Horizontal Scaling**: Stateless backend for easy scaling
- **Database Sharding**: By employee_id for future growth
- **Load Balancing**: Round-robin with health checks
- **CDN**: For static assets (CSS, JS, images)

### Maintainability
- **Code Quality**: SonarQube Score > 80%
- **Test Coverage**: > 80% unit + integration tests
- **Documentation**: API docs (Swagger), deployment guides
- **Version Control**: Git with semantic versioning

### Security
- **Penetration Testing**: Annual security audit
- **Dependency Scanning**: Weekly OWASP dependency checks
- **SSL Certificate**: Auto-renewal, minimum TLS 1.2
- **WAF Rules**: OWASP Top 10 protection

---

## 13. Deployment Model

### Infrastructure
- **Backend**: Independent CodeIgniter 4 application
- **Database**: Independent MySQL 8.0 instance
- **Cache/Queue**: Redis cluster
- **File Storage**: AWS S3 integration
- **Load Balancer**: Nginx + SSL

### Architecture Pattern
- **API Gateway**: Optional (recommended for future)
- **Microservices Ready**: Modular design for future breakdown
- **Message Queue**: For asynchronous operations (email, notifications)
- **Logging**: ELK stack (Elasticsearch, Logstash, Kibana)

### Deployment Process
- **CI Pipeline**: Automated tests, security scan, build
- **CD Pipeline**: Staged deployment (dev → staging → production)
- **Rollback**: 1-click rollback to previous version
- **Release**: Blue-green deployment for zero downtime

---

## 14. Compliance & Enterprise Readiness

### Regulatory Compliance
- **GDPR**: Right to be forgotten (soft delete), consent tracking
- **Data Privacy**: PII encryption, masking, audit trails
- **ISO 27001**: Information security management system
- **SOC 2 Type II**: Security, availability, processing integrity

### Data Governance
- **Privacy by Design**: Data minimization, purpose limitation
- **Consent Management**: Tracking & audit trails
- **Retention Policies**: Defined & enforced
- **Data Classification**: PII, Confidential, Internal, Public

### Access Controls
- **RBAC**: Role-based access enforced at every layer
- **Field-Level Access**: Granular control over sensitive data
- **Audit Trail**: Every access logged for compliance
- **Segregation of Duties**: Approval workflows for critical actions



---

## 15. Production Readiness Checklist

### Authentication & Integration
- [ ] SSO JWT integration with HRMS complete
- [ ] Token validation (RS256) implemented
- [ ] Refresh token mechanism secure
- [ ] Permission inheritance from HRMS working
- [ ] Role mapping tested and validated

### Security & Encryption
- [ ] AES-256 encryption enabled for PII
- [ ] SSL/TLS enforced on all endpoints
- [ ] CSRF protection implemented
- [ ] XSS prevention active
- [ ] SQLi protection (parameterized queries) in place

### Data & Compliance
- [ ] Encryption keys secured in vault
- [ ] Soft delete implemented (no hard deletes)
- [ ] Audit logging active for all changes
- [ ] Field-level change history tracking
- [ ] Sensitive access logging enabled

### HRMS Integration
- [ ] Employee master data sync working
- [ ] Job information sync validated
- [ ] Org hierarchy sync verified
- [ ] Manager relationships synced
- [ ] Sync error handling & retry logic

### API & Backend
- [ ] REST API endpoints implemented
- [ ] Permission checks on every endpoint
- [ ] Input validation enforced
- [ ] Error responses generic (no info leakage)
- [ ] Rate limiting configured

### Database
- [ ] MySQL 8.0 schema created
- [ ] Foreign keys & indexes configured
- [ ] Redis caching setup
- [ ] Backup & restore tested
- [ ] Point-in-time recovery verified

### Performance & Monitoring
- [ ] Profile load < 1.5 sec verified
- [ ] API response time < 500ms (p95)
- [ ] Redis caching effective
- [ ] CDN setup for static assets
- [ ] Logging centralized & monitored

### User Interface
- [ ] All designs integrated
- [ ] Field masking applied (sensitive data)
- [ ] Form validations working
- [ ] Mobile responsive verified
- [ ] Accessibility (WCAG 2.1) compliant

### Testing & QA
- [ ] Unit tests > 80% coverage
- [ ] Integration tests for API flows
- [ ] Permission tests comprehensive
- [ ] Security tests (OWASP Top 10)
- [ ] Load testing (1000 concurrent users)
- [ ] UAT completed

### Deployment & DevOps
- [ ] CI/CD pipeline configured
- [ ] Docker containerization ready
- [ ] Blue-green deployment setup
- [ ] Rollback procedure tested
- [ ] Monitoring & alerting active

### Compliance & Documentation
- [ ] API documentation (Swagger) complete
- [ ] Deployment guide created
- [ ] Data governance policy documented
- [ ] Privacy policy updated
- [ ] GDPR & ISO 27001 ready
- [ ] Audit logs retention policy set

---

## 16. Production Database Schema

### Core Employer & Identity Tables

#### employees
```
- employee_id (PK)
- hrms_employee_id (FK to HRMS, UNIQUE)
- email (UNIQUE)
- first_name
- last_name
- date_of_birth
- nationality
- phone
- profile_picture_url (AWS S3)
- status (active, inactive, suspended, on_leave)
- created_at
- updated_at
- deleted_at (soft delete)
```

#### personal_details
```
- id (PK)
- employee_id (FK)
- gender
- marital_status
- blood_group
- religion
- passport_number_encrypted
- passport_expiry
- visa_status
- work_authorization_number_encrypted
- work_authorization_expiry
- created_at
- updated_at
```

#### health_records (Encrypted)
```
- id (PK)
- employee_id (FK)
- blood_group
- medical_alerts (encrypted)
- allergies (encrypted)
- disability_status
- disability_type
- special_needs (encrypted)
- created_at
- updated_at
```

#### govt_ids (Encrypted)
```
- id (PK)
- employee_id (FK)
- id_type (PAN, Aadhar, DL, Passport)
- id_number_encrypted (AES-256)
- id_issuing_date
- id_expiry_date
- verified
- created_at
- updated_at
```

#### bank_details (Encrypted)
```
- id (PK)
- employee_id (FK)
- account_number_encrypted (AES-256, masked display)
- ifsc_code
- bank_name
- account_holder_name
- account_type
- is_primary
- verified
- created_at
- updated_at
```

#### family_dependents
```
- id (PK)
- employee_id (FK)
- relationship_type (spouse, child, parent, sibling, guardian, nominee)
- first_name
- last_name
- date_of_birth
- contact_number
- email
- is_dependent
- is_nominee
- created_at
- updated_at
```

#### emergency_contacts
```
- id (PK)
- employee_id (FK)
- name
- relationship
- phone (primary)
- phone_secondary
- email
- address
- is_primary
- created_at
- updated_at
```

#### addresses
```
- id (PK)
- employee_id (FK)
- address_type (residential, communication, permanent)
- street_address
- city
- state
- postal_code
- country
- latitude
- longitude
- is_current
- created_at
- updated_at
```

#### contact_preferences
```
- id (PK)
- employee_id (FK)
- email_notifications
- sms_notifications
- phone_call_allowed
- preferred_contact_method
- do_not_contact_date
- created_at
- updated_at
```

#### social_profiles
```
- id (PK)
- employee_id (FK)
- platform (LinkedIn, Twitter, GitHub, etc.)
- profile_url
- handle
- verified
- created_at
- updated_at
```

### Job & Organization Tables (HRMS Synced)

#### job_information
```
- id (PK)
- employee_id (FK)
- hrms_job_id (FK to HRMS)
- job_title
- job_title_code
- department_id (FK)
- manager_id (FK to employees)
- location_id (FK)
- cost_centre_code
- cost_centre_name
- employment_type (full-time, part-time, contract, temporary)
- employment_status (active, on_leave, on_probation, confirmed)
- work_arrangement (on-site, hybrid, remote)
- start_date
- probation_end_date
- confirmation_date
- end_date (NULL for current)
- is_current
- synced_from_hrms
- last_synced_at
- created_at
- updated_at
```

#### employment_history
```
- id (PK)
- employee_id (FK)
- job_title
- department
- manager_id (FK)
- start_date
- end_date
- reason_for_change
- created_at
```

#### org_hierarchy (HRMS Synced)
```
- id (PK)
- department_id (UNIQUE, FK to HRMS)
- department_name
- parent_department_id (FK)
- cost_centre_code
- location
- manager_id (FK to employees)
- employee_count
- budget_allocation
- synced_from_hrms
- last_synced_at
- created_at
- updated_at
```

#### team_reports
```
- id (PK)
- manager_id (FK to employees)
- employee_id (FK to employees)
- reporting_type (direct, indirect)
- start_date
- end_date (NULL for current)
- created_at
```

### Compliance & Documentation Tables

#### compliance_documents
```
- id (PK)
- employee_id (FK)
- document_type (NDA, Offer Letter, Confirmation, Statutory, Background Report, Legal Agreement)
- document_name
- document_url (AWS S3, encrypted)
- document_hash (for verification)
- status (pending, approved, rejected, signed, expired)
- issued_date
- expiry_date
- signed_date
- signed_by_user_id
- created_at
- updated_at (no soft delete)
```

#### nda_agreements
```
- id (PK)
- employee_id (FK)
- agreement_type (Standard, Confidentiality, Non-Compete, IP Assignment)
- agreement_version
- issued_date
- signature_date
- expiry_date
- status (pending, signed, expired)
- document_url (AWS S3)
- created_at
```

#### background_verification
```
- id (PK)
- employee_id (FK)
- verification_type (educational, criminal, employment, reference)
- status (pending, in_progress, verified, failed)
- verification_date
- verified_by
- report_url (AWS S3)
- comments
- created_at
- updated_at
```

### Recruitment & Onboarding Tables

#### recruitment_journey
```
- id (PK)
- employee_id (FK)
- applicant_id (reference to HRMS)
- job_opening_id
- application_date
- offer_date
- offer_amount
- offer_currency
- accepted_date
- offer_status
- created_at
```

#### onboarding_checklist
```
- id (PK)
- employee_id (FK)
- task_name
- task_category (documentation, it, access, orientation)
- status (pending, completed)
- assigned_to
- due_date
- completion_date
- created_at
```

### Performance & Goals Tables

#### performance_reviews
```
- id (PK)
- employee_id (FK)
- reviewer_id (FK to employees)
- review_cycle
- review_period_start
- review_period_end
- overall_rating (1-5)
- feedback
- strengths
- improvements
- status (draft, submitted, approved)
- created_at
- updated_at
```

#### performance_goals
```
- id (PK)
- employee_id (FK)
- goal_name
- goal_description
- goal_category (business, development, learning, personal)
- target_date
- completion_date
- status (not_started, in_progress, completed, on_hold, cancelled)
- progress_percentage
- alignment_to_org
- created_at
- updated_at
```

#### performance_feedback
```
- id (PK)
- performance_review_id (FK)
- feedback_type (360_feedback, manager_feedback, peer_feedback)
- feedback_provider_id (FK to employees)
- feedback_text
- rating
- is_anonymous
- created_at
```

#### ratings_9box
```
- id (PK)
- employee_id (FK)
- assessment_date
- performance_rating (1-5)
- potential_rating (1-5)
- matrix_position (9-box position)
- succession_category
- development_priority
- created_at
```

### Talent Management Tables

#### skills
```
- id (PK)
- skill_name (UNIQUE)
- skill_category
- description
- proficiency_levels (1-no, 2-beginner, 3-intermediate, 4-advanced, 5-expert)
- created_at
```

#### employee_skills
```
- id (PK)
- employee_id (FK)
- skill_id (FK)
- proficiency_level (1-5)
- years_of_experience
- verified
- verified_by_id (FK to employees)
- endorsement_count
- acquired_date
- created_at
- updated_at
```

#### competencies
```
- id (PK)
- competency_name
- competency_category
- description
- proficiency_matrix (JSON)
- is_mandatory
- created_at
```

#### employee_competencies
```
- id (PK)
- employee_id (FK)
- competency_id (FK)
- proficiency_level
- assessed_by_id
- assessment_date
- created_at
```

#### career_development
```
- id (PK)
- employee_id (FK)
- career_aspiration
- desired_role
- desired_location
- growth_opportunities
- skill_gaps
- development_plan (JSON)
- mentoring_status
- mentor_id (FK to employees)
- created_at
- updated_at
```

#### promotions
```
- id (PK)
- employee_id (FK)
- promotion_date
- from_designation
- to_designation
- salary_increase_percentage
- effective_date
- approval_status
- approved_by_id
- created_at
```

#### transfers
```
- id (PK)
- employee_id (FK)
- transfer_date
- from_department
- to_department
- from_location
- to_location
- reason
- approval_status
- approved_by_id
- created_at
```

#### succession_planning
```
- id (PK)
- position_id
- primary_successor_id (FK to employees)
- secondary_successor_id (FK to employees)
- readiness_level (ready_now, ready_1year, ready_3years)
- development_required
- created_at
- updated_at
```

### Learning & Development Tables

#### courses
```
- id (PK)
- course_name
- course_code
- description
- category (technical, soft_skills, compliance, leadership)
- instructor_id
- duration_hours
- delivery_mode (online, classroom, hybrid, self_paced)
- start_date
- end_date
- max_participants
- created_at
```

#### course_enrollments
```
- id (PK)
- employee_id (FK)
- course_id (FK)
- enrollment_date
- status (enrolled, in_progress, completed, dropped, deferred)
- completion_date
- score
- grade
- certificate_url (AWS S3)
- certificate_issued_date
- feedback_rating
- created_at
```

#### certifications
```
- id (PK)
- employee_id (FK)
- certification_name
- issuing_body
- issue_date
- expiry_date
- certification_number
- certificate_url (AWS S3)
- verified
- created_at
```

#### individual_development_plan
```
- id (PK)
- employee_id (FK)
- planning_period
- current_role
- desired_role
- skill_gaps (JSON)
- training_plan (JSON)
- mentoring_plan (JSON)
- created_by_id
- approved_by_id
- approval_date
- review_date
- created_at
- updated_at
```

#### training_history
```
- id (PK)
- employee_id (FK)
- training_name
- training_type (internal, external, online)
- training_date
- duration
- trainer_name
- trainer_id (FK to employees)
- outcome
- skills_gained
- cost
- created_at
```

#### mentor_relationships
```
- id (PK)
- mentor_id (FK to employees)
- mentee_id (FK to employees)
- relationship_start_date
- relationship_end_date
- mentor_type (career, skill, peer)
- session_count
- session_frequency
- status (active, paused, completed)
- created_at
```

### Appreciation & Awards Tables

#### awards_recognition
```
- id (PK)
- employee_id (FK)
- award_name
- award_category
- award_date
- award_level (team, department, company)
- award_value (monetary)
- currency
- description
- awarded_by_id
- approval_status (pending, approved, rejected)
- approved_by_id
- created_at
```

#### patents
```
- id (PK)
- employee_id (FK)
- patent_title
- patent_number
- filing_date
- grant_date
- patent_url
- inventors (JSON)
- monetization_status
- royalty_percentage
- created_at
```

### Audit & Security Tables

#### audit_logs
```
- id (PK)
- user_id (FK)
- employee_id (FK - whose data)
- module (profile, compliance, talent, learning)
- action (view, create, update, delete, approve, export)
- entity_type
- entity_id
- old_value
- new_value
- change_reason
- ip_address
- user_agent
- status (success, failure)
- created_at
```

#### sensitive_access_logs
```
- id (PK)
- accessing_user_id (FK)
- target_employee_id (FK)
- resource_type (bank_details, govt_id, health_records, compliance_doc)
- access_type (view, download, export)
- access_reason
- ip_address
- accessed_at
```

#### api_audit
```
- id (PK)
- user_id (FK)
- endpoint
- method (GET, POST, PUT, DELETE)
- request_data (sensitive fields masked)
- response_status
- execution_time_ms
- ip_address
- accessed_at
```

#### field_change_history
```
- id (PK)
- employee_id (FK)
- table_name
- column_name
- old_value
- new_value
- changed_by_id
- change_reason
- changed_at
```

### System Tables

#### users
```
- id (PK)
- email (UNIQUE)
- password_hash
- first_name
- last_name
- employee_id (FK, nullable for non-employees)
- role (admin, manager, employee, hr, system)
- permissions (JSON)
- is_active
- last_login_at
- created_at
- updated_at
```

#### sync_logs
```
- id (PK)
- sync_type (employee_master, job_info, org_hierarchy, manager_relationships)
- status (started, success, failed)
- records_processed
- records_failed
- error_details
- started_at
- completed_at
```

#### system_configurations
```
- id (PK)
- config_key
- config_value (encrypted if sensitive)
- config_type (string, number, boolean, json)
- created_at
- updated_at
```

---

## 17. Database Indexes & Performance

### Critical Indexes
```sql
CREATE INDEX idx_employees_email ON employees(email);
CREATE INDEX idx_employees_hrms_id ON employees(hrms_employee_id);
CREATE INDEX idx_job_information_employee_id ON job_information(employee_id);
CREATE INDEX idx_job_information_manager_id ON job_information(manager_id);
CREATE INDEX idx_audit_logs_employee_id ON audit_logs(employee_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);
CREATE INDEX idx_employee_skills_employee_id ON employee_skills(employee_id);
CREATE INDEX idx_course_enrollments_employee_id ON course_enrollments(employee_id);
CREATE INDEX idx_performance_reviews_employee_id ON performance_reviews(employee_id);
```

---

## 18. Execution Timeline

### Phase 1: Foundation & Setup (Week 1-2)
- [ ] Database schema creation
- [ ] CodeIgniter 4 project setup
- [ ] Authentication & SSO integration
- [ ] Permission middleware implementation
- [ ] Encryption key management setup

**Deliverables**:
- Production-ready database
- SSO authentication working
- Permission system functional

### Phase 2: Core Profile & Compliance (Week 3-4)
- [ ] Personal Profile module
- [ ] Identity & Compliance module
- [ ] Document management
- [ ] HRMS sync engine

**Deliverables**:
- Profile CRUD operations
- Compliance document handling
- HRMS data synchronization

### Phase 3: Job & Organization (Week 5)
- [ ] Job information display
- [ ] Organization chart
- [ ] Team structure
- [ ] Manager relationships

**Deliverables**:
- Org structure visualization
- Team hierarchy display

### Phase 4: Talent & Performance (Week 6-7)
- [ ] Performance review module
- [ ] Goal tracking
- [ ] Skills & competencies
- [ ] 9-box matrix

**Deliverables**:
- Performance dashboard
- Talent inventory

### Phase 5: Learning & Career (Week 8)
- [ ] Learning & Development module
- [ ] Career development plan
- [ ] Certifications & training
- [ ] IDP management

**Deliverables**:
- Learning dashboard
- Career roadmap

### Phase 6: Testing & Optimization (Week 9)
- [ ] Comprehensive testing
- [ ] Performance optimization
- [ ] Security hardening
- [ ] Load testing

**Deliverables**:
- All tests passing
- Performance metrics met
- Security audit passed

### Phase 7: Deployment Preparation (Week 10)
- [ ] Documentation complete
- [ ] CI/CD pipeline setup
- [ ] Monitoring & alerting
- [ ] Production deployment

**Deliverables**:
- Production-ready system
- Deployment procedures
- Operations manual

---

## 19. Implementation Partners & Handoff

### Development Team Structure
- **Backend Lead**: CodeIgniter 4, API, HRMS integration
- **Database Engineer**: MySQL schema, optimization, sync
- **Security Lead**: Encryption, audit, compliance
- **Frontend Lead**: UI/UX implementation, responsive design
- **QA Lead**: Testing strategy, automation
- **DevOps**: Deployment, monitoring, CI/CD

### Knowledge Transfer
- Code documentation
- API documentation (Swagger)
- Deployment guides
- Operations runbook
- Troubleshooting guide

---

**Status**: Ready for Review & Approval

**Last Updated**: February 24, 2026







