<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSystemConfigurationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'config_key' => ['type' => 'VARCHAR', 'constraint' => 100],
            'config_value' => ['type' => 'LONGTEXT'],
            'config_type' => ['type' => 'ENUM', 'constraint' => ['Boolean', 'Integer', 'String', 'JSON', 'Array'], 'default' => 'String'],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_encrypted' => ['type' => 'BOOLEAN', 'default' => 0],
            'is_active' => ['type' => 'BOOLEAN', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addUniqueKey('config_key');
        $this->forge->createTable('system_configurations', true);
    }

    public function down()
    {
        $this->forge->dropTable('system_configurations', true);
    }
}
