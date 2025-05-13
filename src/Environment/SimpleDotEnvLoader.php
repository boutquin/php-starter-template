<?php

declare(strict_types=1);

namespace App\Environment;

use Psr\Log\LoggerInterface;

/**
 * Class SimpleDotEnvLoader
 *
 * Lightweight, framework-agnostic `.env` loader for PHP CLI, test, or bootstrap scripts.
 *
 * Purpose:
 * - Loads environment variables from a flat .env file into $_ENV and $_SERVER
 * - Avoids overwriting existing entries
 * - Optionally logs diagnostic messages via PSR-3 logger
 *
 * Features:
 * - Supports export VAR=VALUE syntax
 * - Ignores blank lines and comments
 * - Quotes and escapes are handled properly
 * - Masks sensitive values in logs
 *
 * Usage:
 * $loader = new SimpleDotEnvLoader($logger);
 * $loader->load(__DIR__ . '/../.env');
 *
 * @author
 *      Pierre G. Boutquin <github.com/boutquin>
 * @license
 *      Apache-2.0
 *
 * @see
 *      https://github.com/boutquin/php-template
 */
final class SimpleDotEnvLoader
{
    /**
     * Whether the loader has already run.
     *
     * @var bool
     */
    private bool $loaded = false;

    /**
     * Optional logger for info/debug output.
     *
     * @var LoggerInterface|null
     */
    private ?LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param LoggerInterface|null $logger Optional PSR-3 logger for diagnostics
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Loads the .env file if it hasn't already been processed.
     *
     * @param string $path Absolute path to .env file
     *
     * @return void
     */
    public function load(string $path): void
    {
        if ($this->loaded) {
            $this->logger?->info('DotEnv already loaded. Skipping.');
            return;
        }

        if (!is_file($path)) {
            $this->logger?->warning("DotEnv file not found: {$path}");
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            $this->logger?->error("Failed to read dotenv file: {$path}");
            return;
        }

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Support optional 'export' keyword
            $line = preg_replace('/^export\s+/', '', $line);

            if (!is_string($line)) {
                continue;
            }

            $result = preg_match('/^([\w.-]+)\s*=\s*(.*)$/', $line, $matches);

            if ($result === false) {
                $this->logger?->debug("Regex error at line {$lineNumber}: {$line}");
                continue;
            }

            if ($result === 0) {
                // Line did not match expected pattern
                continue;
            }

            [$full, $key, $value] = $matches;
            $value = $this->unquoteValue($value);

            $wasSet = false;

            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                $wasSet = true;
            }

            if (!isset($_SERVER[$key])) {
                $_SERVER[$key] = $value;
                $wasSet = true;
            }

            if ($wasSet && $this->logger !== null) {
                $masked = $this->maskIfSensitive($key, $value);
                $this->logger->info("Loaded env var: {$key}={$masked}");
            }
        }

        $this->loaded = true;
    }

    /**
     * Resets the loader state, allowing reprocessing.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->loaded = false;
    }

    /**
     * Unquotes a value and processes common escape sequences.
     *
     * @param string $raw Raw value from .env line
     *
     * @return string Unquoted and unescaped value
     */
    private function unquoteValue(string $raw): string
    {
        $raw = trim($raw);

        if (
            (str_starts_with($raw, '"') && str_ends_with($raw, '"')) ||
            (str_starts_with($raw, "'") && str_ends_with($raw, "'"))
        ) {
            $raw = substr($raw, 1, -1);
        }

        return str_replace(
            ['\\n', '\\r', '\\t', '\\\\'],
            ["\n", "\r", "\t", "\\"],
            $raw
        );
    }

    /**
     * Returns a masked value if the key appears to be sensitive.
     *
     * @param string $key Environment variable name
     * @param string $value Original value
     *
     * @return string Masked or raw value for logging
     */
    private function maskIfSensitive(string $key, string $value): string
    {
        $sensitiveParts = [
            'password',
            'secret',
            'token',
            'api_key',
            'auth_key',
            'private_key',
        ];

        $keyLower = strtolower($key);

        foreach ($sensitiveParts as $part) {
            if (str_contains($keyLower, $part)) {
                return '*** (masked)';
            }
        }

        return $value;
    }
}
