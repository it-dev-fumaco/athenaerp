<?php

namespace Tests\Unit;

use Tests\TestCase;

class TypesenseConfigTest extends TestCase
{
    public function test_typesense_config_has_client_nodes_and_collection(): void
    {
        $this->assertIsArray(config('typesense.client'));
        $this->assertArrayHasKey('nodes', config('typesense.client'));
        $this->assertIsArray(config('typesense.client.nodes'));
        $this->assertNotEmpty(config('typesense.client.nodes'));
        $this->assertIsString(config('typesense.collection'));
    }
}
