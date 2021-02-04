<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('59.110.213.203', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('durable', false, true, false, false,false);

//$properties = [
//    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
//];
//$msg = new AMQPMessage('消息不要丢!', $properties);

$msg = new AMQPMessage('消息不要丢!');

for ($i = 1; $i <= 50; $i++) {
    $channel->basic_publish($msg, '', 'durable');
    echo "$i [x] Sent '消息不要丢!'\n";
    sleep(1);
}

$channel->close();
$connection->close();


/**
 * 现象：
 * 1. 将queue的durable参数设为false时，服务重启，队列会丢失
 * 2. 只有将queue的durable参数设为true,message的delivery_mode为2时，服务重启，消息和队列才不会丢失
 */

/**
 * 实践：
 * 1. 将queue的durable参数设为false，循环发送50个消息，通过管理后台查看消息数，重启服务，再次查看消息数 =>丢失
 * 2. 将queue的durable参数设为true，message的delivery_mode为1, 循环发送50个消息，通过管理后台查看消息数，重启服务，再次查看消息数 => 丢失
 * 3. 将queue的durable参数设为true，message的delivery_mode为2, 循环发送50个消息，通过管理后台查看消息数，重启服务，再次查看消息数 => 保留
 *
 * 注：通过docker来重启rabbitmq服务
 */

/**
 * 总结：
 * 1. 消息的持久化（写到磁盘），需要将消息和存放消息的队列同时设为持久化才有效
 * 2. 只有队列才有存储消息的能力
 */