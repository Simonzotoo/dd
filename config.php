<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');      // Change this
define('DB_PASS', 'your_db_password');      // Change this
define('DB_NAME', 'ddig_db');

// Email configuration
define('ADMIN_EMAIL', 'info@ddig-group.com');
define('SITE_NAME', 'DDIG Ghana');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        // Log error and return JSON response
        error_log("Database connection failed: " . $conn->connect_error);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection error'
        ]);
        exit();
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Send email notification
function sendEmailNotification($to, $subject, $body, $from = null) {
    if ($from === null) {
        $from = ADMIN_EMAIL;
    }
    
    $headers = "From: " . SITE_NAME . " <" . $from . ">\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $body, $headers);
}

// JSON response helper
function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}
?>