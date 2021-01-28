<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('59.110.213.203', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('hello', false, false, false, false);

$msg = new AMQPMessage('Hello World!');
$channel->basic_publish($msg, '', 'hello');

echo " [x] Sent 'Hello World!'\n";

$channel->close();
$connection->close();


/**
 * 现象：
 * 1. exchange为空
 * 2. 队列hello没有绑定exchange
 * 3. routing_key为hello,去掉routing_key或改名后，队列收不到消息
 *
 * 结论：
 * 1. exchange虽然为空字符，但是使用了rabbitmq内置的(AMQP default)
 * 2. 队列虽然没有用queue_bind()方式显示的绑定exchange,但内部其实是与内置的(AMQP default)交换机进行了绑定
 * 3. routing_key去掉或改名后收不到，是因为绑定到默认exchange时，routing_key必须和queue名称一致才有效
 *
 * 理论佐证：
 * rabbitmq manage 管理后台，默认的exchange详情页，Bindings这一栏内有说明
 * Default exchange
 * The default exchange is implicitly bound to every queue, with a routing key equal to the queue name. It is not possible to explicitly bind to, or unbind from the default exchange. It also cannot be deleted.
 *
 * 延伸知识：
 * 1. 默认的exchange无法显示的声明绑定队列或解绑队列，也无法删除该exchange
 * 2. 消息是发送给exchange的，由exchange通过路由routing_key分配到队列中
 * 3. 当exchange的type为direct时，且不是默认的exchange时，空字符下的routing_key也会当做一种匹配规则，绑定到队列上，此时实现的跟fanout效果类似
 */