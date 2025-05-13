<?php

declare(strict_types=1);

use App\Environment\SimpleDotEnvLoader;

/**
 * PHPUnit / toolchain bootstrap file.
 *
 * ---
 * Purpose:
 * Loads Composer autoloader and optionally loads environment variables
 * from a .env file at the project root.
 *
 * This allows environment-specific settings to be loaded before test execution
 * (e.g., DB credentials, flags, paths).
 *
 * Supports:
 * - Automatic detection of .env file in the parent directory
 * - Optional override via the TEST_DOTENV_PATH environment variable
 *
 * Example override:
 * TEST_DOTENV_PATH="$(pwd)/.env.test" vendor/bin/phpunit
 *
 * @author
 *      Pierre G. Boutquin <github.com/boutquin>
 * @license
 *      Apache-2.0
 *
 * @see
 *      https://github.com/boutquin/php-template
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Allow override for test environments (e.g., .env.test)
$dotenvPath = $_ENV['TEST_DOTENV_PATH'] ?? $_SERVER['TEST_DOTENV_PATH'] ?? dirname(__DIR__) . '/.env';

// Load .env only if the file exists
if (is_file($dotenvPath)) {
    $loader = new SimpleDotEnvLoader();
    $loader->load($dotenvPath);
}
