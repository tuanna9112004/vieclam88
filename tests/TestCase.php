<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    /**
     * Chặn cứng trước khi RefreshDatabase có cơ hội chạy migrate:fresh. Trong lifecycle
     * gốc của Illuminate\Foundation\Testing\TestCase, setUpTheTestEnvironment() gọi
     * refreshApplication() (=> createApplication(), ở đây) TRƯỚC khi gọi setUpTraits()
     * (nơi RefreshDatabase::refreshDatabase() chạy migrate:fresh) — xem
     * vendor/laravel/framework/src/Illuminate/Foundation/Testing/Concerns/
     * InteractsWithTestCaseLifecycle.php:96-115. Đặt guard sau parent::setUp() (cách cũ)
     * chạy quá muộn vì parent::setUp() đã bao gồm cả setUpTraits(). Override
     * createApplication() thay vì setUp() để guard chắc chắn chạy trước.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        TestDatabaseGuard::assertSafe(
            $app->environment(),
            $app['config']->get('database.connections.'.$app['config']->get('database.default').'.database')
        );

        return $app;
    }
}
