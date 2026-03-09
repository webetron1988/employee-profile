<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContractFieldsToJobInformation extends Migration
{
    public function up()
    {
        $this->forge->addColumn('job_information', [
            'work_schedule'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'business_unit'],
            'weekly_hours'      => ['type' => 'DECIMAL', 'constraint' => '4,1', 'null' => true, 'after' => 'work_schedule'],
            'fte'               => ['type' => 'DECIMAL', 'constraint' => '3,2', 'null' => true, 'after' => 'weekly_hours'],
            'union_member'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'fte'],
            'contract_end_date' => ['type' => 'DATE', 'null' => true, 'after' => 'union_member'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('job_information', ['work_schedule', 'weekly_hours', 'fte', 'union_member', 'contract_end_date']);
    }
}
