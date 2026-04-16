<?php
    define('ROOT', __DIR__);
    function alias($path) {
        // Check if path starts with '@/' 
        if (substr($path, 0, 2) === '@/') {
            return ROOT . substr($path, 1); // Replace '@' with ROOT
        }
        return $path;
    }
