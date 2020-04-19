<?php
require __DIR__ . "/../vendor/autoload.php";
require "./ExampleJob.php";
$config = require "./config.php";

$queue = \Bobby\Queue\QueueFacade::make($config)->getQueue();
$manager = new \Bobby\Queue\ConsumerProcessesManager($queue, [
    'consumer' => [
        'daemonize' => false,
        'process_num' => 3
    ]
]);

$manager->run();