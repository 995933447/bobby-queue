<?php
require __DIR__ . "/../vendor/autoload.php";
$config = require "./config.php";

\Bobby\Queue\QueueFacade::make($config)->push(function () {
    echo "Hello world 2!\n";
}, 5);
