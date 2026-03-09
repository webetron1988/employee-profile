<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the system_configurations table with default application settings.
 */
class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            // Authentication
            ['config_key' => 'jwt.access_token_expiry',   'config_value' => '3600',    'config_group' => 'auth',         'description' => 'Access token expiry in seconds',          'is_sensitive' => 0],
            ['config_key' => 'jwt.refresh_token_expiry',  'config_value' => '604800',  'config_group' => 'auth',         'description' => 'Refresh token expiry in seconds (7 days)', 'is_sensitive' => 0],
            ['config_key' => 'auth.max_login_attempts',   'config_value' => '5',       'config_group' => 'auth',         'description' => 'Max failed login attempts before lockout', 'is_sensitive' => 0],
            ['config_key' => 'auth.lockout_duration',     'config_value' => '900',     'config_group' => 'auth',         'description' => 'Lockout duration in seconds (15 min)',     'is_sensitive' => 0],

            // HRMS Sync
            ['config_key' => 'hrms.sync_interval',        'config_value' => '3600',    'config_group' => 'hrms',         'description' => 'Auto-sync interval in seconds',            'is_sensitive' => 0],
            ['config_key' => 'hrms.sync_batch_size',      'config_value' => '100',     'config_group' => 'hrms',         'description' => 'Number of records per sync batch',         'is_sensitive' => 0],
            ['config_key' => 'hrms.sync_retry_attempts',  'config_value' => '3',       'config_group' => 'hrms',         'description' => 'Retry attempts on sync failure',           'is_sensitive' => 0],
            ['config_key' => 'hrms.sync_enabled',         'config_value' => 'true',    'config_group' => 'hrms',         'description' => 'Enable/disable automatic HRMS sync',       'is_sensitive' => 0],

            // File Upload
            ['config_key' => 'upload.max_file_size_mb',   'config_value' => '10',      'config_group' => 'upload',       'description' => 'Maximum upload file size in MB',          'is_sensitive' => 0],
            ['config_key' => 'upload.allowed_image_types','config_value' => 'jpeg,png,webp,gif', 'config_group' => 'upload', 'description' => 'Allowed image MIME types',         'is_sensitive' => 0],
            ['config_key' => 'upload.allowed_doc_types',  'config_value' => 'pdf,doc,docx',     'config_group' => 'upload', 'description' => 'Allowed document MIME types',       'is_sensitive' => 0],
            ['config_key' => 'upload.csv_max_size_mb',    'config_value' => '5',       'config_group' => 'upload',       'description' => 'Max CSV file size for bulk import',        'is_sensitive' => 0],

            // Performance Reviews
            ['config_key' => 'performance.review_period', 'config_value' => 'Annual',  'config_group' => 'performance',  'description' => 'Default review period (Annual/Half-Yearly)','is_sensitive' => 0],
            ['config_key' => 'performance.rating_scale',  'config_value' => '5',       'config_group' => 'performance',  'description' => 'Performance rating scale (max value)',     'is_sensitive' => 0],
            ['config_key' => 'performance.goals_per_cycle','config_value' => '5',      'config_group' => 'performance',  'description' => 'Recommended goals per review cycle',       'is_sensitive' => 0],

            // Notifications
            ['config_key' => 'notify.cert_expiry_days',   'config_value' => '30',      'config_group' => 'notifications','description' => 'Days before cert expiry to notify',        'is_sensitive' => 0],
            ['config_key' => 'notify.doc_expiry_days',    'config_value' => '30',      'config_group' => 'notifications','description' => 'Days before document expiry to notify',    'is_sensitive' => 0],
            ['config_key' => 'notify.review_reminder_days','config_value' => '7',      'config_group' => 'notifications','description' => 'Days before review deadline to remind',    'is_sensitive' => 0],

            // Pagination
            ['config_key' => 'pagination.default_per_page','config_value' => '20',     'config_group' => 'pagination',   'description' => 'Default records per page',                 'is_sensitive' => 0],
            ['config_key' => 'pagination.max_per_page',   'config_value' => '200',     'config_group' => 'pagination',   'description' => 'Maximum allowed records per page',         'is_sensitive' => 0],

            // Maintenance
            ['config_key' => 'app.maintenance_mode',      'config_value' => 'false',   'config_group' => 'app',          'description' => 'Enable/disable maintenance mode',          'is_sensitive' => 0],
            ['config_key' => 'app.version',               'config_value' => '1.0.0',   'config_group' => 'app',          'description' => 'Application version',                      'is_sensitive' => 0],
            ['config_key' => 'app.timezone',              'config_value' => 'Asia/Kolkata', 'config_group' => 'app',     'description' => 'Default application timezone',             'is_sensitive' => 0],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($configs as &$c) {
            $c['created_at'] = $now;
            $c['updated_at'] = $now;
        }

        $this->db->table('system_configurations')->insertBatch($configs);
        echo "  ✓ Seeded " . count($configs) . " system configurations.\n";
    }
}
