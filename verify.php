<?php
session_start();

require_once 'controllers/VerifyController.php';
require_once 'utils/TimeHelper.php';

try {
    $controller = new VerifyController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->verify();
    } else {
        $controller->index();
    }
} catch (Exception $e) {
    error_log("VerifyController Error: " . $e->getMessage());
    die("系统错误，请稍后再试");
}
