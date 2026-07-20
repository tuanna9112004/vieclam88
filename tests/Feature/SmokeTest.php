<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_boots_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_test_suite_uses_an_isolated_database_separate_from_development(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('vieclam88_test', DB::connection()->getDatabaseName());
        $this->assertNotSame('vieclam88', DB::connection()->getDatabaseName());
    }

    public function test_refresh_database_actually_provisions_foundation_tables(): void
    {
        $this->assertTrue(Schema::hasTable('administrative_units'));
        $this->assertTrue(Schema::hasTable('branches'));
        $this->assertTrue(Schema::hasTable('users'));
    }
}
