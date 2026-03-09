<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\PersonalDetail;
use App\Models\Address;
use App\Models\EmergencyContact;
use App\Models\GovtId;
use App\Models\BankDetail;
use App\Models\HealthRecord;
use App\Models\FamilyDependent;
use App\Models\Language;
use App\Models\Hobby;
use App\Models\VolunteerActivity;
use App\Models\Patent;
use App\Models\WorkingCondition;
use App\Models\PhysicalLocation;
use App\Models\MobilityPreference;
use App\Models\GdprConsent;
use App\Models\DataVersion;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Profile extends Controller
{
    use ResponseTrait;

    protected $user;
    protected $personalDetail;
    protected $address;
    protected $emergencyContact;
    protected $govtId;
    protected $bankDetail;
    protected $healthRecord;
    protected $familyDependent;
    protected $language;
    protected $hobby;
    protected $volunteerActivity;
    protected $patent;
    protected $workingCondition;
    protected $physicalLocation;
    protected $mobilityPreference;
    protected $gdprConsent;
    protected $dataVersion;

    public function __construct()
    {
        $this->user = new User();
        $this->personalDetail = new PersonalDetail();
        $this->address = new Address();
        $this->emergencyContact = new EmergencyContact();
        $this->govtId = new GovtId();
        $this->bankDetail = new BankDetail();
        $this->healthRecord = new HealthRecord();
        $this->familyDependent = new FamilyDependent();
        $this->language = new Language();
        $this->hobby = new Hobby();
        $this->volunteerActivity = new VolunteerActivity();
        $this->patent = new Patent();
        $this->workingCondition = new WorkingCondition();
        $this->physicalLocation = new PhysicalLocation();
        $this->mobilityPreference = new MobilityPreference();
        $this->gdprConsent = new GdprConsent();
        $this->dataVersion = new DataVersion();
    }

    /**
     * Get current user's profile
     * GET /profile/
     */
    public function myProfile()
    {
        try {
            $userId = auth()->user()->id;
            $userData = $this->user->find($userId);

            if (!$userData) {
                return $this->failNotFound('User profile not found');
            }

            // Remove sensitive fields
            unset($userData['password_hash'], $userData['refresh_token_hash']);

            // Add related data
            $userData['personal_details'] = $this->personalDetail->where('employee_id', $userId)->first();
            $userData['addresses'] = $this->address->where('employee_id', $userId)->findAll();
            $userData['emergency_contacts'] = $this->emergencyContact->where('employee_id', $userId)->where('is_primary', true)->first();

            return $this->respond(['data' => $userData], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching profile: ' . $e->getMessage());
        }
    }

    /**
     * Get specific employee profile (admin/manager only)
     * GET /profile/{id}
     */
    public function getProfile($id)
    {
        try {
            $userData = $this->user->find($id);

            if (!$userData) {
                return $this->failNotFound('Employee not found');
            }

            unset($userData['password_hash'], $userData['refresh_token_hash']);

            $userData['personal_details'] = $this->personalDetail->where('employee_id', $id)->first();
            $userData['addresses'] = $this->address->where('employee_id', $id)->findAll();

            return $this->respond(['data' => $userData], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching employee profile');
        }
    }

    /**
     * Update current user's profile
     * PUT /profile/
     */
    public function updateProfile()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);

            // Don't allow changing sensitive fields via profile update
            unset($data['password_hash'], $data['role'], $data['permissions'], $data['refresh_token_hash']);

            if ($this->user->update($userId, $data)) {
                return $this->respond(['message' => 'Profile updated successfully'], 200);
            }

            return $this->fail($this->user->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating profile');
        }
    }

    /**
     * Get personal details
     * GET /profile/personal-details
     */
    public function getPersonalDetails()
    {
        try {
            $userId = auth()->user()->id;
            $details = $this->personalDetail->where('employee_id', $userId)->first();

            if (!$details) {
                return $this->failNotFound('Personal details not found');
            }

            // Mask sensitive fields in response
            $details = $this->personalDetail->getMasked($details);

            return $this->respond(['data' => $details], 200);
        } catch (\Throwable $e) {
            log_message('error', 'getPersonalDetails error: ' . $e->getMessage());
            return $this->failServerError('Error fetching personal details');
        }
    }

    /**
     * Update personal details
     * PUT /profile/personal-details
     */
    public function updatePersonalDetails()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            // Model will automatically encrypt passport_number and work_authorization_number
            // Just accept these as plaintext fields: passport_number, work_authorization_number
            // They will be encrypted and stored as passport_number_encrypted, work_authorization_number_encrypted

            $personalDetail = $this->personalDetail->where('employee_id', $userId)->first();

            if ($personalDetail) {
                // Snapshot before update
                $this->dataVersion->createSnapshot('personal_details', $personalDetail['id'], $userId, $personalDetail, $data, $userId);
                if ($this->personalDetail->update($personalDetail['id'], $data)) {
                    $updated = $this->personalDetail->where('employee_id', $userId)->first();
                    return $this->respond(['status' => 'success', 'message' => 'Personal details updated', 'data' => $updated], 200);
                }
            } else {
                $id = $this->personalDetail->insert($data);
                if ($id) {
                    $created = $this->personalDetail->find($id);
                    return $this->respond(['status' => 'success', 'message' => 'Personal details created', 'data' => $created], 201);
                }
            }

            return $this->fail($this->personalDetail->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating personal details');
        }
    }

    /**
     * Get addresses
     * GET /profile/addresses
     */
    public function getAddresses()
    {
        try {
            $userId = auth()->user()->id;
            $addresses = $this->address->where('employee_id', $userId)->findAll();

            return $this->respond(['data' => $addresses], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching addresses');
        }
    }

    /**
     * Add new address
     * POST /profile/addresses
     */
    public function addAddress()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            if ($this->address->insert($data)) {
                return $this->respond(['message' => 'Address added successfully'], 201);
            }

            return $this->fail($this->address->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding address');
        }
    }

    /**
     * Update address
     * PUT /profile/addresses/{id}
     */
    public function updateAddress($id)
    {
        try {
            $userId = auth()->user()->id;
            $address = $this->address->find($id);

            if (!$address || $address['employee_id'] != $userId) {
                return $this->failForbidden('Address not found or unauthorized');
            }

            $data = $this->request->getJSON(true);
            $this->dataVersion->createSnapshot('addresses', (int)$id, (int)$address['employee_id'], $address, $data, $userId);
            if ($this->address->update($id, $data)) {
                return $this->respond(['message' => 'Address updated'], 200);
            }

            return $this->fail($this->address->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating address');
        }
    }

    /**
     * Delete address
     * DELETE /profile/addresses/{id}
     */
    public function deleteAddress($id)
    {
        try {
            $userId = auth()->user()->id;
            $address = $this->address->find($id);

            if (!$address || $address['employee_id'] != $userId) {
                return $this->failForbidden('Address not found or unauthorized');
            }

            if ($this->address->delete($id)) {
                return $this->respond(['message' => 'Address deleted'], 200);
            }

            return $this->failServerError('Error deleting address');
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting address');
        }
    }

    /**
     * Get emergency contacts
     * GET /profile/emergency-contacts
     */
    public function getEmergencyContacts()
    {
        try {
            $userId = auth()->user()->id;
            $contacts = $this->emergencyContact->where('employee_id', $userId)->findAll();

            return $this->respond(['data' => $contacts], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching emergency contacts');
        }
    }

    /**
     * Add emergency contact
     * POST /profile/emergency-contacts
     */
    public function addEmergencyContact()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            if ($this->emergencyContact->insert($data)) {
                return $this->respond(['message' => 'Emergency contact added'], 201);
            }

            return $this->fail($this->emergencyContact->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding emergency contact');
        }
    }

    /**
     * Update emergency contact
     * PUT /profile/emergency-contacts/{id}
     */
    public function updateEmergencyContact($id)
    {
        try {
            $userId = auth()->user()->id;
            $contact = $this->emergencyContact->find($id);

            if (!$contact || $contact['employee_id'] != $userId) {
                return $this->failForbidden('Contact not found or unauthorized');
            }

            $data = $this->request->getJSON(true);
            $this->dataVersion->createSnapshot('emergency_contacts', (int)$id, (int)$contact['employee_id'], $contact, $data, $userId);
            if ($this->emergencyContact->update($id, $data)) {
                return $this->respond(['message' => 'Emergency contact updated'], 200);
            }

            return $this->fail($this->emergencyContact->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating emergency contact');
        }
    }

    /**
     * Get government IDs
     * GET /profile/govt-ids
     */
    public function getGovtIds()
    {
        try {
            $userId = auth()->user()->id;
            $ids = $this->govtId->where('employee_id', $userId)->findAll();

            // Mask sensitive fields in response
            foreach ($ids as &$id) {
                $id = $this->govtId->getMasked($id);
            }

            return $this->respond(['data' => $ids], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching government IDs');
        }
    }

    /**
     * Add government ID
     * POST /profile/govt-ids
     */
    public function addGovtId()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            // Model will automatically encrypt id_number and generate id_number_hash
            // Just accept plaintext id_number field, encryption is handled by model

            if ($this->govtId->insert($data)) {
                return $this->respond(['message' => 'Government ID added'], 201);
            }

            return $this->fail($this->govtId->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding government ID');
        }
    }

    /**
     * Update government ID
     * PUT /profile/govt-ids/{id}
     */
    public function updateGovtId($id)
    {
        try {
            $userId = auth()->user()->id;
            $govtId = $this->govtId->find($id);

            if (!$govtId || $govtId['employee_id'] != $userId) {
                return $this->failForbidden('Government ID not found or unauthorized');
            }

            $data = $this->request->getJSON(true);

            // Snapshot before update
            $this->dataVersion->createSnapshot('govt_ids', (int)$id, (int)$govtId['employee_id'], $govtId, $data, $userId);
            // Model will automatically encrypt id_number if provided
            if ($this->govtId->update($id, $data)) {
                return $this->respond(['message' => 'Government ID updated'], 200);
            }

            return $this->fail($this->govtId->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating government ID');
        }
    }

    /**
     * Get bank details
     * GET /profile/bank-details
     */
    public function getBankDetails()
    {
        try {
            $userId = auth()->user()->id;
            $details = $this->bankDetail->where('employee_id', $userId)->findAll();

            // Mask sensitive fields in response
            foreach ($details as &$detail) {
                $detail = $this->bankDetail->getMasked($detail);
            }

            return $this->respond(['data' => $details], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching bank details');
        }
    }

    /**
     * Add bank detail
     * POST /profile/bank-details
     */
    public function addBankDetail()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            // Model will automatically encrypt account_number and generate account_number_hash
            // Just accept plaintext account_number field, encryption is handled by model

            if ($this->bankDetail->insert($data)) {
                return $this->respond(['message' => 'Bank detail added'], 201);
            }

            return $this->fail($this->bankDetail->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding bank detail');
        }
    }

    /**
     * Update bank detail
     * PUT /profile/bank-details/{id}
     */
    public function updateBankDetail($id)
    {
        try {
            $userId = auth()->user()->id;
            $bankDetail = $this->bankDetail->find($id);

            if (!$bankDetail || $bankDetail['employee_id'] != $userId) {
                return $this->failForbidden('Bank detail not found or unauthorized');
            }

            $data = $this->request->getJSON(true);

            // Snapshot before update
            $this->dataVersion->createSnapshot('bank_details', (int)$id, (int)$bankDetail['employee_id'], $bankDetail, $data, $userId);
            // Model will automatically encrypt account_number if provided
            if ($this->bankDetail->update($id, $data)) {
                return $this->respond(['message' => 'Bank detail updated'], 200);
            }

            return $this->fail($this->bankDetail->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating bank detail');
        }
    }

    /**
     * Get health records
     * GET /profile/health
     */
    public function getHealthRecords()
    {
        try {
            $userId = auth()->user()->id;
            $health = $this->healthRecord->where('employee_id', $userId)->first();

            if (!$health) {
                return $this->failNotFound('Health records not found');
            }

            // Mask sensitive fields in response
            $health = $this->healthRecord->getMasked($health);

            return $this->respond(['data' => $health], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching health records');
        }
    }

    /**
     * Update health records
     * PUT /profile/health
     */
    public function updateHealthRecords()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            // Model will automatically encrypt health_insurance_number
            // Just accept plaintext health_insurance_number field, encryption is handled by model

            $health = $this->healthRecord->where('employee_id', $userId)->first();

            if ($health) {
                // Snapshot before update
                $this->dataVersion->createSnapshot('health_records', $health['id'], $userId, $health, $data, $userId);
                if ($this->healthRecord->update($health['id'], $data)) {
                    $updated = $this->healthRecord->where('employee_id', $userId)->first();
                    return $this->respond(['status' => 'success', 'message' => 'Health records updated', 'data' => $updated], 200);
                }
            } else {
                $insertId = $this->healthRecord->insert($data);
                if ($insertId) {
                    $created = $this->healthRecord->find($insertId);
                    return $this->respond(['status' => 'success', 'message' => 'Health records created', 'data' => $created], 201);
                }
            }

            return $this->fail($this->healthRecord->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating health records');
        }
    }

    /**
     * Get family dependents
     * GET /profile/family-dependents
     */
    public function getFamilyDependents()
    {
        try {
            $userId = auth()->user()->id;
            $dependents = $this->familyDependent->where('employee_id', $userId)->findAll();

            return $this->respond(['data' => $dependents], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching family dependents');
        }
    }

    /**
     * Get single family dependent
     * GET /profile/family-dependents/{id}
     */
    public function getFamilyDependent($id)
    {
        try {
            $userId = auth()->user()->id;
            $dependent = $this->familyDependent->find($id);

            if (!$dependent || $dependent['employee_id'] != $userId) {
                return $this->failNotFound('Family dependent not found');
            }

            return $this->respond(['data' => $dependent], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching family dependent');
        }
    }

    /**
     * Add family dependent
     * POST /profile/family-dependents
     */
    public function addFamilyDependent()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            if ($this->familyDependent->insert($data)) {
                return $this->respond(['message' => 'Family dependent added'], 201);
            }

            return $this->fail($this->familyDependent->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding family dependent');
        }
    }

    /**
     * Update family dependent
     * PUT /profile/family-dependents/{id}
     */
    public function updateFamilyDependent($id)
    {
        try {
            $userId = auth()->user()->id;
            $dependent = $this->familyDependent->find($id);

            if (!$dependent || $dependent['employee_id'] != $userId) {
                return $this->failForbidden('Family dependent not found or unauthorized');
            }

            $data = $this->request->getJSON(true);
            if ($this->familyDependent->update($id, $data)) {
                return $this->respond(['message' => 'Family dependent updated'], 200);
            }

            return $this->fail($this->familyDependent->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating family dependent');
        }
    }

    /**
     * Delete family dependent
     * DELETE /profile/family-dependents/{id}
     */
    public function deleteFamilyDependent($id)
    {
        try {
            $userId = auth()->user()->id;
            $dependent = $this->familyDependent->find($id);

            if (!$dependent || $dependent['employee_id'] != $userId) {
                return $this->failForbidden('Family dependent not found or unauthorized');
            }

            $this->familyDependent->delete($id);
            return $this->respond(['message' => 'Family dependent deleted'], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting family dependent');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LANGUAGES
    // ═══════════════════════════════════════════════════════════════════════════

    public function getLanguages()
    {
        try {
            $userId = auth()->user()->id;
            return $this->respond(['data' => $this->language->where('employee_id', $userId)->findAll()], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching languages');
        }
    }

    public function getLanguage($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->language->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failNotFound('Language not found');
            }
            return $this->respond(['data' => $record], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching language');
        }
    }

    public function addLanguage()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            if ($this->language->insert($data)) {
                return $this->respond(['message' => 'Language added', 'data' => ['id' => $this->language->getInsertID()]], 201);
            }
            return $this->fail($this->language->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding language');
        }
    }

    public function updateLanguage($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->language->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Language not found or unauthorized');
            }
            $data = $this->request->getJSON(true);
            if ($this->language->update($id, $data)) {
                return $this->respond(['message' => 'Language updated'], 200);
            }
            return $this->fail($this->language->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating language');
        }
    }

    public function deleteLanguage($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->language->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Language not found or unauthorized');
            }
            $this->language->delete($id);
            return $this->respond(['message' => 'Language deleted'], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting language');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // HOBBIES / SPORTS / TALENTS
    // ═══════════════════════════════════════════════════════════════════════════

    public function getHobbies()
    {
        try {
            $userId = auth()->user()->id;
            return $this->respond(['data' => $this->hobby->where('employee_id', $userId)->findAll()], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching hobbies');
        }
    }

    public function getHobby($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->hobby->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failNotFound('Hobby not found');
            }
            return $this->respond(['data' => $record], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching hobby');
        }
    }

    public function addHobby()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            if ($this->hobby->insert($data)) {
                return $this->respond(['message' => 'Hobby added', 'data' => ['id' => $this->hobby->getInsertID()]], 201);
            }
            return $this->fail($this->hobby->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding hobby');
        }
    }

    public function updateHobby($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->hobby->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Hobby not found or unauthorized');
            }
            $data = $this->request->getJSON(true);
            if ($this->hobby->update($id, $data)) {
                return $this->respond(['message' => 'Hobby updated'], 200);
            }
            return $this->fail($this->hobby->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating hobby');
        }
    }

    public function deleteHobby($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->hobby->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Hobby not found or unauthorized');
            }
            $this->hobby->delete($id);
            return $this->respond(['message' => 'Hobby deleted'], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting hobby');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // VOLUNTEER ACTIVITIES
    // ═══════════════════════════════════════════════════════════════════════════

    public function getVolunteerActivities()
    {
        try {
            $userId = auth()->user()->id;
            return $this->respond(['data' => $this->volunteerActivity->where('employee_id', $userId)->findAll()], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching volunteer activities');
        }
    }

    public function getVolunteerActivity($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->volunteerActivity->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failNotFound('Volunteer activity not found');
            }
            return $this->respond(['data' => $record], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching volunteer activity');
        }
    }

    public function addVolunteerActivity()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            if ($this->volunteerActivity->insert($data)) {
                return $this->respond(['message' => 'Volunteer activity added', 'data' => ['id' => $this->volunteerActivity->getInsertID()]], 201);
            }
            return $this->fail($this->volunteerActivity->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding volunteer activity');
        }
    }

    public function updateVolunteerActivity($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->volunteerActivity->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Volunteer activity not found or unauthorized');
            }
            $data = $this->request->getJSON(true);
            if ($this->volunteerActivity->update($id, $data)) {
                return $this->respond(['message' => 'Volunteer activity updated'], 200);
            }
            return $this->fail($this->volunteerActivity->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating volunteer activity');
        }
    }

    public function deleteVolunteerActivity($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->volunteerActivity->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Volunteer activity not found or unauthorized');
            }
            $this->volunteerActivity->delete($id);
            return $this->respond(['message' => 'Volunteer activity deleted'], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting volunteer activity');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PATENTS
    // ═══════════════════════════════════════════════════════════════════════════

    public function getPatents()
    {
        try {
            $userId = auth()->user()->id;
            return $this->respond(['data' => $this->patent->where('employee_id', $userId)->findAll()], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching patents');
        }
    }

    public function addPatent()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            if ($this->patent->insert($data)) {
                return $this->respond(['message' => 'Patent added', 'data' => ['id' => $this->patent->getInsertID()]], 201);
            }
            return $this->fail($this->patent->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding patent');
        }
    }

    public function updatePatent($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->patent->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Patent not found or unauthorized');
            }
            $data = $this->request->getJSON(true);
            if ($this->patent->update($id, $data)) {
                return $this->respond(['message' => 'Patent updated'], 200);
            }
            return $this->fail($this->patent->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating patent');
        }
    }

    public function getPatent($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->patent->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failNotFound('Patent not found');
            }
            return $this->respond(['data' => $record], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching patent');
        }
    }

    public function deletePatent($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->patent->find($id);
            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Patent not found or unauthorized');
            }
            $this->patent->delete($id);
            return $this->respond(['message' => 'Patent deleted'], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting patent');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PHYSICAL LOCATION
    // ═══════════════════════════════════════════════════════════════════════════

    public function getPhysicalLocation()
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->physicalLocation->where('employee_id', $userId)->first();

            // Auto-create with defaults if no record exists
            if (!$record) {
                $defaults = [
                    'employee_id'      => $userId,
                    'office_name'      => 'Main Office',
                    'building'         => 'Building A',
                    'floor'            => '1st',
                    'desk'             => 'A-001',
                    'work_arrangement' => 'On-site',
                    'office_days'      => 5,
                    'time_zone'        => 'IST (UTC+5:30)',
                    'country'          => 'India',
                    'region'           => 'South Asia',
                ];
                $this->physicalLocation->insert($defaults);
                $record = $this->physicalLocation->where('employee_id', $userId)->first();
            }

            return $this->respond(['data' => $record], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching physical location: ' . $e->getMessage());
        }
    }

    public function updatePhysicalLocation()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);

            $allowed = ['office_name', 'building', 'floor', 'desk', 'work_arrangement',
                        'office_days', 'time_zone', 'country', 'region'];
            $updateData = [];
            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                return $this->fail('No fields to update', 400);
            }

            $existing = $this->physicalLocation->where('employee_id', $userId)->first();
            if ($existing) {
                if ($this->physicalLocation->update($existing['id'], $updateData)) {
                    $record = $this->physicalLocation->where('employee_id', $userId)->first();
                    return $this->respond(['data' => $record, 'message' => 'Physical location updated'], 200);
                }
            } else {
                $updateData['employee_id'] = $userId;
                if ($this->physicalLocation->insert($updateData)) {
                    $record = $this->physicalLocation->where('employee_id', $userId)->first();
                    return $this->respond(['data' => $record, 'message' => 'Physical location created'], 201);
                }
            }
            return $this->fail($this->physicalLocation->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating physical location: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // WORKING CONDITIONS
    // ═══════════════════════════════════════════════════════════════════════════

    public function getWorkingConditions()
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->workingCondition->where('employee_id', $userId)->first();

            // Auto-create empty record if none exists
            if (!$record) {
                $defaults = [
                    'employee_id'              => $userId,
                    'accommodation_required'    => 0,
                    'accommodation_type'        => null,
                    'special_equipment'         => null,
                    'last_ergonomic_assessment' => null,
                    'notes'                     => null,
                ];
                $this->workingCondition->insert($defaults);
                $record = $this->workingCondition->where('employee_id', $userId)->first();
            }

            return $this->respond(['data' => $record], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching working conditions');
        }
    }

    public function updateWorkingConditions()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            $existing = $this->workingCondition->where('employee_id', $userId)->first();
            if ($existing) {
                if ($this->workingCondition->update($existing['id'], $data)) {
                    return $this->respond(['message' => 'Working conditions updated'], 200);
                }
            } else {
                if ($this->workingCondition->insert($data)) {
                    return $this->respond(['message' => 'Working conditions created'], 201);
                }
            }
            return $this->fail($this->workingCondition->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating working conditions');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MOBILITY PREFERENCES
    // ═══════════════════════════════════════════════════════════════════════════

    public function getMobilityPreferences()
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->mobilityPreference->where('employee_id', $userId)->first();
            return $this->respond(['data' => $record], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching mobility preferences');
        }
    }

    public function updateMobilityPreferences()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            $existing = $this->mobilityPreference->where('employee_id', $userId)->first();
            if ($existing) {
                if ($this->mobilityPreference->update($existing['id'], $data)) {
                    return $this->respond(['message' => 'Mobility preferences updated'], 200);
                }
            } else {
                if ($this->mobilityPreference->insert($data)) {
                    return $this->respond(['message' => 'Mobility preferences created'], 201);
                }
            }
            return $this->fail($this->mobilityPreference->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating mobility preferences');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // GDPR Consent Management
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Get all consents for current user
     * GET /profile/consents
     */
    public function getConsents()
    {
        try {
            $userId = auth()->user()->id;
            $consents = $this->gdprConsent->where('employee_id', $userId)->findAll();
            return $this->respond(['data' => $consents], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching consents');
        }
    }

    /**
     * Record or update consent
     * POST /profile/consents
     */
    public function recordConsent()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;
            $data['ip_address'] = $this->request->getIPAddress();
            $data['user_agent'] = $this->request->getUserAgent()->getAgentString();

            $consentType = $data['consent_type'] ?? '';
            $consentGiven = (int)($data['consent_given'] ?? 0);

            // Check if consent of this type already exists
            $existing = $this->gdprConsent
                ->where('employee_id', $userId)
                ->where('consent_type', $consentType)
                ->first();

            if ($existing) {
                // Update existing consent
                $updateData = [
                    'consent_given' => $consentGiven,
                    'ip_address'    => $data['ip_address'],
                    'user_agent'    => $data['user_agent'],
                    'consent_version' => $data['consent_version'] ?? '1.0',
                    'notes'         => $data['notes'] ?? null,
                ];

                if ($consentGiven) {
                    $updateData['consent_date'] = date('Y-m-d H:i:s');
                    $updateData['withdrawal_date'] = null;
                } else {
                    $updateData['withdrawal_date'] = date('Y-m-d H:i:s');
                }

                $this->gdprConsent->update($existing['id'], $updateData);
                return $this->respond(['message' => 'Consent updated'], 200);
            }

            // New consent
            $data['consent_date'] = $consentGiven ? date('Y-m-d H:i:s') : null;
            $data['withdrawal_date'] = $consentGiven ? null : date('Y-m-d H:i:s');

            if ($this->gdprConsent->insert($data)) {
                return $this->respond(['message' => 'Consent recorded'], 201);
            }
            return $this->fail($this->gdprConsent->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error recording consent');
        }
    }

    /**
     * Withdraw all consents (right to erasure request)
     * DELETE /profile/consents
     */
    public function withdrawAllConsents()
    {
        try {
            $userId = auth()->user()->id;
            $now = date('Y-m-d H:i:s');

            $consents = $this->gdprConsent->where('employee_id', $userId)->findAll();
            foreach ($consents as $consent) {
                $this->gdprConsent->update($consent['id'], [
                    'consent_given'   => 0,
                    'withdrawal_date' => $now,
                    'ip_address'      => $this->request->getIPAddress(),
                ]);
            }

            return $this->respond(['message' => 'All consents withdrawn'], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error withdrawing consents');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Data Version History
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Get version history for a specific entity
     * GET /profile/versions?entity_type=personal_details&entity_id=1
     */
    public function getVersionHistory()
    {
        try {
            $entityType = $this->request->getVar('entity_type');
            $entityId   = $this->request->getVar('entity_id');

            if (!$entityType || !$entityId) {
                return $this->fail('entity_type and entity_id are required', 400);
            }

            $versions = $this->dataVersion->getHistory($entityType, (int)$entityId);
            return $this->respond(['data' => $versions], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching version history');
        }
    }
}
