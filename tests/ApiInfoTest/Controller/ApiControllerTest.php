<?php declare(strict_types=1);

namespace ApiInfoTest\Controller;

use ApiInfoTest\ApiInfoTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

class ApiControllerTest extends AbstractHttpControllerTestCase
{
    use ApiInfoTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        parent::tearDown();
    }

    protected function dispatchJson(string $url): array
    {
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $body = $this->getResponse()->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data, "Response for $url must be a json object.");
        return $data;
    }

    public function testInfosResourcesReturnsCounts(): void
    {
        $this->createItem('Item A');
        $data = $this->dispatchJson('/api/infos');
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('media', $data);
        $this->assertArrayHasKey('item_sets', $data);
        $this->assertArrayHasKey('total', $data['items']);
        $this->assertGreaterThanOrEqual(1, $data['items']['total']);
    }

    public function testInfosSingleResourceReturnsTotal(): void
    {
        $this->createItem('Item B');
        $data = $this->dispatchJson('/api/infos/items');
        $this->assertArrayHasKey('total', $data);
        $this->assertGreaterThanOrEqual(1, $data['total']);
    }

    public function testPingReturnsOne(): void
    {
        $this->dispatch('/api/infos/ping');
        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString('1', $this->getResponse()->getBody());
    }

    public function testInfosResourcesIncludesDigitalObjectsWhenAvailable(): void
    {
        if (!$this->hasDigitalObject()) {
            $this->markTestSkipped('Requires DigitalObject module.');
        }
        $data = $this->dispatchJson('/api/infos');
        $this->assertArrayHasKey('digital_objects', $data);
        $this->assertArrayHasKey('total', $data['digital_objects']);
    }

    public function testIdsIncludesDigitalObjectsWhenAvailable(): void
    {
        if (!$this->hasDigitalObject()) {
            $this->markTestSkipped('Requires DigitalObject module.');
        }
        $data = $this->dispatchJson('/api/infos/ids?types=digital_objects');
        $this->assertArrayHasKey('digital_objects', $data);
    }
}
