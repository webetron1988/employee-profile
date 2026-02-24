# API Endpoints Quick Reference

## Base URL
```
http://localhost:8080/api
```

## Authentication

### Get SSO Login URL
```
POST /auth/sso-login
Body: { "return_url": "string" }
Response: { "sso_url": "string" }
```

### Refresh JWT Token
```
POST /auth/refresh
Headers: Authorization: Bearer {token}
Response: { "token": "string", "expires_in": 3600 }
```

### Verify Token
```
GET /auth/verify
Headers: Authorization: Bearer {token}
Response: { "user": { "id": 1, "email": "user@example.com", "role": "employee" } }
```

### Logout
```
POST /auth/logout
Headers: Authorization: Bearer {token}
Response: { "message": "Logged out successfully" }
```

---

## Profile Module

### Get My Profile
```
GET /profile/
Returns: Current user's complete profile
```

### Get Employee Profile
```
GET /profile/{id}
Returns: Specific employee's profile (HR/Manager access)
```

### Update My Profile
```
PUT /profile/
Body: { "first_name": "John", "last_name": "Doe", ... }
```

### Personal Details
```
GET /profile/personal-details
PUT /profile/personal-details
```

### Addresses
```
GET /profile/addresses                              // List all addresses
POST /profile/addresses                             // Add new address
PUT /profile/addresses/{id}                         // Update address
DELETE /profile/addresses/{id}                      // Delete address
```

### Emergency Contacts
```
GET /profile/emergency-contacts                     // List
POST /profile/emergency-contacts                    // Add
PUT /profile/emergency-contacts/{id}                // Update
```

### Government IDs
```
GET /profile/govt-ids                               // List (encrypted)
POST /profile/govt-ids                              // Add (encrypted)
PUT /profile/govt-ids/{id}                          // Update (encrypted)
```

### Bank Details
```
GET /profile/bank-details                           // List (encrypted)
POST /profile/bank-details                          // Add (encrypted)
PUT /profile/bank-details/{id}                      // Update (encrypted)
```

### Health Records
```
GET /profile/health
PUT /profile/health
```

### Family Dependents
```
GET /profile/family-dependents
POST /profile/family-dependents
PUT /profile/family-dependents/{id}
```

---

## Job & Organization Module

### Job Information
```
GET /job/information                                 // My current job
GET /job/information/{id}                           // Employee's job (HR)
```

### Employment History
```
GET /job/history                                    // My history
GET /job/history/{id}                               // Specific entry
POST /job/history                                   // Add history entry
```

### Organization Hierarchy
```
GET /job/org-hierarchy                              // Full org structure
GET /job/org-hierarchy/{id}                         // Department hierarchy
```

### Team Management
```
GET /job/team-members                               // My team
GET /job/reporting-structure                        // My reporting chain
```

### Promotions & Transfers
```
GET /job/promotions                                 // My promotions
POST /job/promotions                                // Create promotion (HR)

GET /job/transfers                                  // My transfers
POST /job/transfers                                 // Create transfer (HR)
```

---

## Performance Module

### Performance Reviews
```
GET /performance/reviews                            // My reviews
GET /performance/reviews/{id}                       // Specific review
POST /performance/reviews                           // Create review
PUT /performance/reviews/{id}                       // Update review
```

### Performance Goals
```
GET /performance/goals                              // My goals
GET /performance/goals/{id}                         // Specific goal
POST /performance/goals                             // Create goal
PUT /performance/goals/{id}                         // Update goal
```

### Performance Feedback
```
GET /performance/feedback                           // Feedback received
GET /performance/feedback/{id}                      // Specific feedback
POST /performance/feedback                          // Give feedback
```

### Ratings
```
GET /performance/ratings                            // My ratings history
PUT /performance/ratings/{id}                       // Update rating
```

---

## Talent Management Module

### Skills
```
GET /talent/skills                                  // Skill catalog
GET /talent/skills/{id}                             // Specific skill
POST /talent/skills                                 // Add to my skills
PUT /talent/skills/{id}                             // Update my skill
```

### Competencies
```
GET /talent/competencies                            // Competency framework
GET /talent/competencies/{id}                       // Specific competency
GET /talent/my-competencies                         // My competencies
PUT /talent/my-competencies/{id}                    // Update my competency
```

### Certifications
```
GET /talent/certifications                          // My certifications
GET /talent/certifications/{id}                     // Specific cert
POST /talent/certifications                         // Add certification
PUT /talent/certifications/{id}                     // Update certification
```

### Individual Development Plan
```
GET /talent/idp                                     // My current IDP
POST /talent/idp                                    // Create IDP
PUT /talent/idp/{id}                                // Update IDP
```

### Awards
```
GET /talent/awards                                  // My awards
GET /talent/awards/{id}                             // Specific award
```

---

## Learning & Development Module

### Courses
```
GET /learning/courses                               // Course catalog
GET /learning/courses/{id}                          // Course details
```

### Enrollments
```
GET /learning/enrollments                           // My enrollments
GET /learning/enrollments/{id}                      // Enrollment details
POST /learning/enrollments                          // Enroll in course
PUT /learning/enrollments/{id}                      // Update enrollment (progress)
```

### Training History
```
GET /learning/training-history                      // My training records
GET /learning/training-history/{id}                 // Specific training
```

### Learning Paths
```
GET /learning/learning-paths                        // Paths by competency with progress
```

---

## Compliance Module

### Documents
```
GET /compliance/documents                           // My documents
GET /compliance/documents/{id}                      // Specific document
POST /compliance/documents                          // Upload document (HR)
PUT /compliance/documents/{id}                      // Update document
```

### Document Status
```
GET /compliance/document-status                     // Status for all employees (HR)
PUT /compliance/documents/{id}/sign                 // Sign document
```

---

## Admin Module

### Employee Management
```
GET /admin/employees                                // List employees (paginated)
POST /admin/employees                               // Create employee
PUT /admin/employees/{id}                           // Update employee
DELETE /admin/employees/{id}                        // Delete employee (soft)
```

### User Management
```
GET /admin/users                                    // List users
POST /admin/users                                   // Create user
PUT /admin/users/{id}                               // Update user
```

### HRMS Sync
```
POST /admin/sync/employees                          // Trigger employee sync
GET /admin/sync/status                              // Latest sync status
GET /admin/sync/logs                                // Sync history
```

### Audit Logs
```
GET /admin/audit-logs                               // List audit entries (paginated)
GET /admin/audit-logs/{id}                          // Specific audit entry
```

### Configuration
```
GET /admin/configuration                            // Get all config
PUT /admin/configuration/{key}                      // Update specific config
```

---

## Analytics Module

### Organizational Analytics
```
GET /analytics/org-structure                        // Org analytics
GET /analytics/department-stats                     // Department stats
GET /analytics/team-stats                           // Team stats
```

### Performance Analytics
```
GET /analytics/performance-summary                  // Performance metrics
GET /analytics/review-statistics                    // Review stats
```

### Talent Analytics
```
GET /analytics/skill-inventory                      // Skills report
GET /analytics/competency-matrix                    // Competency matrix
```

### Learning Analytics
```
GET /analytics/training-stats                       // Training metrics
GET /analytics/course-effectiveness                 // Course performance
```

### HR Dashboard
```
GET /analytics/hr-dashboard                         // Main HR dashboard
GET /analytics/employee-engagement                  // Engagement metrics
```

---

## Search & Utilities

### Search
```
GET /search/employees              ?q=search        // Search employees
GET /search/skills                 ?q=search        // Search skills
GET /search/courses                ?q=search        // Search courses
GET /search/global                 ?q=search        // Global search
```

### Upload
```
POST /upload/profile-picture                        // Upload profile pic
POST /upload/certificate                            // Upload certificate
POST /upload/document                               // Upload document
POST /upload/bulk-employees                         // Bulk employee upload
```

### Export
```
GET /export/employee-profile/{id}                   // Export as PDF
GET /export/org-chart                               // Export org chart
GET /export/performance-report                      // Export performance
GET /export/skill-audit                             // Export skills
```

### Reports
```
GET /reports/employee-summary                       // Employee report
GET /reports/org-structure                          // Org report
GET /reports/performance                            // Performance report
GET /reports/training                               // Training report
GET /reports/compliance                             // Compliance report
GET /reports/headcount                              // Headcount report
```

### Dashboard
```
GET /dashboard/my-dashboard                         // My dashboard
GET /dashboard/manager-dashboard                    // Manager view
GET /dashboard/hr-dashboard                         // HR view
GET /dashboard/admin-dashboard                      // Admin view
```

---

## Documentation

### API Docs
```
GET /docs/                                          // API documentation index
GET /docs/api                                       // API documentation
GET /docs/endpoints                                 // Endpoints listing
```

---

## Health Check

```
GET /health                                         // System health (no auth required)
Response: { 
  "status": "ok", 
  "timestamp": "2024-01-15T10:30:00Z",
  "environment": "development",
  "version": "1.0.0"
}
```

---

## Common Query Parameters

### Pagination
```
?page=1                                             // Page number
?per_page=20                                        // Items per page
```

### Filtering
```
?search=john                                        // Text search
?status=active                                      // Status filter
?department=sales                                   // Department filter
?type=manager                                       // Type filter
```

### Sorting
```
?sort=name                                          // Sort by field
?order=asc                                          // asc or desc
```

---

## HTTP Status Codes

- `200 OK` - Successful GET/PUT
- `201 Created` - Resource successfully created
- `400 Bad Request` - Invalid request format
- `401 Unauthorized` - Missing or invalid token
- `403 Forbidden` - No permission to access
- `404 Not Found` - Resource doesn't exist
- `422 Unprocessable Entity` - Validation error
- `500 Internal Server Error` - Server error

---

## Authentication Headers

All protected endpoints require:
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

---

## Response Format

### Success (2xx)
```json
{
  "data": { /* entity or list */ },
  "message": "Optional success message"
}
```

### Error (4xx/5xx)
```json
{
  "messages": {
    "field_name": ["Error message"],
    "other_field": ["Error 1", "Error 2"]
  },
  "status": 422
}
```

---

## Example Request

```bash
curl -X GET "http://localhost:8080/api/profile/" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..." \
  -H "Content-Type: application/json"
```

---

## Endpoint Statistics

- **Total Endpoints**: 120+
- **GET Endpoints**: 85+
- **POST Endpoints**: 20+
- **PUT Endpoints**: 15+
- **DELETE Endpoints**: 5+
- **Public Endpoints**: 5 (health, sso-login, docs)
- **Protected Endpoints**: 115 (require JWT token)
- **Admin Endpoints**: 25 (require admin role)

---

**Version**: 1.0  
**Last Updated**: 2024  
**API Status**: Production Ready (BETA)
