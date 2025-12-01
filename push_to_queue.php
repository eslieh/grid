<?php
require alias('@/vendor/autoload.php');
use Dotenv\Dotenv;
use Celery\Celery;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$connectionString = $_ENV['REDIS_CONNECTION_STRING'] ?? 'redis://localhost:6379';

$redis = new Predis\Client($connectionString);

function pushToQueue($task_type, $task_id, $payload) {
    global $redis;
    try {
        $task_id_uuid = uniqid();
        
        // Celery protocol v2 message format
        $message = [
            'body' => base64_encode(json_encode([
                [],  // args array (empty since we're using kwargs)
                [    // kwargs object (must be an object/dict, not array)
                    'data' => [
                        'task_type' => $task_type,
                        'task_id' => $task_id,
                        'payload' => $payload,
                    ]
                ],
                [
                    'callbacks' => null,
                    'errbacks' => null,
                    'chain' => null,
                    'chord' => null
                ]
            ])),
            'content-encoding' => 'utf-8',
            'content-type' => 'application/json',
            'headers' => [
                'lang' => 'php',
                'task' => 'task_queue',
                'id' => $task_id_uuid,
                'root_id' => $task_id_uuid,
                'parent_id' => null,
                'group' => null,
            ],
            'properties' => [
                'correlation_id' => $task_id_uuid,
                'reply_to' => null,
                'delivery_mode' => 2,
                'delivery_info' => [
                    'exchange' => '',
                    'routing_key' => 'celery'
                ],
                'priority' => 0,
                'body_encoding' => 'base64',
                'delivery_tag' => $task_id_uuid
            ]
        ];
        
        $redis->rpush('celery', json_encode($message));
        return true;
    } catch (Exception $e) {
        echo "Failed to push job to queue: " . $e->getMessage();
        return false;
    }
}