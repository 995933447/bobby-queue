<?php
namespace Bobby\Queue;

class QueueFacade
{
    const DEFAULT_CHANNEL = 'default';

    protected $config;

    protected $channel;

    protected $connectionOptions;

    protected $queue;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function make(array $config): QueueFacade
    {
        return new static($config);
    }

    public function channel(string $channel): QueueFacade
    {
        if ($this->channel != $channel) {
            $this->queue = null;
        }

        $this->channel = $channel;

        return $this;
    }

    public function connection(string $connection): QueueFacade
    {
        if (!isset($this->options[$connection])) {
            throw new \InvalidArgumentException("Inputted config has not $connection options.");
        }

        if ($this->connectionOptions !== $this->config['connection']) {
            $this->queue = null;
        }

        $this->connectionOptions = $this->config[$connection];

        return $this;
    }

    public function getQueue(): QueueContract
    {
        if (!is_null($this->queue)) {
            if (is_null($this->connectionOptions)) {
                if (isset($this->config['default'])) {
                    $this->connection($this->config['default']);
                } else {
                    throw new \InvalidArgumentException("Inputted config has not default queue connection name option.");
                }
            }

            $this->queue = QueueFactory::make($this->channel?: static::DEFAULT_CHANNEL, $this->connectionOptions);
        }

        return $this->queue;
    }

    public function __call($name, $arguments)
    {
        return $this->getQueue()->$name(...$arguments);
    }
}