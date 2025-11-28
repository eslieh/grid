<?php
header('Content-Type: application/json; charset=utf-8');

include '../../helper.php';
require alias('@/config/conn.php');
require alias('@/authorization.php');

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
$task_id = $_GET['task_id'] ?? null;
$task_id = mysqli_real_escape_string($conn, $task_id);

$sql = "
    SELECT 
        id,
        task_type,
        payload,
        status,
        timestamp
    FROM tasks 
    WHERE user_id = '{$decodedToken['user_id']}'
";

if ($task_id) {
    $sql .= " AND id = '$task_id'";
}



$task_query = mysqli_query($conn, $sql);
$tasks = [];

// if ()

if ($task_id){
    if (mysqli_num_rows($task_query) === 0) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Task not found."
        ]);
        exit();
    }  
    $row = mysqli_fetch_assoc($task_query);
    if (!empty($row['payload'])) {
        $row['payload'] = json_decode($row['payload'], true);
    }
    echo json_encode([
        "status" => "success",
        "message" => "Task retrieved successfully.",
        "task" => $row
    ]);
    exit();
}
while ($row = mysqli_fetch_assoc($task_query)) {
    if (!empty($row['payload'])) {
        $row['payload'] = json_decode($row['payload'], true);
    }
    $tasks[] = $row;
}

echo json_encode([
    "status" => "success",
    "message" => "Tasks retrieved successfully.",
    "tasks" => $tasks
]);
?>