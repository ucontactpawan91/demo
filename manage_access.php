<?php
session_start();
include 'db.php';
include __DIR__ . '/includes/access_control.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || getUserRole($_SESSION['user_id']) !== 'ADMIN') {
    header("Location: index.php");
    exit;
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User role updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update user role";
    }
    header("Location: manage_access.php");
    exit;
}

// Fetch all users with their current roles
$sql = "SELECT id, username, email, role FROM users WHERE is_deleted = 0 ORDER BY username";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Access</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">    <style>
        body {
            background: #fafbfc;
        }
        .main-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 16px 0 rgba(60,72,88,.08);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 1100px;
        }
        .table-perms th, .table-perms td {
            vertical-align: middle;
            text-align: center;
        }
        .table-perms th {
            background: #f7f7f9;
            font-weight: 600;
        }
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #fff;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        .avatar-blue { background: #6c63ff; }
        .avatar-orange { background: #ffb347; }
        .avatar-purple { background: #a259ff; }
        .avatar-cyan { background: #00bcd4; }
        .perm-checkbox:checked {
            accent-color: #2563eb;
        }
        .perm-checkbox {
            width: 20px;
            height: 20px;
        }
        .perm-label {
            margin-left: 8px;
            font-weight: 400;
        }
        .table-perms tbody tr {
            transition: background 0.2s;
        }
        .table-perms tbody tr:hover {
            background: #f5f7fa;
        }
        .action-btns {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }
        .role-indicator {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
        }
        .role-admin { background-color: #2563eb; } /* Blue */
        .role-team-leader { background-color: #facc15; } /* Yellow */
        .role-user { background-color: #22c55e; } /* Green */
        .role-hr { background-color: #e11d48; } /* Red */
        .tooltip-icon {
            cursor: pointer;
            font-size: 1.2rem;
            color: #6b7280;
        }
        .tooltip-icon:hover {
            color: #374151;
        }
        @media (max-width: 900px) {
            .main-card { padding: 0.5rem; }
            .table-perms th, .table-perms td { font-size: 0.95rem; }
        }
    </style>
</head>
<body>
    <div class="main-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0">Permission Manager</h3>
            <div class="action-btns">
                <button class="btn btn-light border me-2">Cancel</button>
                <button class="btn btn-primary">Save changes</button>
            </div>
        </div>
        <div class="mb-4">
            <div class="input-group" style="max-width:400px;">
                <input type="text" id="userSearch" class="form-control" placeholder="Search users...">
                <button class="btn btn-outline-secondary" type="button">Search</button>
            </div>
        </div>
        <div class="mb-3">
            <span class="role-indicator role-admin">🟦 Admin</span>
            <span class="role-indicator role-team-leader">🟨 Team Leader</span>
            <span class="role-indicator role-user">🟩 User</span>
            <span class="role-indicator role-hr">🟥 HR</span>
        </div>
        <div class="table-responsive">
            <table class="table table-perms align-middle">
                <thead>
                    <tr>
                        <th style="min-width:180px;text-align:left;">Name</th>
                        <th>Delete <i class="bi bi-trash tooltip-icon" data-bs-toggle="tooltip" title="Delete Access"></i></th>
                        <th>Update <i class="bi bi-pencil-square tooltip-icon" data-bs-toggle="tooltip" title="Update Access"></i></th>
                        <th>View <i class="bi bi-eye tooltip-icon" data-bs-toggle="tooltip" title="View Access"></i></th>
                        <th>Edit <i class="bi bi-gear tooltip-icon" data-bs-toggle="tooltip" title="Edit Access"></i></th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $serial = 1;
                $avatar_colors = ['avatar-blue','avatar-orange','avatar-purple','avatar-cyan'];
                while ($user = $result->fetch_assoc()): 
                    $color = $avatar_colors[($serial-1)%count($avatar_colors)];
                    $initials = strtoupper(substr($user['username'],0,1));
                    $role_class = '';
                    $role_label = '';
                    switch ($user['role']) {
                        case 'ADMIN':
                            $role_class = 'role-admin';
                            $role_label = 'Admin';
                            break;
                        case 'TEAM_LEADER':
                            $role_class = 'role-team-leader';
                            $role_label = 'Team Leader';
                            break;
                        case 'USER':
                            $role_class = 'role-user';
                            $role_label = 'User';
                            break;
                        case 'HR':
                            $role_class = 'role-hr';
                            $role_label = 'HR';
                            break;
                    }
                ?>
                    <tr class="user-row">
                        <td style="text-align:left;">
                            <span class="avatar <?php echo $color; ?>"><?php echo $initials; ?></span>
                            <span class="fw-semibold"><?php echo htmlspecialchars($user['username']); ?></span>
                            <span class="role-indicator <?php echo $role_class; ?>">(<?php echo $role_label; ?>)</span><br>
                            <span class="text-muted" style="font-size:0.95em;"> <?php echo htmlspecialchars($user['email']); ?></span>
                        </td>
                        <td>
                            <input class="form-check-input perm-checkbox" type="checkbox" id="delete_<?php echo $user['id']; ?>">
                        </td>
                        <td>
                            <input class="form-check-input perm-checkbox" type="checkbox" id="update_<?php echo $user['id']; ?>">
                        </td>
                        <td>
                            <input class="form-check-input perm-checkbox" type="checkbox" id="view_<?php echo $user['id']; ?>">
                        </td>
                        <td>
                            <input class="form-check-input perm-checkbox" type="checkbox" id="edit_<?php echo $user['id']; ?>">
                        </td>
                    </tr>
                <?php $serial++; endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>