<?php
return [
    'default' => 'redis',

    'connections' => [
        'redis' => [
            'driver' => \Bobby\Queue\Drivers\RedisQueue::class,
            'connection' => [

            ]
        ]
    ]
];