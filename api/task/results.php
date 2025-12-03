<?php
header('Content-Type: application/json; charset=utf-8');

include '../../helper.php';
require alias('@/config/conn.php');


if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $data = json_decode(file_get_contents("php://input"));

    if (!$data || !isset($data->status) || !isset($data->result) || !isset($data->task_id)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "status, result and task_id are required."
        ]);
        exit();
    }

    $task_id = mysqli_real_escape_string($conn, trim($data->task_id));
    $result = json_encode($data->result);
    $status = mysqli_real_escape_string($conn, trim($data->status));

    #save the results to the db

    $query = mysqli_query($conn, "SELECT id FROM tasks WHERE id = '$task_id'");
    if(mysqli_num_rows($query) > 0){
        
        $update_query = mysqli_query($conn, "UPDATE tasks SET results = '$result', `status` = '$status'  WHERE id = '$task_id'");
        if ($update_query){
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "db excecuted successfully and status message"
            ]);
        }else{
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Error saving to db"
            ]);
        }
    }
}