<?php

/*
 * This is a slight variation of the worker.php class that will only work while there are jobs
 * in the queue. As soon as the queue is emptied, this process will gracefully stop.
 */

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

define("RABBITMQ_HOST", "rabbitmq.programster.org");
define("RABBITMQ_PORT", 5672);
define("RABBITMQ_USERNAME", "guest");
define("RABBITMQ_PASSWORD", "guest");
define("RABBITMQ_QUEUE_NAME", "task_queue");

$connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
    RABBITMQ_HOST, 
    RABBITMQ_PORT, 
    RABBITMQ_USERNAME, 
    RABBITMQ_PASSWORD
);


$channel = $connection->channel();

# Create the queue if it doesn't already exist.
$channel->queue_declare(
    $queue = RABBITMQ_QUEUE_NAME,
    $passive = false,
    $durable = true,
    $exclusive = false,
    $auto_delete = false,
    $nowait = false,
    $arguments = null,
    $ticket = null
);


echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function($msg){
    echo " [x] Received ", $msg->body, "\n";
    $job = json_decode($msg->body, $assocForm=true);
    sleep($job['sleep_period']);
    echo " [x] Done", "\n";
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null);

$channel->basic_consume(
    $queue = RABBITMQ_QUEUE_NAME,
    $consumer_tag = '',
    $no_local = false,
    $no_ack = false,
    $exclusive = false,
    $nowait = false,
    $callback
);

// This is the best I could find for a non-blocking wait. Unfortunately one has to have
// a timeout (for now), and simply setting nonBlocking=true on its own appears do to nothing.
// An exception is thrown when the timout is reached, breaking the loop, and you should catch it
// to exit gracefully.
try
{
    while (count($channel->callbacks)) 
    {
        print "running non blocking wait." . PHP_EOL;
        $channel->wait($allowed_methods=null, $nonBlocking=true, $timeout=1);
    }
}
catch (Exception $e)
{
    print "There are no more tasks in the queue." . PHP_EOL;
}


$channel->close();
$connection->close();