<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('59.110.213.203', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('logs', 'fanout', false, false, false);

list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
//list($queue_name, ,) = $channel->queue_declare("", false, false, true, true);

$channel->queue_bind($queue_name, 'logs');

echo " [*] Waiting for logs. To exit press CTRL+C\n";

$callback = function ($msg) {
    echo ' [x] ', $msg->body, "\n";
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();

/**
 * 现象
 * 1. 声明队列时使用的空字符
 */

/**
 * 实践步骤
 * 1. 执行`php receive_logs.php`,通过管理后台查看exchange下是否有队列绑定
 * 2. 查看队列是否能接收消息
 * 3. 将队列的参数`auto_delete`改为`true`
 *
 */

/**
 * 结论
 * 1. 声明队列的时候，使用空字符，可以生成一个名称为`amqp.gen-xxx`形式的临时队列
 * 2. 对于临时队列的使用场景为：
 *    a. 消费者启动时创建的，为该消费者独有，其他交换机和消费者无法使用,所以参数`exclusive`设置为`true`
 *    b. 消费者关闭后，可以将`auto_delete`参数设置为`true`，自动将队列删除
 */

?>
