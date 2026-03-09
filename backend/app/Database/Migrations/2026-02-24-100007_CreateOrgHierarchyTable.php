<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrgHierarchyTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'parent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'department' => ['type' => 'VARCHAR', 'constraint' => 100],
            'division' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'section' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'team' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'org_level' => ['type' => 'INT', 'constraint' => 11],
            'hierarchy_path' => ['type' => 'VARCHAR', 'constraint' => 191],
            'is_manager' => ['type' => 'BOOLEAN', 'default' => 0],
            'team_size' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_org_hierarchy_employee');
        $this->forge->addKey('department');
        $this->forge->addKey('parent_id');
        $this->forge->addKey('hierarchy_path');
        $this->forge->createTable('org_hierarchy', true);
    }

    public function down()
    {
        $this->forge->dropTable('org_hierarchy', true);
    }
}
