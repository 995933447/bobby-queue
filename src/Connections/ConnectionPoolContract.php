<?php
namespace Bobby\Queue\Connections;

abstract class ConnectionPoolContract
{
    const DEFAULT_POOL_SIZE = 50;

    protected $pool;

    protected $options;

    protected $connectionsSize;

    public function __construct(array $options)
    {
        $this->pool = new \SplQueue();
        $this->options = $options;
    }

    public function pop()
    {
        if ($this->pool->count() > 0) {
            $connection = $this->pool->pop();

            if ($this->connectionIsAlive($connection)) {
                $connection = $this->createConnection();
            }
        } else {
            $maxPoolSize = $this->options['connection']['pool']['size']?? static::DEFAULT_POOL_SIZE;

            if ($this->connectionsSize < $maxPoolSize) {
                $connection = $this->createConnection();
                $this->connectionsSize++;
            } else {
                throw new \RuntimeException('Connection pool has not available connection.');
            }
        }

        return $connection;
    }

    public function recycle($connection)
    {
        $this->pool->push($connection);
    }

    public function flush()
    {
        while (!$this->pool->isEmpty()) {
            $this->closeConnection($this->pool->pop());
        }
    }

    abstract public function createConnection();

    abstract public function closeConnection($connection);

    abstract public function connectionIsAlive($connection): bool;
}