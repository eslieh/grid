<?php
header('Content-Type: application/json; charset=utf-8');

include '../../helper.php';
require alias('@/config/conn.php');
require alias('@/authorization.php');

// Get JSON input
$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON payload."
    ]);
    exit();
}

// Sanitize input
$email = mysqli_real_escape_string($conn, trim($data->email ?? ''));
$password = mysqli_real_escape_string($conn, trim($data->password ?? ''));

// Validate required fields
if (!$email || !$password) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Email and password are required."
    ]);
    exit();
}

// Fetch user
$query = mysqli_query($conn, "SELECT id, password, name, profile_url FROM user WHERE email='$email'");
if (mysqli_num_rows($query) === 0) {
    http_status_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password."
    ]);
    exit();
}  
$user = mysqli_fetch_assoc($query);
// Verify password
if (!password_verify($password, $user['password'])) {
    http_status_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Incorrect password."
    ]);
    exit();
}
$accessToken = createAccessToken($user['id'], $email);     
echo json_encode([
    "status" => "success",
    "message" => "Login successful.",
    "access_token" => $accessToken,
    "user" => [
        "id" => $user['id'],
        "name" => $user['name'],
        "email" => $email,
        "profile_url" => $user['profile_url']
    ]
]);