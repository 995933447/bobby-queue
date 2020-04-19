<?php
require __DIR__ ."/../vendor/autoload.php";
require "./ExampleJob.php";

$queue = new \Bobby\Queue\Drivers\RedisQueue('default', []);
$queue->push(new ExampleJob());