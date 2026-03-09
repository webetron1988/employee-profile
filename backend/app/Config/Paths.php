<?php

namespace Config;

class Paths
{
    /**
     * Path to the CI4 system directory (installed via Composer).
     */
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * Path to the application directory.
     */
    public string $appDirectory = __DIR__ . '/..';

    /**
     * Writable directory for logs, cache, uploads, sessions.
     */
    public string $writableDirectory = __DIR__ . '/../../writable';

    /**
     * Tests directory.
     */
    public string $testsDirectory = __DIR__ . '/../../tests';

    /**
     * Views directory.
     */
    public string $viewDirectory = __DIR__ . '/../Views';

    /**
     * Directory containing the .env file.
     */
    public string $envDirectory = __DIR__ . '/../../';
}
