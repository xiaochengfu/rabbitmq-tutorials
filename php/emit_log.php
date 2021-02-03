<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('59.110.213.203', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('logs', 'fanout', false, false, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "info: Hello World!";
}
$msg = new AMQPMessage($data);

$channel->basic_publish($msg, 'logs');

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();


/**
 * 现象：
 * 1. 声明了exchange,消息直接发送到了exchange中
 * 2. 没有声明队列，也没有绑定队列
 */

/**
 * 实践：
 * 1. 执行`php emit_log.php`,通过管理后台查看logs交换机是否有和队列的绑定关系
 * 2. 查看是否有队列接收到了发送的消息
 */

/**
 * 结论：
 * 1. 生产者总是将消息发给exchange,甚至不用关心exchange有没有绑定的队列，消息到底发到了哪个队列
 * 官网文档地址：[https://www.rabbitmq.com/tutorials/tutorial-three-python.html](https://www.rabbitmq.com/tutorials/tutorial-three-python.html)
 * The core idea in the messaging model in RabbitMQ is that the producer never sends any messages directly to a queue. Actually, quite often the producer doesn't even know if a message will be delivered to any queue at all.
 * 2. 消息在没有到达队列内时，消息丢失了，说明exchange是不具有存储能力的
 */
?>

