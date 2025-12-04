<?php
require alias('@/vendor/autoload.php');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;


$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$jwtSecret = $_ENV['JWT_SECRET_KEY'] ?? 'default-secret';
$secret = $jwtSecret;



function createAccessToken($userId, $email) {
    global $secret;
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = [
        'user_id' => $userId,
        'email' => $email,
        'iat' => time(),
        'exp' => time() + (7 * 24 * 60 * 60) // Token valid for 1 week
    ];
    $jwt = JWT::encode($payload, $secret, 'HS256');
    return $jwt;
}


function verifyAccessToken($token) {
    global $secret;
    try {
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        return (array)$decoded;
    } catch (Exception $e) {
        return null;
    }
}