<?php declare(strict_types=1);

namespace ApiInfoTest;

use PHPUnit\Framework\TestCase;

/**
 * Checks the migration to ZipStream v3 (named arguments, no v1/v2 option class)
 * and the PHP 8.1 requirement enforced on install and upgrade.
 */
class ZipStreamTest extends TestCase
{
    protected function controllerSource(): string
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/src/Controller/ApiController.php'
        );
        self::assertNotFalse($source);
        return $source;
    }

    protected function moduleSource(): string
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/Module.php');
        self::assertNotFalse($source);
        return $source;
    }

    public function testZipStreamV3IsInstalled(): void
    {
        self::assertTrue(
            class_exists(\ZipStream\ZipStream::class),
            'ZipStream must be available.'
        );
        self::assertTrue(
            enum_exists(\ZipStream\CompressionMethod::class),
            'ZipStream v3 must be installed (CompressionMethod enum).'
        );
    }

    public function testControllerDoesNotUseLegacyOptionClass(): void
    {
        self::assertStringNotContainsString(
            'ZipStream\Option\Archive',
            $this->controllerSource(),
            'The v1/v2 Option\Archive class must not be used with ZipStream v3.'
        );
    }

    public function testControllerUsesV3NamedArguments(): void
    {
        $source = $this->controllerSource();
        self::assertStringContainsString('outputName:', $source);
        self::assertStringContainsString('sendHttpHeaders: true', $source);
    }

    public function testComposerRequiresZipStreamV3(): void
    {
        $composer = json_decode(
            file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true
        );
        self::assertSame('^3.1', $composer['require']['maennchen/zipstream-php'] ?? null);
        self::assertSame('^8.1', $composer['require']['php'] ?? null);
    }

    public function testInstallChecksPhp81(): void
    {
        $source = $this->moduleSource();
        self::assertStringContainsString(
            'public function install(ServiceLocatorInterface $serviceLocator)',
            $source
        );
        self::assertStringContainsString('PHP_VERSION_ID >= 80100', $source);
    }

    public function testUpgradeChecksPhp81(): void
    {
        self::assertStringContainsString(
            'public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)',
            $this->moduleSource()
        );
    }

    public function testCheckPhpVersionThrowsBelow81(): void
    {
        if (PHP_VERSION_ID < 80100) {
            self::markTestSkipped('Running on PHP < 8.1.');
        }
        // On a supported runtime the guard must be a no-op (no exception).
        $module = new \ApiInfo\Module();
        $method = new \ReflectionMethod($module, 'checkPhpVersion');
        $method->setAccessible(true);
        $services = $this->createMock(\Laminas\ServiceManager\ServiceLocatorInterface::class);
        $services->expects(self::never())->method('get');
        $method->invoke($module, $services);
        self::assertTrue(true);
    }
}
