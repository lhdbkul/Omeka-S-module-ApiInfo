<?php declare(strict_types=1);

namespace ApiInfoTest;

use PHPUnit\Framework\TestCase;

/**
 * Source-level checks that the module exposes the digital_objects resource in
 * the api controller (list, infos, ids) and in the json-ld resource listener.
 */
class DigitalObjectSupportTest extends TestCase
{
    protected function controllerSource(): string
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/src/Controller/ApiController.php'
        );
        self::assertNotFalse($source);
        return $source;
    }

    public function testGetListHandlesDigitalObjects(): void
    {
        self::assertStringContainsString(
            "\$resource === 'digital_objects' && \$this->hasResource('digital_objects')",
            $this->controllerSource(),
            'getList() must accept the digital_objects resource when available.'
        );
    }

    public function testGetInfosResourcesCountsDigitalObjects(): void
    {
        self::assertStringContainsString(
            "\$data['digital_objects']['total'] = \$api->search('digital_objects', \$query)->getTotalResults();",
            $this->controllerSource(),
            'getInfosResources() must count digital_objects when available.'
        );
    }

    public function testGetIdsIncludesDigitalObjects(): void
    {
        $source = $this->controllerSource();
        $start = strpos($source, '$defaultTypes = [');
        self::assertNotFalse($start);
        $end = strpos($source, '];', $start);
        $defaultTypes = substr($source, $start, $end - $start);
        self::assertStringContainsString(
            "'digital_objects'",
            $defaultTypes,
            'getIds() default types must include digital_objects.'
        );
    }

    public function testModuleListensToDigitalObjectRepresentation(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/Module.php');
        self::assertNotFalse($source);
        self::assertStringContainsString(
            '\DigitalObject\Api\Representation\DigitalObjectRepresentation::class',
            $source,
            'Module must attach the json-ld listener to digital objects.'
        );
    }
}
