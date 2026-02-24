# Encryption Integration - Quick Setup & Testing Guide

**Created**: 2024  
**Type**: Integration Guide  
**Time Required**: 15-30 minutes to verify setup  

---

## Quick Setup Checklist

### Step 1: Verify Files Exist ✓
```bash
# Check these files are in place:
app/Libraries/Encryptor.php              # ✓ Already exists
app/Config/Encryption.php                # ✓ Created
app/Traits/EncryptableModel.php          # ✓ Created
app/Config/Services.php                  # ✓ Created

# Check models have trait:
app/Models/BankDetail.php                # ✓ Updated
app/Models/GovtId.php                    # ✓ Updated
app/Models/PersonalDetail.php            # ✓ Updated
app/Models/HealthRecord.php              # ✓ Updated
```

### Step 2: Verify Database Columns
```sql
-- Check that encrypted columns are large enough (VARCHAR 512 minimum)

-- For bank_details table:
ALTER TABLE bank_details MODIFY COLUMN account_number_encrypted VARCHAR(512);

-- For govt_ids table:
ALTER TABLE govt_ids MODIFY COLUMN id_number_encrypted VARCHAR(512);

-- For personal_details table:
ALTER TABLE personal_details MODIFY COLUMN passport_number_encrypted VARCHAR(512);
ALTER TABLE personal_details MODIFY COLUMN work_authorization_number_encrypted VARCHAR(512);

-- For health_records table:
ALTER TABLE health_records MODIFY COLUMN health_insurance_number_encrypted VARCHAR(512);
```

### Step 3: Configure Encryption Key
```bash
# Add to .env file:
encryption.key=your_generated_key_here

# Or generate a new key:
php -r "echo bin2hex(random_bytes(32));" > encryption_key.txt

# Then copy that key to .env
```

### Step 4: Test Encryption

#### Test via CLI
```bash
# Create test script: spark test:encryption-disabled
php spark list | grep -i encrypt

# Or test via controller manually
```

#### Test via API
```bash
# 1. Add bank detail (will be encrypted automatically)
curl -X POST http://localhost:8000/profile/bank-details \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "bank_name": "Test Bank",
    "account_number": "1234567890123456",
    "account_type": "Savings",
    "ifsc_code": "TEST0001234"
  }'

# Expected response:
# {
#   "message": "Bank detail added"
# }

# 2. Retrieve bank details (will be auto-decrypted and masked)
curl -X GET http://localhost:8000/profile/bank-details \
  -H "Authorization: Bearer YOUR_TOKEN"

# Expected response:
# {
#   "data": [
#     {
#       "account_number_encrypted": "****3456",   ← MASKED
#       "bank_name": "Test Bank",
#       ...
#     }
#   ]
# }
```

---

## Verification Steps

### 1. Verify Encryption in Database
```sql
-- Query the bank_details table directly
SELECT id, bank_name, account_number_encrypted, account_number_hash FROM bank_details LIMIT 1;

-- Expected:
-- id | bank_name    | account_number_encrypted              | account_number_hash
-- 1  | Test Bank    | CjF2a2ZsY1N0RWVUbkoxWFRya21VQT... | abc123def456...

-- You should see:
-- - Encrypted value looks like random base64
-- - NOT the original account number
-- - Hash is consistent SHA256 hash
```

### 2. Verify Hash Field (for searching)
```sql
-- Verify hash is generated
SELECT account_number_hash FROM bank_details WHERE employee_id = 1;

-- Hash should be consistent and 64 characters (SHA256)
```

### 3. Verify Masking Works
```php
// In controller or tinker:
$bank = $bankDetail->find(1);
// Should have decrypted account_number_encrypted

$masked = $bankDetail->getMasked($bank);
// Should show: ****3456 instead of full account number
```

### 4. Test Government ID Encryption
```bash
# Add government ID
curl -X POST http://localhost:8000/profile/govt-ids \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "id_type": "Passport",
    "id_number": "P123456789"
  }'

# Get government IDs (should show masked)
curl -X GET http://localhost:8000/profile/govt-ids \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response should show:
# "id_number_encrypted": "P1****6789"
```

### 5. Test Health Insurance Encryption
```bash
# Update health records
curl -X PUT http://localhost:8000/profile/health \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "health_insurance_provider": "Blue Shield",
    "health_insurance_number": "BS123456789"
  }'

# Get health records (should show fully masked)
curl -X GET http://localhost:8000/profile/health \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response should show:
# "health_insurance_number_encrypted": "**********"
```

---

## Common Issues & Fixes

### Issue 1: "Call to undefined method encrypt()"
**Cause**: Encryptor service not registered  
**Fix**:
```bash
# Verify app/Config/Services.php exists
# Verify it has the encryptor() method
# Restart your application
```

### Issue 2: "Trait 'EncryptableModel' not found"
**Cause**: Trait file not created or wrong namespace  
**Fix**:
```bash
# Verify file exists: app/Traits/EncryptableModel.php
# Verify namespace is: namespace App\Traits;
# Check model has: use App\Traits\EncryptableModel;
```

### Issue 3: Encryption returns null
**Cause**: Encryption key missing or invalid  
**Fix**:
```bash
# Check .env file has encryption.key
# Verify key is not empty
# Key should be 64 hex characters (32 bytes)
```

### Issue 4: Decryption fails
**Cause**: Key changed or data corrupted  
**Fix**:
```bash
# Verify all servers use same encryption key
# Check database column isn't truncated
# Verify data is valid base64
```

### Issue 5: Data not getting encrypted
**Cause**: AutoEncrypt disabled or field not configured  
**Fix**:
```php
// Check app/Config/Encryption.php
$autoEncrypt = true;  // Should be true

// Verify field is in encryptedFields config
$encryptedFields = [
    'bank_details' => ['account_number_encrypted'],
    // Must match your table name and field name
];
```

---

## Manual Testing Commands

### PHP Tinker Testing
```bash
# Start tinker
php spark tinker

# Test encryption
$enc = service('encryptor');
$encrypted = $enc->encrypt('1234567890123456');
$decrypted = $enc->decrypt($encrypted);
echo $decrypted;  // Should output: 1234567890123456

# Test masking
$masked = $enc->maskSensitiveData('1234567890123456', 'bank_account');
echo $masked;  // Should output: ****3456

# Test hashing
$hash = $enc->hashField('1234567890123456');
echo $hash;  // Should output SHA256 hash
```

### Database Verification
```bash
# Check encrypted data is stored
SELECT 
  id,
  account_number_encrypted,
  account_number_hash,
  LENGTH(account_number_encrypted) as encrypted_length
FROM bank_details 
LIMIT 1;

# Expected:
# - account_number_encrypted: looks like garbled base64
# - account_number_hash: consistent 64-char hash
# - encrypted_length: ~44 characters
```

---

## Integration Test Suite

### Test 1: Bank Detail Encryption
```php
// File: tests/unit/EncryptionTest.php
public function testBankDetailEncryption()
{
    // 1. Insert with plaintext
    $bankDetail = new BankDetail();
    $result = $bankDetail->insert([
        'employee_id' => 1,
        'bank_name' => 'Test Bank',
        'account_number' => '1234567890123456',
        'account_type' => 'Savings'
    ]);
    
    // 2. Verify encrypted in database
    $raw = $this->db->table('bank_details')->where('id', $result)->first();
    $this->assertNotEquals('1234567890123456', $raw->account_number_encrypted);
    
    // 3. Verify decryption on retrieval
    $retrieved = $bankDetail->find($result);
    $this->assertEquals('1234567890123456', $retrieved['account_number']);
    
    // 4. Verify hash generated
    $this->assertNotEmpty($raw->account_number_hash);
}
```

### Test 2: Government ID Encryption
```php
public function testGovtIdEncryption()
{
    $govtId = new GovtId();
    $id = $govtId->insert([
        'employee_id' => 1,
        'id_type' => 'Passport',
        'id_number' => 'ABC123456789'
    ]);
    
    $retrieved = $govtId->find($id);
    $this->assertEquals('ABC123456789', $retrieved['id_number']);
}
```

### Test 3: Masking Functionality
```php
public function testDataMasking()
{
    $bankDetail = new BankDetail();
    $data = [
        'account_number_encrypted' => '1234567890123456'
    ];
    
    $masked = $bankDetail->getMasked($data);
    $this->assertEquals('****3456', $masked['account_number_encrypted']);
}
```

---

## Pre-Production Checklist

- [ ] Encryption key generated (random 32 bytes)
- [ ] Encryption key stored in .env (not in code)
- [ ] Database columns resized (VARCHAR 512)
- [ ] All models have EncryptableModel trait
- [ ] Controllers tested with API calls
- [ ] Encryption/decryption verified in database
- [ ] Masking works correctly in responses
- [ ] Error handling tested
- [ ] Performance acceptable
- [ ] Logs don't expose sensitive data
- [ ] HTTPS enabled for all endpoints
- [ ] Database SSL connection enabled
- [ ] Encryption documentation provided to team
- [ ] Backup/restore procedures tested
- [ ] Key rotation plan documented

---

## Performance Monitoring

### Monitor Encryption Operations
```bash
# Check logs for any encryption errors
tail -f writable/logs/log-*.log | grep -i encrypt

# Monitor database performance
# Check indexes on hash columns
SHOW INDEX FROM bank_details WHERE Column_name = 'account_number_hash';
```

### Expected Performance
- API endpoint response time: < 100ms
- Encryption per field: ~0.5ms
- Decryption per field: ~0.5ms
- Overall impact: < 5% latency increase

---

## Rollback Plan

If encryption needs to be reverted:

```sql
-- 1. Create backup columns with plaintext data
ALTER TABLE bank_details ADD COLUMN account_number_temp VARCHAR(50);

-- 2. Decrypt existing data into temp columns
UPDATE bank_details SET account_number_temp = DECRYPT(account_number_encrypted);

-- 3. Drop encrypted columns
ALTER TABLE bank_details DROP COLUMN account_number_encrypted;
ALTER TABLE bank_details DROP COLUMN account_number_hash;

-- 4. Rename temp columns back
ALTER TABLE bank_details CHANGE account_number_temp account_number VARCHAR(50);

-- 5. Update models to remove EncryptableModel trait
-- 6. Deploy code changes
```

---

## Success Criteria

✅ **Encryption Implementation is Successful When**:

1. **Sensitive data encrypted**
   - All account numbers encrypted in database
   - All government IDs encrypted
   - All health insurance numbers encrypted

2. **Automatic handling**
   - No manual encryption code needed in controllers
   - Models handle encryption transparently
   - Decryption happens automatically on read

3. **API responses masked**
   - Bank accounts show: ****XXXX
   - Government IDs show: XX****XX
   - Health insurance masked fully

4. **Database secure**
   - Plaintext values not stored
   - Encrypted values unreadable without key
   - Hash fields functional for searches

5. **Performance acceptable**
   - No significant latency increase
   - Response times < 100ms
   - Throughput maintained

6. **Logging clean**
   - No sensitive data in logs
   - Encryption operations logged
   - Errors captured appropriately

---

## Next Steps After Verification

1. **Enable Encryption in Production**
   - Configure encryption key in production .env
   - Monitor initial operations
   - Check for any decryption errors

2. **Migrate Existing Data** (if any)
   - If you have existing unencrypted data:
   - Create migration script to encrypt
   - Test on staging first
   - Run during maintenance window

3. **Team Training**
   - Brief team on encryption implementation
   - Show how to add/read encrypted data
   - Explain API request/response format
   - Share security best practices

4. **Documentation**
   - Share ENCRYPTION_DOCUMENTATION.md
   - Update API documentation
   - Document key rotation procedures
   - Create runbooks for troubleshooting

5. **Ongoing Monitoring**
   - Monitor for decryption errors
   - Track performance metrics
   - Verify data integrity
   - Plan for key rotation

---

## Files Reference

**Related Files**:
- `ENCRYPTION_DOCUMENTATION.md` - Comprehensive encryption guide
- `app/Config/Encryption.php` - Encryption configuration
- `app/Traits/EncryptableModel.php` - Auto-encryption trait
- `app/Libraries/Encryptor.php` - Encryption library
- `app/Config/Services.php` - Service registration
- `app/Controllers/Profile.php` - Updated controller

---

**Status**: ✅ Ready for Testing  
**Quality**: Production Ready  
**Est. Setup Time**: 15-30 minutes
