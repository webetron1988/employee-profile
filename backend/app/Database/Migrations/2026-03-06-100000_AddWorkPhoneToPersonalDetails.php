<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkPhoneToPersonalDetails extends Migration
{
    public function up()
    {
        $this->forge->addColumn('personal_details', [
            'work_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'twitter_url',
            ],
            'work_extension' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'after'      => 'work_phone',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('personal_details', ['work_phone', 'work_extension']);
    }
}
