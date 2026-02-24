# HRMS Batch Synchronization System Documentation

**Date**: 2024  
**Version**: 1.0  
**Status**: Production Ready

---

## Overview

The HRMS Batch Synchronization System provides automated, command-line based tools to synchronize employee data from the HRMS system to the Employee Profile application. The system handles three core synchronization tasks:

1. **Employee Master Sync** - Core employee information
2. **Organization Hierarchy Sync** - Department structure and relationships
3. **Job Information Sync** - Position and role details

---

## Batch Commands

### 1. HrmsSync (Employee Master Synchronization)

**Purpose**: Synchronize employee master data from HRMS to application database

**Command**:
```bash
php spark hrms:sync [OPTIONS]
```

**Options**:
```
--type=full|incremental     Type of sync (default: full)
--dry-run                     Preview changes without saving
--limit=N                     Limit to N employees for testing
--retry=N                     Number of retry attempts (default: 3)
```

**Examples**:

Full synchronization of all employees:
```bash
php spark hrms:sync --type=full
```

Incremental sync (only changed records):
```bash
php spark hrms:sync --type=incremental
```

Test sync with first 10 employees:
```bash
php spark hrms:sync --dry-run --limit=10
```

**Features**:
- ✅ Batch processing with progress bar
- ✅ Full and incremental sync modes
- ✅ Duplicate detection (by HRMS ID)
- ✅ Automatic create/update logic
- ✅ Comprehensive error handling
- ✅ Detailed logging and audit trail
- ✅ Dry-run mode for testing
- ✅ Configurable retry logic

**Output**:
```
═══════════════════════════════════════════════════════════
HRMS Employee Synchronization Started
═══════════════════════════════════════════════════════════

Configuration:
  Sync Type: full
  Dry Run: NO
  Retry Attempts: 3

Fetching employees from HRMS...
  Fetching batch 1...
  Fetching batch 2...
  Total employees fetched: 245

Processing employees...
  [EMP001] John Doe - CREATED
  [EMP002] Jane Smith - UPDATED
  ...

Results:
  Total Processed: 245
  Created: 12
  Updated: 85
  Unchanged: 148
  Failed: 0
  Duration: 45 seconds

═══════════════════════════════════════════════════════════
HRMS Synchronization Completed Successfully
═══════════════════════════════════════════════════════════
```

---

### 2. OrgHierarchySync (Organization Structure Synchronization)

**Purpose**: Synchronize organizational structure, departments, and reporting relationships

**Command**:
```bash
php spark hrms:sync-org [OPTIONS]
```

**Options**:
```
--dry-run                     Preview changes without saving
--force                       Override recent sync check
```

**Examples**:

Sync organization structure:
```bash
php spark hrms:sync-org
```

Force sync even if recent sync exists:
```bash
php spark hrms:sync-org --force
```

Test mode:
```bash
php spark hrms:sync-org --dry-run
```

**Features**:
- ✅ Hierarchical department structure
- ✅ Parent-child relationships
- ✅ Reporting line synchronization
- ✅ 6-hour cooldown to prevent excessive syncs
- ✅ Two-pass processing (departments then relationships)

**Output**:
```
═══════════════════════════════════════════════════════════
Organization Hierarchy Synchronization Started
═══════════════════════════════════════════════════════════

Fetching organizational hierarchy from HRMS...
Received 35 departments

Processing organizational units...
  ✓ Engineering
  ✓ Sales
  ✓ Human Resources
  ...

Processing reporting relationships...
  ✓ Relationship synced
  ✓ Relationship synced
  ...

Results:
  Departments Processed: 35
  Relationships Created: 245
  Errors: 0

Organization Hierarchy Sync Completed
```

---

### 3. JobInfoSync (Job Information Synchronization)

**Purpose**: Synchronize job titles, designations, departments, and manager assignments

**Command**:
```bash
php spark hrms:sync-jobs [OPTIONS]
```

**Options**:
```
--dry-run                     Preview changes without saving
```

**Examples**:

Sync job information:
```bash
php spark hrms:sync-jobs
```

Test mode:
```bash
php spark hrms:sync-jobs --dry-run
```

**Features**:
- ✅ Job designation synchronization
- ✅ Department assignment
- ✅ Manager relationship linking
- ✅ Salary band and grade tracking
- ✅ Employment type and status
- ✅ Cost center assignment
- ✅ Business unit tracking

**Output**:
```
═══════════════════════════════════════════════════════════
Job Information Synchronization Started
═══════════════════════════════════════════════════════════

Fetching job information from HRMS...
Received job data for 245 employees

Processing job information...
  [EMP001] Senior Engineer - OK
  [EMP002] Engineering Manager - OK
  ...

Results:
  Total Processed: 245
  Created: 10
  Updated: 125
  Failed: 0

Job Information Sync Completed
```

---

### 4. SyncAll (Complete Synchronization)

**Purpose**: Execute all synchronization tasks in sequence

**Command**:
```bash
php spark hrms:sync-all [OPTIONS]
```

**Options**:
```
--dry-run                     Preview all changes without saving
--skip-org                    Skip organization hierarchy sync
--skip-jobs                   Skip job information sync
```

**Examples**:

Complete sync:
```bash
php spark hrms:sync-all
```

Test complete sync:
```bash
php spark hrms:sync-all --dry-run
```

Skip certain steps:
```bash
php spark hrms:sync-all --skip-org --skip-jobs
```

**Features**:
- ✅ Sequential execution of all sync jobs
- ✅ Unified progress reporting
- ✅ Comprehensive summary display
- ✅ Total execution time tracking
- ✅ Step-by-step status reporting
- ✅ Selective execution via skip options

**Output**:
```
╔═══════════════════════════════════════════════════════════╗
║       HRMS COMPLETE SYNCHRONIZATION STARTED              ║
╚═══════════════════════════════════════════════════════════╝

───────────────────────────────────────────────────────────
Step 1 of 3: Employee Master Sync
───────────────────────────────────────────────────────────
[... employee sync output ...]
✓

───────────────────────────────────────────────────────────
Step 2 of 3: Organization Hierarchy Sync
───────────────────────────────────────────────────────────
[... org sync output ...]
✓

───────────────────────────────────────────────────────────
Step 3 of 3: Job Information Sync
───────────────────────────────────────────────────────────
[... job sync output ...]
✓

╔═══════════════════════════════════════════════════════════╗
║               SYNCHRONIZATION SUMMARY                     ║
╚═══════════════════════════════════════════════════════════╝

Step 1: Employee Master: ✓ SUCCESS
Step 2: Organization Hierarchy: ✓ SUCCESS
Step 3: Job Information: ✓ SUCCESS

Total Execution Time: 87.34 seconds

═══════════════════════════════════════════════════════════
Synchronization Complete
═══════════════════════════════════════════════════════════
```

---

## Scheduled Synchronization

### Using System CRON

Add to system crontab to run nightly syncs:

**Full Sync Daily at 2 AM**:
```bash
0 2 * * * cd /var/www/employee-profile/backend && php spark hrms:sync --type=full >> logs/sync.log 2>&1
```

**Incremental Sync Every 6 Hours**:
```bash
0 */6 * * * cd /var/www/employee-profile/backend && php spark hrms:sync --type=incremental >> logs/sync.log 2>&1
```

**Complete Sync Weekly on Sunday at 3 AM**:
```bash
0 3 * * 0 cd /var/www/employee-profile/backend && php spark hrms:sync-all >> logs/sync.log 2>&1
```

### Using Task Scheduler (Windows)

Create scheduled task:
```
Program: C:\php\php.exe
Arguments: C:\wamp64new\www\employee-profile\backend\spark hrms:sync --type=full
Working Directory: C:\wamp64new\www\employee-profile\backend
Schedule: Daily at 2:00 AM
```

### Using Supervisor (Linux)

Create `/etc/supervisor/conf.d/hrms-sync.conf`:
```ini
[program:hrms-sync]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/employee-profile/backend/spark hrms:sync --type=full
autostart=false
autorestart=false
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/hrms-sync.log
```

---

## Data Transformation

### Employee Master Transformation

HRMS → Application Mapping:
```
hrms.id                      → hrms_employee_id
hrms.first_name              → first_name
hrms.last_name               → last_name
hrms.email                   → email
hrms.phone                   → phone
hrms.date_of_birth           → date_of_birth
hrms.gender                  → gender
hrms.nationality             → nationality
hrms.employment_status       → employment_status
hrms.date_of_joining         → date_of_joining
hrms.is_active               → is_active
```

### Organization Hierarchy Transformation

```
hrms.dept_id                 → external_id
hrms.department_name         → name
hrms.department_code         → department_code
hrms.parent_dept_id          → parent_id
hrms.level                   → level
hrms.is_active               → is_active
```

### Job Information Transformation

```
hrms.designation             → designation
hrms.department              → department
hrms.employment_type         → employment_type
hrms.employment_status       → employment_status
hrms.grade                   → grade
hrms.manager_hrms_id         → reporting_manager_id (resolved)
hrms.functional_manager_id   → functional_manager_id (resolved)
```

---

## Error Handling

### Common Errors and Solutions

**Employee Not Found**
```
ERROR: Employee not found for HRMS ID EMP123
SOLUTION: Ensure employee master sync completed before job sync
```

**Manager Not Found**
```
ERROR: Reporting manager not found
SOLUTION: Manager must exist in application before assignment
```

**Database Constraint Violation**
```
ERROR: Duplicate HRMS Employee ID
SOLUTION: Check for duplicate data in HRMS or application
```

**Timeout Error**
```
ERROR: HRMS API connection timeout
SOLUTION: Check HRMS API availability, increase timeout in HrmsClient
```

### Retry Logic

All sync commands implement automatic retry logic:
- Default: 3 retry attempts
- Backoff: 1 second between retries
- Configurable via `--retry` option

### Error Logging

All errors are logged to:
1. **Console Output** - Real-time display during sync
2. **Sync Log Table** - Persistent database record
3. **Application Logs** - `/backend/writable/logs/`

---

## Sync Log Table

The `sync_logs` table tracks all synchronization activity:

```sql
SELECT * FROM sync_logs ORDER BY sync_date DESC LIMIT 10;
```

**Fields**:
- `id` - Unique identifier
- `sync_type` - Type of sync (employee_master, org_hierarchy, job_information)
- `sync_date` - When sync started
- `completed_at` - When sync finished
- `status` - Completed, Completed with Errors, Failed
- `records_processed` - Total records processed
- `records_created` - Records created
- `records_updated` - Records updated
- `records_failed` - Records that failed
- `error_details` - JSON array of errors
- `duration_seconds` - Total execution time

---

## Performance Optimization

### Batch Processing

All syncs process data in batches:
- Employee sync: 100 employees per batch
- With progress bar for monitoring
- Configurable via limit parameter

### Database Optimization

- Indexes on `hrms_employee_id` for fast lookups
- Batch inserts/updates for performance
- No unnecessary queries per record

### Performance Metrics

Typical execution times:
- 250 employees: ~45 seconds
- 35 departments: ~15 seconds
- 245 job records: ~30 seconds
- Complete sync: ~90 seconds

---

## Monitoring and Alerts

### Check Recent Sync Status

```bash
# View last 10 syncs
SELECT * FROM sync_logs ORDER BY sync_date DESC LIMIT 10;

# Check for failures
SELECT * FROM sync_logs WHERE status LIKE '%Error%';

# View failed records
SELECT error_details FROM sync_logs WHERE sync_type = 'employee_master';
```

### API Endpoint for Status

```
GET /admin/sync/status                  # Latest sync status
GET /admin/sync/logs?limit=50           # Sync history
```

### Alert Conditions

- Sync fails completely
- More than 5% records fail
- Sync takes longer than expected
- No sync in last 24 hours

---

## Best Practices

1. **Start with Full Sync**
   - Always run full sync before incremental
   - Establishes baseline data integrity

2. **Test with Dry-Run**
   - Always test with `--dry-run` first
   - Verify changes before committing

3. **Monitor First Sync**
   - Run first sync manually
   - Watch for data transformation issues
   - Verify data accuracy in application

4. **Schedule Incrementals**
   - Run daily incremental syncs
   - Captures changes within 24 hours
   - Reduces load on HRMS API

5. **Weekly Full Validation**
   - Run full sync weekly
   - Catches missed incremental changes
   - Ensures data consistency

6. **Monitor Logs**
   - Check application logs regularly
   - Set up alerts for sync failures
   - Track error trends

7. **Backup Before Sync**
   - Database backup before major sync
   - Rollback capability if issues occur

---

## Troubleshooting

### Sync Hangs or Times Out

**Steps**:
1. Check HRMS API availability
2. Verify network connectivity
3. Increase timeout in HrmsClient
4. Check database locks

### Data Mismatch After Sync

**Steps**:
1. Run full sync with fresh data
2. Compare sync logs for errors
3. Check transformation logic
4. Verify HRMS data quality

### Large Number of Failures

**Steps**:
1. Run with `--dry-run` to preview
2. Check error details in sync log
3. Verify HRMS data completeness
4. Contact HRMS support for data issues

### Performance Issues

**Steps**:
1. Check database indexes
2. Run with reduced `--limit`
3. Schedule during off-peak hours
4. Monitor server resources

---

## Migration from Legacy System

1. **Week 1**: Run full sync in dry-run mode
2. **Week 2**: Run full sync to test database
3. **Week 3**: Validate data accuracy
4. **Week 4**: Go live with incremental syncs

---

## API Integration

All sync operations are visible via API:

```
GET /admin/sync/status              # Latest sync status
GET /admin/sync/logs?limit=50       # Sync history (paginated)
POST /admin/sync/employees          # Trigger manual sync
```

---

## Files Location

- **Commands**: `/backend/app/Commands/`
  - `HrmsSync.php`
  - `OrgHierarchySync.php`
  - `JobInfoSync.php`
  - `SyncAll.php`

- **Models**: `/backend/app/Models/`
  - `SyncLog.php`
  - `Employee.php`
  - `OrgHierarchy.php`
  - `JobInformation.php`

- **Services**: `/backend/app/Libraries/`
  - `HrmsClient.php`

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2024 | Initial release with 4 batch commands |

---

## Support

For issues or questions:
1. Check logs in `/backend/writable/logs/`
2. Review sync_logs table for error details
3. Contact HR system administrator
4. Review HRMS API documentation

---

**Status**: ✅ Production Ready  
**Last Updated**: 2024
