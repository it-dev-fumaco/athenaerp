<?php

namespace App\Services\Typesense;

use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

class TypesenseCollectionManager
{
    public function __construct(private Client $client) {}

    public function collectionName(): string
    {
        return (string) config('typesense.collection');
    }

    /**
     * @return array<string, mixed>
     */
    public function schemaDefinition(): array
    {
        return [
            'name' => $this->collectionName(),
            'fields' => [
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'description', 'type' => 'string', 'optional' => true],
                ['name' => 'item_name', 'type' => 'string', 'optional' => true],
                ['name' => 'item_group', 'type' => 'string', 'optional' => true, 'facet' => true],
                ['name' => 'lvl1', 'type' => 'string', 'optional' => true, 'facet' => true],
                ['name' => 'lvl2', 'type' => 'string', 'optional' => true, 'facet' => true],
                ['name' => 'lvl3', 'type' => 'string', 'optional' => true, 'facet' => true],
                ['name' => 'lvl4', 'type' => 'string', 'optional' => true, 'facet' => true],
                ['name' => 'lvl5', 'type' => 'string', 'optional' => true, 'facet' => true],
                ['name' => 'item_classification', 'type' => 'string', 'optional' => true, 'facet' => true],
                ['name' => 'brand', 'type' => 'string', 'optional' => true, 'facet' => true],
                ['name' => 'stock_uom', 'type' => 'string', 'optional' => true],
                ['name' => 'supplier_part_nos', 'type' => 'string', 'optional' => true],
                ['name' => 'warehouse_codes', 'type' => 'string[]', 'optional' => true, 'facet' => true],
                ['name' => 'has_stock_warehouse', 'type' => 'bool', 'facet' => true],
                ['name' => 'modified', 'type' => 'int64'],
                ['name' => 'lifecycle_status', 'type' => 'string', 'optional' => true, 'facet' => true],
            ],
            'default_sorting_field' => 'modified',
        ];
    }

    public function ensureCollection(): void
    {
        $name = $this->collectionName();
        try {
            $this->client->collections[$name]->retrieve();

            return;
        } catch (ObjectNotFound) {
            $this->client->collections->create($this->schemaDefinition());
        }
    }

    public function deleteCollectionIfExists(): void
    {
        $name = $this->collectionName();
        try {
            $this->client->collections[$name]->delete();
        } catch (ObjectNotFound) {
            // ok
        }
    }
}
