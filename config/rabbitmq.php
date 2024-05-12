<?php

return [
    'amqp' => [
        'host' => env('rabbitmq.host', 'localhost'),   //连接rabbitmq,此为安装rabbitmq服务器
        'port'=>env('rabbitmq.port','5672'),
        'login'=>env('rabbitmq.user', 'rabbitmq'),
        'password'=>env('rabbitmq.pass', 'rabbitmq'),
    ],
];
