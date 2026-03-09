<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSyncLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'sync_type' => [
                'type' => 'ENUM',
                'constraint' => ['employee_master', 'job_info', 'org_hierarchy', 'manager_relationships'],
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['started', 'success', 'failed'],
            ],
            'records_processed' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'records_failed' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'error_details' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'started_at' => [
                'type' => 'TIMESTAMP',
            ],
            'completed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('sync_type');
        $this->forge->addKey('status');
        $this->forge->createTable('sync_logs');
    }

    public function down()
    {
        $this->forge->dropTable('sync_logs');
    }
}
