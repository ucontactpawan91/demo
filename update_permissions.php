<?php
session_start();
require_once('includes/access_control.php');
require_once('includes/db.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || getUserRole($_SESSION['user_id']) !== 'ADMIN') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['userId']) || !isset($data['permission']) || !isset($data['granted'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$userId = (int)$data['userId'];
$permission = mysqli_real_escape_string($conn, $data['permission']);
$granted = (bool)$data['granted'];

// Check if user exists and is not an admin (admins have all permissions by default)
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

if ($user['role'] === 'ADMIN') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Cannot modify admin permissions']);
    exit;
}

// Update or insert permission
$stmt = $conn->prepare("
    INSERT INTO user_permissions (user_id, permission, granted)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE granted = ?
");

$stmt->bind_param("isii", $userId, $permission, $granted, $granted);
$success = $stmt->execute();

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
