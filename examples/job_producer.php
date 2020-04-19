<?php
require __DIR__ ."/../vendor/autoload.php";
require "./ExampleJob.php";
$config = require "./config.php";

\Bobby\Queue\QueueFacade::make($config)->push(new ExampleJob());