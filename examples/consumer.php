<?php
require __DIR__ . "/../vendor/autoload.php";

$queue = new \Bobby\Queue\Drivers\RedisQueue\RedisQueue("default", []);
$manager = new \Bobby\Queue\ConsumerProcessesManager($queue, []);
$manager->run();