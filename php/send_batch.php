<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('59.110.213.203', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('hello', false, false, false, false);

for ($i = 1; $i <= 10; $i++) {
    $msg = new AMQPMessage('Hello World!');
    $channel->basic_publish($msg, '', 'hello');

    echo "$i [x] Sent 'Hello World!'\n";
}


$channel->close();
$connection->close();
