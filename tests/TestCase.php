<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * phpunit.xml points the suite at an in-memory database, but a cached
     * config (bootstrap/cache/config.php, written by `php artisan config:cache`
     * for production) is loaded ahead of those env vars and silently wins. That
     * aims RefreshDatabase — which runs migrate:fresh — at the live SQLite file.
     *
     * Rather than rely on remembering to clear the cache, refuse to run at all
     * unless the connection really is the throwaway one.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $database = config('database.connections.'.config('database.default').'.database');

        if ($database !== ':memory:') {
            throw new RuntimeException(
                "Tests are pointed at [{$database}] instead of :memory:. "
                .'This is almost always a stale bootstrap/cache/config.php — '
                .'run `php artisan config:clear` before the suite.',
            );
        }
    }
}
