<?php

namespace App\Services;

use PDO;
use PDOException;
use RuntimeException;

class LocalPcDatabase
{
    private ?PDO $pdo = null;

    /**
     * Return a reusable PDO connection to the local SQL Server.
     */
    public function connection(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = config('services.local_pc.dsn');
        $username = config('services.local_pc.username');
        $password = config('services.local_pc.password');

        $missing = [];

        if (blank($dsn)) {
            $missing[] = 'dsn';
        }

        if (blank($username)) {
            $missing[] = 'username';
        }

        if ($password === null || $password === '') {
            $missing[] = 'password';
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Local PC database configuration is incomplete. Missing: '
                . implode(', ', $missing)
            );
        }

        try {
            $this->pdo = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_TIMEOUT => 10,
                    PDO::ATTR_PERSISTENT => false,
                ]
            );

            return $this->pdo;
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Unable to connect to the local SQL Server: '
                . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    /**
     * Execute a SELECT query and return all rows.
     */
    public function select(string $query, array $bindings = []): array
    {
        $statement = $this->connection()->prepare($query);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query.
     */
    public function statement(string $query, array $bindings = []): bool
    {
        $statement = $this->connection()->prepare($query);

        return $statement->execute($bindings);
    }
}
