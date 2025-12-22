<?php
    include 'helper.php';
    header('content-type:application/json;charset=utf-8');

    echo json_encode(
        [
            "status" => "success",
            "message" => "Welcome to grid web service: authenticate via /api/auth/login.php",
        ]
    );