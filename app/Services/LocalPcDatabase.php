<?php

namespace App\Services;

use App\Exceptions\BhwisConnectionException;
use PDO;
use PDOException;
use Throwable;

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
            throw new BhwisConnectionException('BHWIS database configuration is incomplete. Missing: '.implode(', ', $missing).'.');
        }

        try {
            $this->pdo = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false,
                ]
            );
            try {
                $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, max(1, (int) config('services.local_pc.timeout', 15)));
            } catch (PDOException) {
                // PDO ODBC drivers differ; keep the connection when this optional attribute is unsupported.
            }

            return $this->pdo;
        } catch (PDOException $exception) {
            throw new BhwisConnectionException('Unable to connect to BHWIS through the configured ODBC data source.', previous: $exception);
        }
    }

    /**
     * Execute a SELECT query and return all rows.
     */
    public function select(string $query, array $bindings = []): array
    {
        try {
            $statement = $this->connection()->prepare($query);
            $statement->execute($bindings);

            return $statement->fetchAll();
        } catch (BhwisConnectionException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new BhwisConnectionException('The BHWIS query could not be completed.', previous: $exception);
        }
    }

    public function first(string $query, array $bindings = []): ?array
    {
        $rows = $this->select($query, $bindings);

        return $rows[0] ?? null;
    }

    public function scalar(string $query, array $bindings = []): mixed
    {
        try {
            $statement = $this->connection()->prepare($query);
            $statement->execute($bindings);

            return $statement->fetchColumn();
        } catch (BhwisConnectionException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new BhwisConnectionException('The BHWIS query could not be completed.', previous: $exception);
        }
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query.
     */
    public function statement(string $query, array $bindings = []): bool
    {
        try {
            $statement = $this->connection()->prepare($query);

            return $statement->execute($bindings);
        } catch (BhwisConnectionException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new BhwisConnectionException('The BHWIS statement could not be completed.', previous: $exception);
        }
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($this);
            $pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function isAvailable(): bool
    {
        try {
            $this->testConnection();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array{server_time: mixed, database_name: mixed} */
    public function testConnection(): array
    {
        $row = $this->first('SELECT GETDATE() AS server_time, DB_NAME() AS database_name');

        if ($row === null) {
            throw new BhwisConnectionException('BHWIS returned no connection diagnostic data.');
        }

        return $row;
    }
}
