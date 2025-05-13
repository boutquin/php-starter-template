<?php

declare(strict_types=1);

namespace Tests\App\Environment;

use App\Environment\SimpleDotEnvLoader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class SimpleDotEnvLoaderTest
 *
 * Comprehensive PHPUnit suite ensuring the reliability of {@see SimpleDotEnvLoader}.
 *
 * Purpose:
 * - Verifies correct loading of .env files into $_ENV/$_SERVER without overwriting existing keys.
 * - Confirms quoted/escaped values are parsed, sensitive keys are masked in logs, and runtime idempotence.
 * - Guards against regressions by covering error cases such as missing files or malformed lines.
 *
 * Features:
 * - Supports `export VAR=VALUE` syntax, blank‑line/comment skipping, complex keys, and whitespace tolerance.
 * - Utilises temp files for isolation and PSR‑3 logger mocks to assert diagnostic output.
 * - Covers reset() functionality plus cache‑busting behaviour.
 *
 * Usage:
 * ```bash
 * vendor/bin/phpunit --filter SimpleDotEnvLoaderTest
 * ```
 *
 * @author
 *      Pierre G. Boutquin <github.com/boutquin>
 * @license
 *      Apache-2.0
 *
 * @see
 *      https://github.com/boutquin/php-template
 *
 * @covers  \App\Environment\SimpleDotEnvLoader
 *
 * @uses    \App\Environment\SimpleDotEnvLoader::unquoteValue
 * @uses    \App\Environment\SimpleDotEnvLoader::maskIfSensitive
 */
final class SimpleDotEnvLoaderTest extends TestCase
{
    /**
     * Temporary .env file path created for each test.
     */
    private string $tempEnvFile = '';

    /**
     * Creates a temporary file before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tempEnvFile = tempnam(sys_get_temp_dir(), 'env_test_');
    }

    /**
     * Removes the temporary file after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        @unlink($this->tempEnvFile);
    }

    /**
     * Helper to write .env‑style content into the temp file.
     *
     * @param string $content Raw .env content.
     *
     * @return void
     */
    private function setEnvFileContent(string $content): void
    {
        file_put_contents($this->tempEnvFile, $content);
    }

    /* --------------------------------------------------------------------- */
    /*                              Happy Path                               */
    /* --------------------------------------------------------------------- */

    /**
     * Ensures a well‑formed .env file is parsed and loaded into $_ENV.
     *
     * @return void
     */
    public function testLoadsValidEnvFile(): void
    {
        $this->setEnvFileContent(<<<ENV
            APP_ENV=testing
            APP_DEBUG=true
            APP_NAME="Test App"
            DB_PASSWORD=secret123
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame('testing', $_ENV['APP_ENV']);
        $this->assertSame('true', $_ENV['APP_DEBUG']);
        $this->assertSame('Test App', $_ENV['APP_NAME']);
        $this->assertSame('secret123', $_ENV['DB_PASSWORD']);
    }

    /**
     * Confirms existing environment variables are not overridden on load.
     *
     * @return void
     */
    public function testSkipsAlreadySetVariables(): void
    {
        $_ENV['APP_ENV'] = 'preset';
        $this->setEnvFileContent("APP_ENV=should_not_override");

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame('preset', $_ENV['APP_ENV']);
    }

    /**
     * Verifies that re‑invoking load() does not re‑process the same file.
     *
     * @return void
     */
    public function testLoaderIsIdempotent(): void
    {
        $this->setEnvFileContent('ONCE_ONLY=yes');

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);
        $_ENV['ONCE_ONLY'] = 'manual_change'; // mimic runtime modification
        $loader->load($this->tempEnvFile);

        $this->assertSame('manual_change', $_ENV['ONCE_ONLY']);
    }

    /* --------------------------------------------------------------------- */
    /*                              Edge Cases                               */
    /* --------------------------------------------------------------------- */

    /**
     * Ensures loading a missing file emits a warning (and does nothing else).
     *
     * @return void
     */
    public function testHandlesMissingFileGracefully(): void
    {
        $missing = '/nonexistent/.env';

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains($missing));

        $loader = new SimpleDotEnvLoader($logger);
        $loader->load($missing);
    }

    /**
     * Confirms sensitive keys (e.g., *_TOKEN, *_PASSWORD) are masked in logs.
     *
     * @return void
     */
    public function testMasksSensitiveKeysWhenLogging(): void
    {
        $this->setEnvFileContent(<<<ENV
            API_TOKEN=mytoken123
            NORMAL_KEY=value
            ENV);

        $expected = [
            ['info', 'API_TOKEN=***'],
            ['info', 'NORMAL_KEY=value'],
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function(string $message) use (&$expected): void {
                $check = array_shift($expected);
                if (!is_array($check)) {
                    self::fail('Unexpected logger invocation or missing log expectation.');
                }

                $this->assertStringContainsString($check[1], $message);
            });

        $loader = new SimpleDotEnvLoader($logger);
        $loader->load($this->tempEnvFile);
    }

    /**
     * Validates correct unquoting of single‑ and double‑quoted values.
     *
     * @return void
     */
    public function testParsesQuotedValuesCorrectly(): void
    {
        $this->setEnvFileContent(<<<ENV
            QUOTED1='hello world'
            QUOTED2="test string"
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame('hello world', $_ENV['QUOTED1']);
        $this->assertSame('test string', $_ENV['QUOTED2']);
    }

    /**
     * Ensures the internal processed‑file cache can be reset to allow reloads.
     *
     * @return void
     */
    public function testResetAllowsReload(): void
    {
        $this->setEnvFileContent('RESET_VAR=abc');

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);
        $loader->reset();
        unset($_ENV['RESET_VAR']);
        $loader->load($this->tempEnvFile);

        $this->assertSame('abc', $_ENV['RESET_VAR']);
    }

    /* --------------------------------------------------------------------- */
    /*                             Parsing Quirks                            */
    /* --------------------------------------------------------------------- */

    /**
     * Supports optional leading `export`, ignores invalid lines & comments.
     *
     * @return void
     */
    public function testHandlesExportAndIgnoresInvalidLines(): void
    {
        $this->setEnvFileContent(<<<ENV
            export VALID=ok
            INVALID_LINE
            # comment
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame('ok', $_ENV['VALID']);
        $this->assertArrayNotHasKey('INVALID_LINE', $_ENV);
    }

    /**
     * Correctly processes escaped quotes and backslashes within quoted values.
     *
     * @return void
     */
    public function testHandlesEscapedQuotesAndBackslashes(): void
    {
        $this->setEnvFileContent(<<<ENV
            ESCAPED_QUOTE="He said \\\"hi\\\""
            ESCAPED_BACKSLASH="C:\\\\Path"
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame('He said \"hi\"', $_ENV['ESCAPED_QUOTE']);
        $this->assertSame('C:\\Path', $_ENV['ESCAPED_BACKSLASH']);
    }

    /**
     * Accepts nested quotes of opposite kind within quoted values.
     *
     * @return void
     */
    public function testHandlesQuotesInsideQuotes(): void
    {
        $this->setEnvFileContent(<<<ENV
            INSIDE1="He said 'yo'"
            INSIDE2='Then she said "bye"'
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame("He said 'yo'", $_ENV['INSIDE1']);
        $this->assertSame('Then she said "bye"', $_ENV['INSIDE2']);
    }

    /**
     * Trims surrounding whitespace around keys and values.
     *
     * @return void
     */
    public function testHandlesWhitespaceAroundKeysAndValues(): void
    {
        $this->setEnvFileContent(<<<ENV
              KEY1   =   " spaced "
            TAB_KEY\t=\t"tabbed"
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame(' spaced ', $_ENV['KEY1']);
        $this->assertSame('tabbed', $_ENV['TAB_KEY']);
    }

    /**
     * Supports keys containing dots, dashes, numbers, etc.
     *
     * @return void
     */
    public function testHandlesComplexKeys(): void
    {
        $this->setEnvFileContent(<<<ENV
            KEY.ONE=1
            KEY-TWO=2
            KEY123=3
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame('1', $_ENV['KEY.ONE']);
        $this->assertSame('2', $_ENV['KEY-TWO']);
        $this->assertSame('3', $_ENV['KEY123']);
    }

    /**
     * Parsing should ignore blank lines and pure comments without crashing.
     *
     * @return void
     */
    public function testIgnoresPureCommentsAndWhitespace(): void
    {
        $this->setEnvFileContent(<<<ENV
            # just a comment


            \t
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertTrue(true); // No assertions beyond “no crash”.
    }
}
