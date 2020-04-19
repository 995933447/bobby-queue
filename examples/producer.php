<?php
require __DIR__ . "/../vendor/autoload.php";
$config = require "./config.php";

\Bobby\Queue\QueueFacade::make($config)->push(function () {
    echo "Hello world 2!\n";
}, 5);

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
