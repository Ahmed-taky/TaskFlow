<?php

namespace App\Database;

use PDO;

class Database
{
    private null|PDO $connection = NULL;

    function getConnection(): PDO
    {
        if ($this->connection === NULL) {
            $this->connection = new PDO("pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}", "{$_ENV['DB_USER']}", "{$_ENV['DB_PASSWORD']}", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return $this->connection;
    }
}
?>