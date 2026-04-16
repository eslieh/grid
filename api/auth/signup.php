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
$fullname = mysqli_real_escape_string($conn, trim($data->fullname ?? ''));
$email = mysqli_real_escape_string($conn, trim($data->email ?? ''));
$password = mysqli_real_escape_string($conn, trim($data->password ?? ''));
$profile_url = mysqli_real_escape_string($conn, trim($data->profile_url ?? ''));

// Validate required fields
if (!$fullname || !$email || !$password) {
    echo json_encode([
        "status" => "error",
        "message" => "Fullname, email, and password are required."
    ]);
    exit();
}

// Check if email already exists
$query = mysqli_query($conn, "SELECT email FROM user WHERE email='$email'");
if (mysqli_num_rows($query) > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already exists."
    ]);
    exit();
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$insertQuery = mysqli_query($conn, "INSERT INTO user (`name`, email, `password`, profile_url) VALUES ('$fullname', '$email', '$hashedPassword', '$profile_url')");

if ($insertQuery) {
    $accessToken = createAccessToken(mysqli_insert_id($conn), $email);
    echo json_encode([
        "status" => "success",
        "message" => "User registered successfully.",
        "user_id" => mysqli_insert_id($conn),
        "access_token" => $accessToken
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Registration failed. Please try again."
    ]);
}
?>
