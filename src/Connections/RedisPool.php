<?php
namespace Bobby\Queue\Connections;

use Bobby\Queue\ConnectionPoolContract;
use Predis\Client;

class RedisPool extends ConnectionPoolContract
{
    public function createConnection()
    {
        return new Client($this->options?: []);
    }

    public function closeConnection($connection)
    {
        if ($connection instanceof Client) {
            $connection->getConnection()->disconnect();
        }
    }

    public function connectionIsAlive($connection): bool
    {
        if (!$connection instanceof Client) {
            return false;
        }

        try {
            return $connection->isConnected() && $connection->ping();
        } catch (\Throwable $e) {
            return false;
        }
    }
}