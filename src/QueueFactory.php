<?php
namespace Bobby\Queue;

class QueueFactory
{
    public static function make(string $channel, array $options)
    {
        if (isset($options['driver']) || class_exists($options['driver'])) {
            throw new \InvalidArgumentException(sprintf("Queue driver class:%s not found.", $options['driver']?? ''));
        }

        $driver = new $options['driver']($channel, $options);
        if (!$driver instanceof QueueContract) {
            throw new \InvalidArgumentException("Queue driver {$options['driver']} not instanceof " . QueueContract::class);
        }

        return $driver;
    }
}