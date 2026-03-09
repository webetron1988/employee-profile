<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsToMobilityAndTransfers extends Migration
{
    public function up()
    {
        // Add international_interest and remote_preference to mobility_preferences
        $this->forge->addColumn('mobility_preferences', [
            'international_interest' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null, 'after' => 'preferred_role'],
            'remote_preference' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null, 'after' => 'international_interest'],
        ]);

        // Add key_achievement and skills_gained to transfers
        $this->forge->addColumn('transfers', [
            'key_achievement' => ['type' => 'TEXT', 'null' => true, 'after' => 'transfer_reason'],
            'skills_gained' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'key_achievement'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('mobility_preferences', ['international_interest', 'remote_preference']);
        $this->forge->dropColumn('transfers', ['key_achievement', 'skills_gained']);
    }
}
