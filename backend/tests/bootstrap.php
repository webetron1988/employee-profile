<?php

/**
 * PHPUnit bootstrap for CodeIgniter 4 test suite.
 *
 * Sets up the environment, paths, and loads the CI4 test bootstrap
 * so all CI4 helpers, models, and infrastructure are available.
 */

// Force test environment
$_SERVER['CI_ENVIRONMENT'] = 'testing';
putenv('CI_ENVIRONMENT=testing');

// On Windows, OpenSSL key generation requires OPENSSL_CONF to be set.
// Auto-detect the openssl.cnf location if not already configured.
if (PHP_OS_FAMILY === 'Windows' && empty(getenv('OPENSSL_CONF'))) {
    $candidates = [
        'C:\\wamp64new\\bin\\apache\\apache2.4.54.2\\conf\\openssl.cnf',
        'C:\\wamp64\\bin\\apache\\apache2.4.54.2\\conf\\openssl.cnf',
        'C:\\OpenSSL-Win64\\openssl.cnf',
        'C:\\Program Files\\OpenSSL-Win64\\openssl.cnf',
    ];
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            putenv('OPENSSL_CONF=' . $candidate);
            break;
        }
    }
}

// Root of the project (one level above tests/)
$rootPath = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR;

// CI4 requires FCPATH to point to the public directory
if (!defined('FCPATH')) {
    define('FCPATH', $rootPath . 'public' . DIRECTORY_SEPARATOR);
}

// Change into the public directory (CI4 expectation)
if (is_dir(FCPATH)) {
    chdir(FCPATH);
}

// Resolve the CI4 system directory from vendor (Composer install)
$systemPath = $rootPath . 'vendor' . DIRECTORY_SEPARATOR
    . 'codeigniter4' . DIRECTORY_SEPARATOR
    . 'framework' . DIRECTORY_SEPARATOR
    . 'system' . DIRECTORY_SEPARATOR;

if (!is_dir($systemPath)) {
    // Fallback for alternate Composer package name
    $systemPath = $rootPath . 'vendor' . DIRECTORY_SEPARATOR
        . 'codeigniter4' . DIRECTORY_SEPARATOR
        . 'codeigniter4' . DIRECTORY_SEPARATOR
        . 'system' . DIRECTORY_SEPARATOR;
}

if (!is_dir($systemPath)) {
    echo "ERROR: Could not locate CodeIgniter 4 system directory.\n";
    echo "Looked at: $systemPath\n";
    echo "Run 'composer install' and try again.\n";
    exit(1);
}

// Define HOMEPATH and CONFIGPATH before CI4's bootstrap so it doesn't
// use getcwd() (which points to public/ after the chdir above) and end
// up with a false CONFIGPATH that causes "\Paths.php: No such file".
defined('HOMEPATH')   || define('HOMEPATH',   $rootPath);
defined('CONFIGPATH') || define('CONFIGPATH', $rootPath . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR);
defined('PUBLICPATH') || define('PUBLICPATH', $rootPath . 'public' . DIRECTORY_SEPARATOR);

// Load CI4's own test bootstrap (sets SYSTEMPATH, APPPATH, autoloader, etc.)
require $systemPath . 'Test' . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Load .env.testing if it exists (overrides phpunit.xml <env> entries if needed)
$envTestFile = $rootPath . '.env.testing';
if (file_exists($envTestFile)) {
    $dotenv = new \CodeIgniter\Config\DotEnv($rootPath, '.env.testing');
    $dotenv->load();
}

// Ensure the encryption key env var is set (needed by Encryptor)
if (!getenv('encryption.key')) {
    putenv('encryption.key=testkey_employee_profile_app_256');
}
if (!getenv('app.baseURL')) {
    putenv('app.baseURL=http://localhost:8000');
}
