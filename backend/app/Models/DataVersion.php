<?php

namespace App\Models;

use CodeIgniter\Model;

class DataVersion extends Model
{
    protected $table = 'data_versions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'entity_type',
        'entity_id',
        'employee_id',
        'version_number',
        'old_data',
        'new_data',
        'changed_fields',
        'changed_by',
        'change_reason',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Create a version snapshot before updating a record.
     *
     * @param string $entityType  Table name (e.g. 'personal_details')
     * @param int    $entityId    Record primary key
     * @param int    $employeeId  Employee who owns the data
     * @param array  $oldData     Previous record data
     * @param array  $newData     New data being written
     * @param int|null $changedBy User ID who made the change
     * @param string|null $reason Reason for the change
     */
    public function createSnapshot(
        string $entityType,
        int    $entityId,
        int    $employeeId,
        array  $oldData,
        array  $newData,
        ?int   $changedBy = null,
        ?string $reason = null
    ): int|false {
        // Determine which fields changed
        $changedFields = [];
        foreach ($newData as $key => $value) {
            if (in_array($key, ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            if (!array_key_exists($key, $oldData) || $oldData[$key] !== $value) {
                $changedFields[] = $key;
            }
        }

        if (empty($changedFields)) {
            return false; // No actual changes
        }

        // Get next version number
        $lastVersion = $this->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('version_number', 'DESC')
            ->first();

        $versionNumber = ($lastVersion['version_number'] ?? 0) + 1;

        // Strip sensitive encrypted fields from snapshots
        $sensitiveKeys = ['password_hash', 'refresh_token_hash'];
        $safeOld = array_diff_key($oldData, array_flip($sensitiveKeys));
        $safeNew = array_diff_key($newData, array_flip($sensitiveKeys));

        return $this->insert([
            'entity_type'    => $entityType,
            'entity_id'      => $entityId,
            'employee_id'    => $employeeId,
            'version_number' => $versionNumber,
            'old_data'       => json_encode($safeOld),
            'new_data'       => json_encode($safeNew),
            'changed_fields' => implode(',', $changedFields),
            'changed_by'     => $changedBy,
            'change_reason'  => $reason,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get version history for a specific record
     */
    public function getHistory(string $entityType, int $entityId): array
    {
        return $this->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('version_number', 'DESC')
            ->findAll();
    }
}
