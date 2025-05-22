<?php
session_start();
require_once('includes/db.php');
require_once('includes/access_control.php');

header('Content-Type: application/json');

// Development: show PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check for admin
if (!isset($_SESSION['user_id']) || getUserRole($_SESSION['user_id']) !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($input)) {
        throw new Exception('Invalid input format');
    }

    if (!isset($input['userId'], $input['permission'], $input['granted'])) {
        throw new Exception('Missing required parameters');
    }

    $userId = filter_var($input['userId'], FILTER_VALIDATE_INT);
    $permission = filter_var($input['permission'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $granted = filter_var($input['granted'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    if ($userId === false || $granted === null) {
        throw new Exception('Invalid input values');
    }

    $permMap = [
        'add' => 'add',
        'edit' => 'edit',
        'view' => 'view',
        'delete' => 'delete',
    ];
    if (!isset($permMap[$permission])) {
        throw new Exception('Invalid permission type: ' . $permission);
    }

    $permCol = $permMap[$permission];

    // Check if user exists
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND is_deleted = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result->fetch_assoc()) {
        throw new Exception('User not found');
    }

    // Insert or update permission
    $sql = "INSERT INTO user_permissions (user_id, permission, granted)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE granted = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('SQL Prepare Error: ' . $conn->error);
    }

    $grantedValue = $granted ? 1 : 0;
    $stmt->bind_param("isii", $userId, $permission, $grantedValue, $grantedValue);
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'Permission updated successfully']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if ($conn) {
    $conn->close();
}
