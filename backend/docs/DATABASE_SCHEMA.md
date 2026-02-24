# Employee Profile System - Database Schema Documentation

## Overview

This document provides comprehensive documentation of the Employee Profile System database schema. The system uses 30 tables organized into 5 core modules:

1. **Identity & Authentication** (5 tables)
2. **Job & Organization** (3 tables)
3. **Performance Management** (4 tables)
4. **Talent & Learning** (8 tables)
5. **Compliance & Personal** (10 tables)

---

## Migration Sequence

All migrations must be run in order. The sequence is critical due to foreign key dependencies.

### Core System (Migrations 100000-100004)
1. **100000_CreateEmployeesTable** - Core employee identity
2. **100001_CreatePersonalDetailsTable** - Personal information (depends on employees)
3. **100002_CreateUsersTable** - Application authentication (depends on employees)
4. **100003_CreateAuditLogsTable** - Compliance audit trail (depends on users, employees)
5. **100004_CreateSyncLogsTable** - HRMS sync tracking (standalone)

### Job & Organization (Migrations 100005-100007)
6. **100005_CreateJobInformationTable** - Current job position (depends on employees)
7. **100006_CreateEmploymentHistoryTable** - Job change history (depends on employees)
8. **100007_CreateOrgHierarchyTable** - Organizational structure (depends on employees)

### Performance Management (Migrations 100008-100009)
9. **100008_CreatePerformanceReviewsTable** - Annual/periodic reviews (depends on employees)
10. **100009_CreateSkillsTable** - Skill master data (standalone)

### Talent & Learning (Migrations 100010-100014)
11. **100010_CreateEmployeeSkillsTable** - Employee skill mapping (depends on employees, skills)
12. **100011_CreateCompetenciesTable** - Competency master data (standalone)
13. **100012_CreateEmployeeCompetenciesTable** - Employee competency mapping (depends on employees, competencies)
14. **100013_CreateCoursesTable** - Course catalog (depends on skills, competencies)
15. **100014_CreateCourseEnrollmentsTable** - Course enrollment tracking (depends on employees, courses)

### Health & Document Management (Migrations 100015-100017)
16. **100015_CreateHealthRecordsTable** - Medical information (depends on employees)
17. **100016_CreateCertificationsTable** - Professional certifications (depends on employees)
18. **100017_CreateFamilyDependentsTable** - Family member details (depends on employees)

### Career Development (Migrations 100018-100019)
19. **100018_CreatePerformanceGoalsTable** - Goal tracking (depends on employees)
20. **100019_CreateComplianceDocumentsTable** - Legal agreements (depends on employees)

### Personal Information (Migrations 100020-100023)
21. **100020_CreateAddressesTable** - Address records (depends on employees)
22. **100021_CreateEmergencyContactsTable** - Emergency contacts (depends on employees)
23. **100022_CreateGovtIdsTable** - Government IDs - ENCRYPTED (depends on employees)
24. **100023_CreateBankDetailsTable** - Bank accounts - ENCRYPTED (depends on employees)

### Career Management (Migrations 100024-100027)
25. **100024_CreatePromotionsTable** - Promotion records (depends on employees)
26. **100025_CreateTransfersTable** - Transfer records (depends on employees)
27. **100026_CreateIndividualDevelopmentPlanTable** - Career IDP (depends on employees)
28. **100027_CreatePerformanceFeedbackTable** - 360-degree feedback (depends on employees)

### Achievements & Configuration (Migrations 100028-100030)
29. **100028_CreateAwardsRecognitionTable** - Awards & recognition (depends on employees)
30. **100029_CreateTrainingHistoryTable** - Training attendance (depends on employees)
31. **100030_CreateSystemConfigurationsTable** - System settings (standalone)

---

## Table Relationships

### Dependency Graph

```
employees (root)
├── personal_details
├── users
├── job_information
├── employment_history
├── org_hierarchy
├── performance_reviews
├── employee_skills
│   └── (depends on skills)
├── employee_competencies
│   └── (depends on competencies)
├── course_enrollments
│   └── (depends on courses)
├── health_records
├── certifications
├── family_dependents
├── performance_goals
├── compliance_documents
├── addresses
├── emergency_contacts
├── govt_ids
├── bank_details
├── promotions
├── transfers
├── individual_development_plan
├── performance_feedback
├── awards_recognition
└── training_history

audit_logs (depends on users, employees)
sync_logs (standalone)
skills (standalone)
competencies (standalone)
courses (depends on skills, competencies)
system_configurations (standalone)
```

---

## Module Descriptions

### 1. Identity & Authentication Module

#### employees (Core)
- **Records**: All employees in the system
- **Key Fields**: employee_id (PK), hrms_employee_id (unique, for sync), email, first_name, last_name, status
- **Soft Delete**: Enabled (deleted_at field)
- **Indexes**: employee_id, hrms_employee_id, email
- **Foreign Keys**: None (root table)

#### personal_details
- **Records**: Personal identity information per employee
- **Encrypted Fields**: passport_number_encrypted, work_authorization_number_encrypted
- **Unique**: One per employee
- **Sensitive Data**: Government/personal identification info
- **Compliance**: PII protection required

#### users
- **Records**: Application authentication records
- **Optional**: May not correspond to employees (e.g., admin users)
- **Access Control**: role field (admin, hr, manager, employee, system), permissions JSON
- **Security**: password_hash (NOT used for SSO), last_login_at tracking

#### audit_logs (Compliance)
- **Records**: Complete activity trail
- **Retention**: 7-year retention required
- **Granularity**: Field-level change tracking (old_value, new_value)
- **Access Logging**: All API requests logged
- **Indexes**: employee_id, user_id, created_at (for reporting)

#### sync_logs (HRMS Integration)
- **Records**: HRMS synchronization tracking
- **Purpose**: Monitor sync health, identify failures
- **Fields**: sync_type, status, records_processed, records_failed, error_details
- **Monitoring**: For real-time + batch sync operations

---

### 2. Job & Organization Module

#### job_information
- **Records**: Current job details per employee
- **Unique**: One per employee
- **Key Fields**: designation, department, grade, employment_type, employment_status
- **Relationships**: reporting_manager_id (self-join to employees)
- **Scope**: Full-time, part-time, contract, temporary, intern positions
- **Status Tracking**: Active, On Leave, Suspended, Terminated

#### employment_history
- **Records**: Historical job changes (promotions, transfers, demotions)
- **Purpose**: Career progression tracking
- **Fields**: previous/new designation, promotion_reason, approval_status
- **Timeline**: start_date, end_date for each position
- **Approval Workflow**: Pending → Approved/Rejected

#### org_hierarchy
- **Records**: Organizational structure mapping
- **Hierarchy**: parent_id (self-join), hierarchy_path, org_level
- **Manager Detection**: is_manager flag, team_size tracking
- **Purpose**: Org chart generation, reporting structure
- **Scope**: Department, Division, Section, Team levels

---

### 3. Performance Management Module

#### performance_reviews
- **Records**: Annual/periodic performance reviews
- **Key Fields**: overall_rating, performance_status, strengths, areas_for_improvement
- **Reviewers**: reviewer_id (manager), approver (approval_status tracking)
- **Timeline**: review_date, approved_at
- **Status Workflow**: Draft → Pending Approval → Approved/Rejected
- **Compliance**: Full audit trail for performance decisions

#### performance_goals
- **Records**: Individual goal setting (technical, behavioral, leadership, personal development)
- **Tracking**: progress_percentage, achievement_percentage
- **Lifecycle**: Not Started → In Progress → Completed
- **Measurement**: target_value, measurement_criteria, weightage for goal importance
- **Timeline**: start_date, end_date for goal period

#### performance_feedback
- **Records**: 360-degree feedback collection
- **Types**: Manager, Self, Peer, Team, 360 Degree
- **Visibility**: is_anonymous flag for peer feedback, status tracking for employee communication
- **Fields**: strengths, areas_for_improvement, suggestions, overall_comment, rating
- **Workflow**: Pending Review → Shared with Employee → Acknowledged

#### performance_ratings (via skills/competencies)
- **Purpose**: 9-box grid ratings, KRA ratings
- **Integration**: Through employee_competencies table with manager_assessment field
- **Granularity**: Multiple competencies can be rated for comprehensive assessment

---

### 4. Talent & Learning Development Module

#### skills
- **Records**: Master list of skills in organization
- **Fields**: skill_name (unique), skill_category, skill_level, description
- **Master Data**: Maintained by HR, used for assessments
- **Status**: Active/Inactive

#### employee_skills
- **Records**: Employee skill inventory
- **Fields**: proficiency_level (Beginner, Intermediate, Advanced, Expert), years_of_experience
- **Validation**: verified flag, endorsements count
- **Timeline**: last_used_date for skill freshness tracking
- **Unique Constraint**: One skill per employee per skill

#### competencies
- **Records**: Organizational competencies (behavioral, technical, leadership)
- **Structure**: Competency profiles with multiple proficiency levels (JSON)
- **Framework**: Supports competency-based HR processes
- **Status**: Active/Inactive

#### employee_competencies
- **Records**: Employee competency assessment
- **Assessment Types**: 
  - Self-assessment (self_assessment field)
  - Manager assessment (manager_assessment field)
- **Development**: development_goal for gap closure planning
- **Timeline**: assessment_date, created_at for trend analysis
- **Unique Constraint**: One competency per employee per competency

#### courses
- **Records**: Learning catalog
- **Fields**: course_name (unique), course_code, provider, course_type (Online, Classroom, Hybrid, Self-Paced)
- **Duration**: duration_hours, cost tracking
- **Links**: skill_id, competency_id for learning path mapping
- **Status**: Active, Inactive, Archived

#### course_enrollments
- **Records**: Employee course participation
- **Tracking**: completion_status, completion_percentage, score/passing_score
- **Certificates**: certificate_url, certificate_obtained flag
- **Timeline**: enrollment_date, scheduled dates, actual dates
- **Unique Constraint**: One enrollment per employee per course

#### individual_development_plan
- **Records**: Career development roadmap per year
- **Fields**: career_goal, skill_gaps, development_activities, training_needs
- **Mentoring**: mentor_assigned_id for career guidance
- **Approval**: reviewed_by_id, reviewed_date, status tracking
- **Status**: Draft, In Progress, Completed, Postponed

---

### 5. Compliance & Personal Information Module

#### health_records
- **Records**: Medical information per employee
- **Encrypted Fields**: health_insurance_number_encrypted
- **Sensitive Data**: Allergies, chronic_conditions, medications
- **Emergency**: Emergency contact details (name, phone, relation)
- **Compliance**: Medical privacy, health insurance tracking
- **Unique**: One per employee

#### certifications
- **Records**: Professional credentials (PMP, CPA, etc.)
- **Tracking**: issue_date, expiry_date, status (Active, Expired, Revoked, Pending)
- **Validation**: certificate_number, certificate_url for verification
- **Timeline**: Renewal tracking via expiry_date

#### family_dependents
- **Records**: Family member information for benefits/insurance
- **Fields**: name, relationship, date_of_birth, occupation, education_level
- **Benefits**: dependent_for_insurance flag for coverage determination
- **Scope**: Spouse, Child, Parent, Sibling, Other relationships

#### compliance_documents
- **Records**: Legal agreement tracking (NDA, Non-Compete, Confidentiality, etc.)
- **Validation**: signed_date, signed_by_id, approval tracking
- **Lifecycle**: Pending → Signed → Renewed (on expiry)
- **Compliance**: 7-year retention via audit logs

#### addresses
- **Records**: Multiple addresses per employee (residential, permanent, official)
- **Fields**: street_address, city, state, postal_code, country
- **Primary**: is_primary flag for default address
- **Scope**: Support global employee base

#### emergency_contacts
- **Records**: Emergency contact information
- **Fields**: contact_name, relationship, phone_number, email, address
- **Primary**: is_primary flag

#### govt_ids
- **Records**: Government-issued identification
- **ENCRYPTED**: id_number_encrypted, id_number_hash (for lookups without decryption)
- **Types**: Aadhaar, PAN, Passport, Driving License, Voter ID
- **Validation**: verified flag, verification_date, issue_date, expiry_date
- **Primary**: is_primary flag for main ID
- **Compliance**: Sensitive government data protection

#### bank_details
- **Records**: Bank account information
- **ENCRYPTED**: account_number_encrypted, account_number_hash
- **Fields**: bank_name, account_type, ifsc_code, branch_name, account_holder_name
- **Verification**: verified flag for account validation
- **Primary**: is_primary flag (usually 1 account per employee)
- **Compliance**: Financial PII protection

#### promotions
- **Records**: Career advancement history
- **Fields**: previous_designation → new_designation, previous_grade → new_grade
- **Timing**: promotion_date, effective_date
- **Financial**: salary_increment_percentage
- **Approval**: approval_status, approved_by_id, approved_date
- **Reason**: Merit, Seniority, Vacancy, Other

#### transfers
- **Records**: Department/location moves
- **Scope**: Departmental, Geographical, or Both transfer types
- **Timing**: transfer_date, effective_date
- **Approval**: approval_status tracking
- **Reason**: transfer_reason text field

#### awards_recognition
- **Records**: Employee recognition and awards
- **Category**: Performance, Innovation, Safety, Customer Service, Teamwork, Leadership
- **Monetary**: monetary_reward tracking
- **Recognition**: recognized_by_id, award_date, certificate_url
- **Timeline**: Award history for employee engagement

#### training_history
- **Records**: Training attendance
- **Scope**: Technical, Behavioral, Compliance, Leadership, Soft Skills, Other
- **Delivery**: Online, Classroom, Hybrid, On-the-job modes
- **Assessment**: assessment_score, certificate_obtained, feedback
- **Timeline**: training_date, duration_hours, cost tracking
- **Provider**: training_provider, trainer_name

#### system_configurations
- **Records**: System-level settings (not per-employee)
- **Encryption**: is_encrypted flag for sensitive configs
- **Types**: Boolean, Integer, String, JSON, Array
- **Status**: is_active flag for feature toggles
- **Purpose**: System behavior configuration without code changes

---

## Encryption Strategy

### Encrypted Fields (AES-256-CBC)

**Government IDs Table:**
- `govt_ids.id_number_encrypted` - Actual encrypted ID number
- `govt_ids.id_number_hash` - SHA-256 hash for lookup/duplicate detection

**Bank Details Table:**
- `bank_details.account_number_encrypted` - Encrypted bank account number
- `bank_details.account_number_hash` - SHA-256 hash for lookup

**Health Records Table:**
- `health_records.health_insurance_number_encrypted` - Encrypted insurance number

**Personal Details Table:**
- `personal_details.passport_number_encrypted` - Encrypted passport
- `personal_details.work_authorization_number_encrypted` - Encrypted work auth

### Encryption Implementation

1. **At Rest**: All encrypted fields stored in LONGTEXT as base64-encoded ciphertext with IV
2. **In Transit**: TLS/SSL mandatory (configured in .env)
3. **Key Management**: 
   - Primary key from environment (encryption.key in .env)
   - Rotation support via hash strategy
   - Keys never logged or exposed

---

## Indexing Strategy

### Performance Indexes

**Frequent Queries (typically filtered by):**
- `employees`: employee_id (PK), hrms_employee_id, email
- `job_information`: employee_id, department, reporting_manager_id
- `performance_reviews`: employee_id, review_date
- `audit_logs`: employee_id, user_id, created_at, module
- `course_enrollments`: employee_id, course_id

**Sorted/Range Queries (typically sorted/filtered on):**
- `certifications`: expiry_date (for renewal tracking)
- `performance_goals`: status, end_date
- `promotions`: promotion_date
- `training_history`: training_date

**Hash Lookups (for encryption):**
- `govt_ids`: id_number_hash (unique, for duplicate detection)
- `bank_details`: account_number_hash (unique, for duplicate detection)

---

## Foreign Key Relationships

### Cascade Rules

**Scenarios where CASCADE is appropriate:**
- Employee deletion → Delete all related records (personal_details, skills, goals, etc.)
- Course deletion → Mark enrollments as orphaned

**Scenarios where SET NULL is appropriate:**
- Manager deletion → Set reporting_manager_id to NULL (won't cascade)
- Course optional link from training_history

### Soft Deletes

- `employees`: Uses soft delete (deleted_at field) - employee records should not be permanently deleted for audit trail
- Others: No soft delete - relies on audit logs for compliance

---

## Audit & Compliance

### Audit Logging

**All changes tracked in `audit_logs`:**
- User making change (user_id)
- Employee affected (employee_id)
- Module/section (performance, personal, job, etc.)
- Action type (create, update, delete, export)
- Field-level tracking (old_value, new_value)
- Reason for change (change_reason)
- Access context (ip_address, user_agent)
- Timestamp (created_at)

**Compliance Requirements:**
- 7-year retention policy
- Immutable after creation
- Field-level change visibility for sensitive data
- Access logging for compliance documents

---

## Sync Strategy (HRMS Integration)

### Real-time Sync Fields

These fields sync immediately from HRMS:
- `employees.hrms_employee_id` - Master ID from HRMS
- `employees.email` - May change in HRMS
- `job_information` - When job changes in HRMS
- Updates tracked in `sync_logs` with status/errors

### Batch Sync Schedule

**Recommended Schedule:**
- Employee master: Nightly (new hires, terminations, status changes)
- Org hierarchy: Weekly (manager/department changes)
- Job info: On-demand after bulk updates

**Tracking:**
- `sync_logs.sync_type`: employee_master, job_info, org_hierarchy, manager_relationships
- `sync_logs.records_processed`, `records_failed`
- `sync_logs.error_details` for troubleshooting

---

## Data Validation Rules

### Mandatory Fields (NOT NULL)

- All FK relationships (employee_id in child tables)
- Core identifying fields (employee_id in employees)
- Key dates (start_date, joined_date, etc.)
- Status fields (with enum constraints)

### Enum Constraints

**Employment Status:** Active, On Leave, Suspended, Terminated  
**Employment Type:** Full-Time, Part-Time, Contract, Temporary, Intern  
**Review Status:** Draft, Pending Approval, Approved, Rejected  
**Completion Status:** Not Started, In Progress, Completed, Dropped  
**Promotion Reason:** Merit, Seniority, Vacancy, Other  
**Transfer Type:** Departmental, Geographical, Both  

### Unique Constraints

- `employees.email` - System-wide unique
- `employees.hrms_employee_id` - Link to HRMS
- `personal_details.employee_id` - One per employee
- `job_information.employee_id` - One per employee
- `health_records.employee_id` - One per employee
- `govt_ids.id_number_hash` - Prevent duplicate ID registration
- `bank_details.account_number_hash` - Prevent duplicate account
- `courses.course_code` - Unique course identifier
- `employee_skills.employee_id, skill_id` - One skill per employee
- `employee_competencies.employee_id, competency_id` - One competency per employee
- `course_enrollments.employee_id, course_id` - One enrollment per employee per course

---

## Migration Execution

### Run All Migrations

```bash
php spark migrate
```

### Run Specific Module

```bash
# Run only core system tables
php spark migrate --targetName 2026-02-24-100004_CreateSyncLogsTable
```

### Rollback Last Migration

```bash
php spark migrate:rollback
```

### Refresh All (Development Only)

```bash
php spark migrate:refresh --seed
```

---

## Testing Checklist

- [ ] All 30 tables created successfully
- [ ] Foreign keys validated
- [ ] Indexes created and performing
- [ ] Soft delete functioning for employees
- [ ] Encryption fields storing/retrieving correctly
- [ ] Unique constraints enforced
- [ ] Nullable fields working as expected
- [ ] ENUM constraints validated
- [ ] Cascade delete/update working
- [ ] Default values applied
- [ ] Timestamps (created_at, updated_at) auto-populated

---

## Performance Targets

- View employee profile: < 200ms  
- Search employees: < 500ms  
- Generate org chart: < 1s  
- Performance review retrieval: < 300ms  
- Audit log query: < 1s (7-year archive)  

---

## Future Enhancements

1. **Partitioning**: Partition audit_logs by year for archive queries
2. **Materialized Views**: For complex org & performance queries
3. **Full Text Search**: On certifications, skills, competencies
4. **Caching Strategy**: Redis caching for frequently accessed profiles
5. **Data Warehouse**: DW sync for analytics (separate mirror database)

---

Generated: 2026-02-24  
Version: 1.0  
Status: Production Ready
