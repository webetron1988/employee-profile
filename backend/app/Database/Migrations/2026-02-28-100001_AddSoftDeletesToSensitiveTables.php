<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSoftDeletesToSensitiveTables extends Migration
{
    private array $tables = [
        'govt_ids',
        'health_records',
        'personal_details',
        'addresses',
        'family_dependents',
        'bank_details',
        'emergency_contacts',
    ];

    public function up()
    {
        foreach ($this->tables as $table) {
            $this->forge->addColumn($table, [
                'deleted_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
            ]);
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            $this->forge->dropColumn($table, 'deleted_at');
        }
    }
}
