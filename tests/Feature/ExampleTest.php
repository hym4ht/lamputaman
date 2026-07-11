<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_guest_can_view_public_monitoring_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
