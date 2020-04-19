<?php
namespace Bobby\Queue\Utils;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ConsoleLogger
{
    public function __construct(array $options)
    {
        $name = sprintf('bobby-queue-%s', $options['channel'] ?? 'default');
        $this->executor = new Logger($name);
        $stdoutHandler = new StreamHandler(STDOUT, $options['level'] ?? Logger::INFO);
        $this->executor->setHandlers([$stdoutHandler]);
    }

    public function __call($name, $arguments)
    {
        return $this->executor->$name(...$arguments);
    }
}