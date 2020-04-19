<?php
require __DIR__ . "/../vendor/autoload.php";

$config = [
    'connection' => []
];

$queue = new \Bobby\Queue\Drivers\RedisQueue\RedisQueue("default", $config);

$queue->push(function () {
    echo "Hello world 2!\n";
});

//while (1) {
//    $id = $queue->pop();
//    $job = $queue->get($id);
//    if (is_null($job)) {
//        break;
//    }
//    $job();
//
//    $queue->fail($id);
//    $queue->release($id);
//}
