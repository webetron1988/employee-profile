<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRefreshTokenHashToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'refresh_token_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'last_login_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'refresh_token_hash');
    }
}
