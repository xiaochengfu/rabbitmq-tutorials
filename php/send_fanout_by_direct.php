<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('59.110.213.203', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('FM_DIRECT', 'direct', false, false, false, false);
for ($i = 1; $i <= 5; $i++) {
    $channel->queue_declare('FM_STUDY_' . $i, false, false, false, false);
    $channel->queue_bind('FM_STUDY_' . $i, 'FM_DIRECT');
}

$msg = new AMQPMessage('好好听讲!');
$channel->basic_publish($msg, 'FM_DIRECT');

echo " [x] Sent '好好听讲!'\n";

$channel->close();
$connection->close();

/**
 * 用direct来实现广播fanout的功能
 * 当exchange的type为direct时，且不是默认的exchange时，空字符下的routing_key也会当做一种匹配规则，绑定到队列上，此时实现的跟fanout效果类似
 *
 * 提示：虽然都能实现广播的功能，但广播的场景，还是推荐使用fanout，因为效率会更高，direct还是会解析空的routing_key，而fanout会自动忽略routing_key，直接往绑定的队列里扔消息
 */