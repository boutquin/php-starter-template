<?php

declare(strict_types=1);

namespace App\Environment;

use Psr\Log\LoggerInterface;

/**
 * Class SimpleDotEnvLoader
 *
 * Loads environment variables from a `.env` file into the runtime environment.
 *
 * Provides a lightweight, framework-agnostic solution for injecting config
 * at runtime, such as in CLI scripts, test suites, or bootstrap files.
 */
final class SimpleDotEnvLoader
{
    private bool $loaded = false;
    private ?LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param LoggerInterface|null $logger Optional PSR-3 logger for diagnostics.
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Loads a .env file if not already loaded.
     *
     * @param string $path Absolute path to .env file.
     *
     * @return void
     */
    public function load(string $path): void
    {
        if ($this->loaded) {
            $this->logger?->info("DotEnv already loaded. Skipping.");
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

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $line = preg_replace('/^export\s+/', '', $line);

            if (!is_string($line)) {
                continue;
            }

            $result = preg_match('/^([\w.-]+)\s*=\s*(.*)$/', $line, $matches);

            if ($result === false) {
                $this->logger?->debug("Skipping invalid line at {$lineNumber}: {$line}");
                continue;
            }

            if ($result === 0) {
                // no match
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
     * Resets internal load state to allow reprocessing.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->loaded = false;
    }

    /**
     * Removes wrapping quotes and handles escaped characters.
     *
     * @param string $raw Raw value string.
     *
     * @return string Cleaned value.
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

        return str_replace(['\\n', '\\r', '\\t', '\\\\'], ["\n", "\r", "\t", "\\"], $raw);
    }

    /**
     * Masks sensitive values such as tokens and passwords.
     *
     * @param string $key Environment variable name.
     * @param string $value Unmasked value.
     *
     * @return string Masked or raw value.
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
