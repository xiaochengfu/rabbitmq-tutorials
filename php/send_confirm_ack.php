<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

try {
    $connection = new AMQPStreamConnection('59.110.213.203', 5672, 'guest', 'guest');
    $channel = $connection->channel();

    $channel->set_ack_handler(
    //注意:如果发送到exchange成功，但是没有匹配的队列（比如说取消了绑定），ack 返回值为还是 true,并不会走到nack的回调中
        function (AMQPMessage $message) {
            echo "Message send success and  acked with content " . $message->body . PHP_EOL;
        }
    );

    //rabbitmq的内部错误咱无法模拟，无法检验
    $channel->set_nack_handler(
        function (AMQPMessage $message) {
            echo "Message send fail and  nacked with content " . $message->body . PHP_EOL;
        }
    );

    $channel->set_return_listener(
    //当消息没有发送到队列中的时候，将触发此函数
        function ($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $message) {
            echo "replyCode:$replyCode,replyText:$replyText,exchange:$exchange,routingKey:$routingKey,message:" . $message->body . PHP_EOL;
        }
    );

    $channel->confirm_select();

    $channel->exchange_declare("product_confirm", 'direct', false, true, false);
    $channel->queue_declare('ack_q', false, true, false, false);
    $properties = [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    ];
    $channel->queue_bind('ack_q', 'product_confirm', 'ack');

    for ($i = 1; $i <= 30; $i++) {
        $msg = new AMQPMessage('Hello World ' . $i, $properties);
        $channel->basic_publish($msg, 'product_confirm', 'ack', true);
        echo "$i [x] Sent 'Hello World!'\n";
        sleep(1);
    }
    $channel->wait_for_pending_acks_returns();
    $channel->close();
    $connection->close();
} catch (\Exception $e) {
    echo "程序异常：" . $e->getMessage();
}


/**
 * 如何验证消息发送不成功的情况：
 * 启动程序，然后通过rabbitmq的管理端页面，取消product_confirm和ack_q的绑定关系
 */

/**
 * basic_publish的三个参数：
 * exchange：交换机名称
 * routingKey：路由键
 * props：消息属性字段，比如消息头部信息等等
 * body：消息主体部分
 * mandatory：当mandatory标志位设置为true时，如果exchange根据自身类型和消息routingKey无法找到一个合适的queue存储消息，那么broker会调用basic.return方法将消息返还给生产者;
 * 当mandatory设置为false时，出现上述情况broker会直接将消息丢弃;
 * 通俗的讲，mandatory标志告诉broker代理服务器至少将消息route到一个队列中，否则就将消息return给发送者;
 */


