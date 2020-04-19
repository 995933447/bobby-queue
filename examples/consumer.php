<?php
require __DIR__ . "/../vendor/autoload.php";

$queue = new \Bobby\Queue\Drivers\RedisQueue("default", []);
$manager = new \Bobby\Queue\ConsumerProcessesManager($queue, [
    'consumer' => [
        'daemonize' => false,
        'process_num' => 3
    ]
]);

$manager->run();