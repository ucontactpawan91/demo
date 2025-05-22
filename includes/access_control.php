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
    
    // Do not update permissions for ADMIN if you want to enforce all permissions
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && $user['role'] === 'ADMIN') {
        return false; // Optionally, you could allow modification for testing
    }
    
    $grantedValue = (int)$granted;
    $stmt = $conn->prepare("
        INSERT INTO user_permissions (user_id, permission, granted)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE granted = ?
    ");
    $stmt->bind_param("isii", $userId, $permission, $grantedValue, $grantedValue);
    return $stmt->execute();
}
?>