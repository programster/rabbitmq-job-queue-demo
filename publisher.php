<?php

require_once(__DIR__ . '/vendor/autoload.php');

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

$job_id=0;
while (true)
{
    $jobArray = array(
        'id' => $job_id++,
        'task' => 'sleep',
        'sleep_period' => rand(0, 3)
    );
    
    $msg = new \PhpAmqpLib\Message\AMQPMessage(
        json_encode($jobArray, JSON_UNESCAPED_SLASHES),
        array('delivery_mode' => 2) # make message persistent
    );
    
    $channel->basic_publish($msg, '', RABBITMQ_QUEUE_NAME);
    print 'Job created' . PHP_EOL;
    sleep(1);
}


