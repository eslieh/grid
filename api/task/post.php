<?php
header('Content-Type: application/json; charset=utf-8');

include '../../helper.php';
require alias('@/config/conn.php');
require alias('@/authorization.php');
require alias('@/push_to_queue.php');

$get_authorization = getallheaders()['Authorization'] ?? '';
$token = str_replace('Bearer ', '', $get_authorization);
$decodedToken = verifyAccessToken($token);

if (!$decodedToken) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized access. Invalid or missing token."
    ]);
    exit();
}
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $data = json_decode(file_get_contents("php://input"));

    if (!$data || !isset($data->task_type) || !isset($data->payload)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "task_type and payload are required."
        ]);
        exit();
    }

    $task_type = mysqli_real_escape_string($conn, trim($data->task_type));
    $payloadJson = json_encode($data->payload);
    $payload = mysqli_real_escape_string($conn, $payloadJson);
    $status = "initiated";

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert task
        $insertQuery = mysqli_query(
            $conn, 
            "INSERT INTO tasks (user_id, task_type, payload, status) 
             VALUES ('{$decodedToken['user_id']}', '$task_type', '$payload', '$status')"
        );

        if (!$insertQuery) {
            throw new Exception("MySQL insert failed: " . mysqli_error($conn));
        }

        $task_id = mysqli_insert_id($conn);

        // Push to queue
        $responce =  pushToQueue(
            $task_type,
            $task_id,
            $payloadJson
        );
        if (!$responce['success']) {
            http_response_code(500);
            $errror_message = $responce['message'];
            throw new Exception(`Failed to push task to queue: $error_message`);
            die();
        }

        // Commit transaction
        mysqli_commit($conn);

        echo json_encode([
            "status" => "success",
            "message" => "Task created successfully.",
            "task_id" => $task_id
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);

        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Failed to create task.",
            "error" => $e->getMessage()
        ]);

        // Log the error
        error_log("Task creation failed: " . $e->getMessage());
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed."
    ]);
    exit();
}
?>