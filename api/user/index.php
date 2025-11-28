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
$user_query = mysqli_query($conn, "SELECT id, name, email, profile_url FROM user WHERE id='{$decodedToken['user_id']}'");
if (mysqli_num_rows($user_query) === 0) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized access. User not found."
    ]);
    exit();
}  

$user_data = mysqli_fetch_assoc($user_query);


echo json_encode([
    "status" => "success",
    "message" => "Authorized access.",
    "user" => [
        "id" => $decodedToken['user_id'],
        "email" =>$user_data['name'],
        "profile_url" => $user_data['profile_url'],
    ]
]);