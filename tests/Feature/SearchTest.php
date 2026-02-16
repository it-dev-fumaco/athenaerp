<?php

namespace Tests\Feature;

use Tests\TestCase;

class SearchTest extends TestCase
{
    public function test_search_results_requires_authentication(): void
    {
        $response = $this->get('/search_results');

        $response->assertRedirect(route('login'));
    }
}
