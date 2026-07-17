<?php

namespace Tests\Unit;

use App\Exceptions\BhwisConnectionException;
use App\Services\LocalPcDatabase;
use Tests\TestCase;

class LocalPcDatabaseTest extends TestCase
{
    public function test_missing_configuration_fails_without_attempting_a_connection(): void
    {
        config(['services.local_pc' => [
            'dsn' => '', 'username' => null, 'password' => null, 'timeout' => 15,
        ]]);

        $this->expectException(BhwisConnectionException::class);
        $this->expectExceptionMessage('Missing: dsn, username, password');

        (new LocalPcDatabase)->connection();
    }
}
