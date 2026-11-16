<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The home page sends visitors to the sign-up page.
     */
    public function test_home_redirects_to_register(): void
    {
        $this->get('/')->assertRedirect('/register');
    }
}
