# Ryfty Grid Client Queue Producer

This PHP project acts as the client-side producer for the Grid Worker system. Its job is to package a task request in a Celery-compatible format and push it into Redis so the Python worker can process it asynchronously.

## Purpose

The queue producer is centered around [push_to_queue.php](/Users/app/Development/htdocs/ryfty-grid/push_to_queue.php:1).

It does not process files itself. Instead, it:

- accepts a task type, task ID, and payload
- builds a Celery protocol v2 message
- pushes that message to Redis queue `celery`
- lets the Python worker pick it up and do the actual work

## Main File

- [push_to_queue.php](/Users/app/Development/htdocs/ryfty-grid/push_to_queue.php:1): loads environment variables, connects to Redis, and exposes `pushToQueue(...)`

## Dependencies

Defined in [composer.json](/Users/app/Development/htdocs/ryfty-grid/composer.json:1):

- `vlucas/phpdotenv`: loads environment variables from `.env`
- `predis/predis`: Redis client used to push queue messages
- `wangjichao/swoole-celery`: Celery-related PHP library available in the project

## How It Works

### 1. Load configuration

When [push_to_queue.php](/Users/app/Development/htdocs/ryfty-grid/push_to_queue.php:1) is loaded, it:

- loads Composer autoloading
- loads `.env` values using `phpdotenv`
- reads `REDIS_CONNECTION_STRING`
- creates a shared `Predis\Client`

Relevant lines:

- env loading at [push_to_queue.php](/Users/app/Development/htdocs/ryfty-grid/push_to_queue.php:6)
- Redis connection at [push_to_queue.php](/Users/app/Development/htdocs/ryfty-grid/push_to_queue.php:9)

### 2. Build a queue message

The function `pushToQueue($task_type, $task_id, $payload)` is defined at [push_to_queue.php](/Users/app/Development/htdocs/ryfty-grid/push_to_queue.php:13).

It creates:

- a unique internal message ID using `uniqid()`
- a Celery message body containing:
  - empty positional args
  - kwargs with a `data` object
  - Celery metadata for callbacks, errbacks, chain, and chord
- Celery headers including the target task name `task_queue`
- delivery properties describing the Redis routing key

The worker-facing payload is packaged like this:

```json
{
  "data": {
    "task_type": "image.resize",
    "task_id": "uuid-123",
    "payload": {
      "original_url": "https://example.com/image.png",
      "parameters": {
        "width": 800,
        "height": 600,
        "keep_aspect_ratio": true,
        "output_format": "jpg"
      }
    }
  }
}
```

### 3. Push to Redis

After building the message, the function serializes it to JSON and pushes it into the Redis list named `celery` using:

```php
$redis->rpush('celery', json_encode($message));
```

That happens at [push_to_queue.php](/Users/app/Development/htdocs/ryfty-grid/push_to_queue.php:60).

### 4. Return a local enqueue response

The function returns a JSON string indicating whether the enqueue step succeeded.

Success response:

```json
{
  "success": true,
  "message": "task queued successfully"
}
```

Failure response:

```json
{
  "success": false,
  "message": "error details"
}
```

## End-to-End Flow

```text
PHP app
    |
    | call `pushToQueue(task_type, task_id, payload)`
    v
`push_to_queue.php`
    |
    | build Celery protocol v2 message
    v
Redis list `celery`
    |
    v
Python Celery worker
    |
    v
`task_queue` dispatcher
    |
    +--> `image.remove_bg`
    +--> `image.resize`
    +--> `images.to_pdf`
    |
    v
Cloudinary + callback API
```

## Expected Task Contract

The client should send three logical pieces of information:

- `task_type`: the worker task to run
- `task_id`: your application-level tracking ID
- `payload`: task-specific input data

Example for background removal:

```php
$taskType = 'image.remove_bg';
$taskId = 'task-001';
$payload = [
    'original_url' => 'https://example.com/photo.png',
    'parameters' => [
        'output_format' => 'png',
    ],
];

$result = pushToQueue($taskType, $taskId, $payload);
```

Example for image resize:

```php
$taskType = 'image.resize';
$taskId = 'task-002';
$payload = [
    'original_url' => 'https://example.com/photo.png',
    'parameters' => [
        'width' => 800,
        'height' => 600,
        'keep_aspect_ratio' => true,
        'output_format' => 'jpg',
    ],
];

$result = pushToQueue($taskType, $taskId, $payload);
```

Example for PDF generation:

```php
$taskType = 'images.to_pdf';
$taskId = 'task-003';
$payload = [
    'original_url' => [
        'https://example.com/a.png',
        'https://example.com/b.jpg',
    ],
    'parameters' => [
        'output_file_name' => 'merged.pdf',
    ],
];

$result = pushToQueue($taskType, $taskId, $payload);
```

## Environment Variables

This client expects:

- `REDIS_CONNECTION_STRING`: Redis connection string used by both the client and worker

The Python worker side also depends on Cloudinary and callback API settings, but those are configured in the worker project, not here.

## Local Setup

Install dependencies:

```bash
composer install
```

Make sure your `.env` file includes a valid Redis connection string:

```env
REDIS_CONNECTION_STRING=redis://localhost:6379
```

## Important Notes

- This client pushes directly to Redis rather than calling a Python API.
- The Redis queue name is hardcoded as `celery`.
- The target Celery task is hardcoded as `task_queue`.
- This file returns JSON strings, not PHP arrays.
- The producer only confirms that the task was queued. It does not confirm that processing succeeded.
- Final processing results are posted back later by the Python worker to its configured callback endpoint.

## Current Caveats

- [push_to_queue.php](/Users/app/Development/htdocs/ryfty-grid/push_to_queue.php:2) uses `require alias('@/vendor/autoload.php');`, which assumes a custom `alias()` helper is available in the runtime.
- The file imports `use Celery\Celery;` but currently pushes to Redis manually with Predis instead of using that class directly.
- The producer wraps the job under `data`, so the Python worker and this client need to stay in sync on that message shape.
