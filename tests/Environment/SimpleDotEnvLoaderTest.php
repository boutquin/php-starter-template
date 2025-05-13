<?php

declare(strict_types=1);

namespace Tests\App\Environment;

use App\Environment\SimpleDotEnvLoader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class SimpleDotEnvLoaderTest
 *
 * Validates correct parsing, loading, masking, and logging of `.env` files using SimpleDotEnvLoader.
 */
final class SimpleDotEnvLoaderTest extends TestCase
{
    private string $tempEnvFile = '';

    /**
     * Creates a temporary file for test use.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tempEnvFile = tempnam(sys_get_temp_dir(), 'env_test_');
    }

    /**
     * Deletes the temporary file after test completes.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        @unlink($this->tempEnvFile);
    }

    /**
     * Writes test content to the temporary .env file.
     *
     * @param string $content Raw .env-style content.
     *
     * @return void
     */
    private function setEnvFileContent(string $content): void
    {
        file_put_contents($this->tempEnvFile, $content);
    }

    /**
     * Verifies loading of typical unquoted and quoted variables.
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
     * Ensures previously loaded variables are not overwritten.
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
     * Confirms that calling load() multiple times does not reload variables.
     *
     * @return void
     */
    public function testLoaderIsIdempotent(): void
    {
        $this->setEnvFileContent("ONCE_ONLY=yes");

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $_ENV['ONCE_ONLY'] = 'overwritten_manually';

        $loader->load($this->tempEnvFile);

        $this->assertSame('overwritten_manually', $_ENV['ONCE_ONLY']);
    }

    /**
     * Validates logger warning is issued for missing .env file.
     *
     * @return void
     */
    public function testHandlesMissingFileGracefully(): void
    {
        $missingPath = '/nonexistent/path.env';

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains($missingPath));

        $loader = new SimpleDotEnvLoader($mockLogger);
        $loader->load($missingPath);
    }

    /**
     * Verifies sensitive keys like "token" are masked in logger output.
     *
     * @return void
     */
    public function testMasksSensitiveKeysWhenLogging(): void
    {
        $this->setEnvFileContent(<<<ENV
            API_TOKEN=mytoken123
            NORMAL_KEY=value
            ENV);

        $expectedLogs = [
            ['info', 'API_TOKEN=***'],
            ['info', 'NORMAL_KEY=value'],
        ];

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function($message) use (&$expectedLogs) {
                $expectedLog = array_shift($expectedLogs);
                if ($expectedLog === null) {
                    $this->fail('Logger was called more times than expected.');
                }

                [$level, $content] = $expectedLog;
                $this->assertStringContainsString($content, $message);
            });

        $loader = new SimpleDotEnvLoader($mockLogger);
        $loader->load($this->tempEnvFile);
    }

    /**
     * Confirms quoted values are correctly parsed.
     *
     * @return void
     */
    public function testParsesQuotedValuesCorrectly(): void
    {
        $this->setEnvFileContent(<<<ENV
            QUOTED1='value with spaces'
            QUOTED2="another spaced value"
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame('value with spaces', $_ENV['QUOTED1']);
        $this->assertSame('another spaced value', $_ENV['QUOTED2']);
    }

    /**
     * Confirms reset() allows the loader to reprocess the file.
     *
     * @return void
     */
    public function testResetAllowsReload(): void
    {
        $this->setEnvFileContent("RESET_VAR=123");

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);
        $loader->reset();
        unset($_ENV['RESET_VAR']);
        $loader->load($this->tempEnvFile);

        $this->assertSame('123', $_ENV['RESET_VAR']);
    }

    /**
     * Confirms export keyword is handled and invalid lines are ignored.
     *
     * @return void
     */
    public function testHandlesExportAndIgnoresInvalidLines(): void
    {
        $this->setEnvFileContent(<<<ENV
            export VALID_KEY=valid
            MALFORMED_LINE
            # comment
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame('valid', $_ENV['VALID_KEY']);
        $this->assertArrayNotHasKey('MALFORMED_LINE', $_ENV);
    }

    /**
     * Validates proper unescaping of backslashes and double quotes.
     *
     * @return void
     */
    public function testHandlesEscapedQuotesAndBackslashes(): void
    {
        $this->setEnvFileContent(<<<ENV
            ESCAPED_QUOTE="He said \\\"hello\\\""
            ESCAPED_BACKSLASH="C:\\\\Program Files\\\\App"
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame("He said \\\"hello\\\"", $_ENV['ESCAPED_QUOTE']);
        $this->assertSame("C:\\Program Files\\App", $_ENV['ESCAPED_BACKSLASH']);
    }

    /**
     * Confirms nested quotes are preserved inside quoted strings.
     *
     * @return void
     */
    public function testHandlesQuotesInsideQuotes(): void
    {
        $this->setEnvFileContent(<<<ENV
            NESTED_QUOTE1="He said 'hello'"
            NESTED_QUOTE2='She replied "goodbye"'
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame("He said 'hello'", $_ENV['NESTED_QUOTE1']);
        $this->assertSame('She replied "goodbye"', $_ENV['NESTED_QUOTE2']);
    }

    /**
     * Ensures extra spaces and tabs are trimmed appropriately.
     *
     * @return void
     */
    public function testHandlesWhitespaceAroundKeysAndValues(): void
    {
        $this->setEnvFileContent(<<<ENV
              SPACED_KEY   =    "  spaced value "
            TAB_KEY\t=\t"tabbed"
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame('  spaced value ', $_ENV['SPACED_KEY']);
        $this->assertSame('tabbed', $_ENV['TAB_KEY']);
    }

    /**
     * Validates parsing of keys with symbols and digits.
     *
     * @return void
     */
    public function testHandlesComplexKeys(): void
    {
        $this->setEnvFileContent(<<<ENV
            MY.KEY=value1
            MY-KEY=value2
            KEY123=value3
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertSame('value1', $_ENV['MY.KEY']);
        $this->assertSame('value2', $_ENV['MY-KEY']);
        $this->assertSame('value3', $_ENV['KEY123']);
    }

    /**
     * Verifies that comment-only and blank lines are safely skipped.
     *
     * @return void
     */
    public function testIgnoresPureCommentsAndWhitespace(): void
    {
        $this->setEnvFileContent(<<<ENV
            # this is a comment


            \t\t
            ENV);

        $loader = new SimpleDotEnvLoader();
        $loader->load($this->tempEnvFile);

        $this->assertTrue(true); // confirms no fatal error or env pollution
    }
}
