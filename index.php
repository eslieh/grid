<?php
    include 'helper.php';
    header('content-type:application/json;charset=utf-8');

    echo json_encode(
        [
            "status" => "success",
            "message" => "Hello world!",
        ]
    );