<?php
session_start();

require_once 'controllers/HomeController.php';

try {
    $controller = new HomeController();
    $controller->index();
} catch (Exception $e) {
    error_log("HomeController Error: " . $e->getMessage());
    die("系统错误，请稍后再试");
}
