<?php
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
            "status" => 404,
            "message" => "Look like you're lost, check the documentation: https://grid.ryfty.net/docs",
    ]);