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
    public function test_the_login_page_returns_a_successful_response(): void
    {
        config()->set('app.url', 'http://localhost');
        app('url')->forceRootUrl('http://localhost');

        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
