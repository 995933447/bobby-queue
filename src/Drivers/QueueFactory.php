<?php
namespace Bobby\Queue\Drivers;

class QueueFactory
{
    public static function make(string $channel, array $options): QueueContract
    {
        $driverClass = $options['driver']?? '';

        $driver = null;
        if (class_exists($driverClass)) {
            $driver = new $driverClass($channel, $options);
        } else {
            throw new \RuntimeException("Driver class $driverClass not found.");
        }

        if (!$driver instanceof QueueContract) {
            throw new \RuntimeException("class $driverClass is not instanceof " . QueueContract::class . ".");
        }

        return $driver;
    }
}