<?php
session_start();

require_once 'controllers/QueryController.php';

try {
    $controller = new QueryController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->query();
    } else {
        $controller->index();
    }
} catch (Exception $e) {
    error_log("QueryController Error: " . $e->getMessage());
    die("系统错误，请稍后再试");
}
