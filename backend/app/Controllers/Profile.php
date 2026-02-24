<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\PersonalDetail;
use App\Models\Address;
use App\Models\EmergencyContact;
use App\Models\GovtId;
use App\Models\BankDetail;
use App\Models\HealthRecord;
use App\Models\FamilyDependent;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Profile extends Controller
{
    use ResponseTrait;

    protected $employee;
    protected $personalDetail;
    protected $address;
    protected $emergencyContact;
    protected $govtId;
    protected $bankDetail;
    protected $healthRecord;
    protected $familyDependent;

    public function __construct()
    {
        $this->employee = new Employee();
        $this->personalDetail = new PersonalDetail();
        $this->address = new Address();
        $this->emergencyContact = new EmergencyContact();
        $this->govtId = new GovtId();
        $this->bankDetail = new BankDetail();
        $this->healthRecord = new HealthRecord();
        $this->familyDependent = new FamilyDependent();
    }

    /**
     * Get current user's profile
     * GET /profile/
     */
    public function myProfile()
    {
        try {
            $userId = auth()->user()->id;
            $employee = $this->employee->find($userId);

            if (!$employee) {
                return $this->failNotFound('Employee profile not found');
            }

            // Add related data
            $employee['personal_details'] = $this->personalDetail->where('employee_id', $userId)->first();
            $employee['job_information'] = $this->employee->find($userId)['job_information'] ?? null;
            $employee['addresses'] = $this->address->where('employee_id', $userId)->findAll();
            $employee['emergency_contacts'] = $this->emergencyContact->where('employee_id', $userId)->where('is_primary', true)->first();

            return $this->respond(['data' => $employee], 200);
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
            $employee = $this->employee->find($id);

            if (!$employee) {
                return $this->failNotFound('Employee not found');
            }

            $employee['personal_details'] = $this->personalDetail->where('employee_id', $id)->first();
            $employee['addresses'] = $this->address->where('employee_id', $id)->findAll();

            return $this->respond(['data' => $employee], 200);
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

            if ($this->employee->update($userId, $data)) {
                return $this->respond(['message' => 'Profile updated successfully'], 200);
            }

            return $this->fail($this->employee->errors(), 422);
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
                if ($this->personalDetail->update($personalDetail['id'], $data)) {
                    return $this->respond(['message' => 'Personal details updated'], 200);
                }
            } else {
                if ($this->personalDetail->insert($data)) {
                    return $this->respond(['message' => 'Personal details created'], 201);
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
                if ($this->healthRecord->update($health['id'], $data)) {
                    return $this->respond(['message' => 'Health records updated'], 200);
                }
            } else {
                if ($this->healthRecord->insert($data)) {
                    return $this->respond(['message' => 'Health records created'], 201);
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
}
