<?php
function getUserRole($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    return $user['role'] ?? 'USER'; // Default to USER if role is not set
}

function canViewUser($logged_user_id, $target_user_id) {
    $role = getUserRole($logged_user_id);
    
    switch($role) {
        case 'ADMIN':
        case 'HR':
            return true;
        case 'TEAM_LEADER':
            return isTeamMember($target_user_id, $logged_user_id);
        case 'USER':
            return $logged_user_id === $target_user_id;
        default:
            return false;
    }
}

function canEditUser($logged_user_id, $target_user_id) {
    $role = getUserRole($logged_user_id);
    
    switch($role) {
        case 'ADMIN':
        case 'HR':
            return true;
        case 'TEAM_LEADER':
            return isTeamMember($target_user_id, $logged_user_id);
        case 'USER':
            return $logged_user_id === $target_user_id;
        default:
            return false;
    }
}

function canDeleteUser($logged_user_id, $target_user_id) {
    $role = getUserRole($logged_user_id);
    
    switch($role) {
        case 'ADMIN':
            return true;
        case 'TEAM_LEADER':
            return isTeamMember($target_user_id, $logged_user_id);
        default:
            return false;
    }
}

function isTeamMember($user_id, $team_leader_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT 1 FROM team_members tm 
        JOIN teams t ON tm.team_id = t.id 
        WHERE tm.user_id = ? AND t.team_leader_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $team_leader_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function softDeleteUser($user_id, $deleted_by) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into deleted_records
        $stmt = $conn->prepare("
            INSERT INTO deleted_records (table_name, record_id, deleted_by) 
            VALUES ('users', ?, ?)
        ");
        $stmt->bind_param("ii", $user_id, $deleted_by);
        $stmt->execute();
        
        // Update user status
        $stmt = $conn->prepare("
            UPDATE users SET is_deleted = 1 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;    }
}

function getUserPermissions($userId) {
    global $conn;
    $permissions = [];
    
    // Check if user is admin first
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && $user['role'] === 'ADMIN') {
        // Admins have all permissions
        return [
            'view' => true,
            'add' => true,
            'update' => true,
            'delete' => true
        ];
    }
    
    // Get specific permissions for non-admin users
    $stmt = $conn->prepare("SELECT permission, granted FROM user_permissions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $permissions[$row['permission']] = (bool)$row['granted'];
    }
    
    return $permissions;
}

function hasPermission($userId, $permission) {
    $permissions = getUserPermissions($userId);
    return isset($permissions[$permission]) && $permissions[$permission] === true;
}

function updatePermission($userId, $permission, $granted) {
    global $conn;
    
    // Check if user is admin
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && $user['role'] === 'ADMIN') {
        return false; // Cannot modify admin permissions
    }
    
    $stmt = $conn->prepare("
        INSERT INTO user_permissions (user_id, permission, granted)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE granted = ?
    ");
    
    $granted = (int)$granted;
    $stmt->bind_param("isii", $userId, $permission, $granted, $granted);
    return $stmt->execute();
}

// Function to initialize permissions for a new user
function initializeUserPermissions($userId, $role = 'USER') {
    $permissions = ['view', 'add', 'update', 'delete'];
    $isAdmin = ($role === 'ADMIN');
    
    foreach ($permissions as $permission) {
        updatePermission($userId, $permission, $isAdmin);
    }
}
?>