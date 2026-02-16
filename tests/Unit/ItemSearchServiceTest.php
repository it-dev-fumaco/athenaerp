<?php

namespace Tests\Unit;

use Tests\TestCase;

class ItemSearchServiceTest extends TestCase
{
    public function test_search_service_returns_paginator(): void
    {
        $this->markTestSkipped('Requires authenticated user and database');
    }
}
