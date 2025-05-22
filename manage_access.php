<?php
session_start();
include 'db.php';
include __DIR__ . '/includes/access_control.php';

if (!isset($_SESSION['user_id']) || getUserRole($_SESSION['user_id']) !== 'ADMIN') {
    header("Location: index.php");
    exit;
}

// Create the user_permissions table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS user_permissions (
    id INT PRIMARY KEY,
    `add` TINYINT(1) DEFAULT 0,
    `edit` TINYINT(1) DEFAULT 0,
    `view` TINYINT(1) DEFAULT 0,
    `delete` TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id) REFERENCES users(id)
)";

if (!$conn->query($sql)) {
    die("Error creating permissions table: " . $conn->error);
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        // If user is made admin, give them all permissions
        if ($new_role === 'ADMIN') {
            $sql = "INSERT INTO user_permissions (id, `add`, `edit`, `view`, `delete`) 
                   VALUES (?, 1, 1, 1, 1) 
                   ON DUPLICATE KEY UPDATE 
                   `add` = 1, `edit` = 1, `view` = 1, `delete` = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }
        $_SESSION['success'] = "User role updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update user role";
    }
    header("Location: manage_access.php");
    exit;
}

// Fetch all users with their current roles
$sql = "SELECT 
    u.id,
    u.username,
    u.email,
    u.role,
    (SELECT granted FROM user_permissions WHERE user_id = u.id AND permission = 'add') AS can_add,
    (SELECT granted FROM user_permissions WHERE user_id = u.id AND permission = 'edit') AS can_edit,
    (SELECT granted FROM user_permissions WHERE user_id = u.id AND permission = 'view') AS can_view,
    (SELECT granted FROM user_permissions WHERE user_id = u.id AND permission = 'delete') AS can_delete
FROM users u
WHERE u.is_deleted = 0
ORDER BY u.username";

$result = $conn->query($sql);
if (!$result) {
    die("SQL Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Manage User Access</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap/icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .main-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 1200px;
        }
        .search-container {
            max-width: 400px;
            margin-bottom: 2rem;
        }
        .search-container .input-group {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .table-perms {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.02);
        }
        .table-perms th {
            background: #f8f9fa;
            font-weight: 600;
            border-top: none;
        }
        .table-perms td, .table-perms th {
            padding: 1rem;
            vertical-align: middle;
        }        .user-info {
            display: flex;
            align-items: center;
        }
        .user-details {
            display: flex;
            flex-direction: column;
        }
        .user-name {
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-email {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .role-icon {
            font-size: 1.1rem;
            margin-left: 6px;
        }
        .role-admin { color: #2563eb; }
        .role-team-leader { color: #eab308; }
        .role-user { color: #22c55e; }
        .role-hr { color: #ef4444; }
          .perm-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            margin: 0;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .perm-checkbox:checked {
            background-color: #2563eb;
            border-color: #2563eb;
            position: relative;
        }
        .perm-checkbox:hover:not(:checked) {
            border-color: #2563eb;
        }
        .loading {
            pointer-events: none;
            opacity: 0.7;
        }
        
    
        .table-perms tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .btn-icon {
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-icon:hover {
            background: #f3f4f6;
        }        .role-text {
            font-size: 0.875rem;
            color: #4b5563;
        }
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            color: #fff;
            margin-right: 0.5rem;
        }
        .avatar-blue { background-color: #2563eb; }
        .avatar-orange { background-color: #eab308; }
        .avatar-purple { background-color: #6b5ce6; }
        .avatar-cyan { background-color: #06b6d4; }
        
        .dataTables_paginate {
            margin-top: 1rem;
            text-align: right;
        }
        .dataTables_info {
            margin-top: 1rem;
            color: #6b7280;
        }
        .paginate_button {
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 0.375rem;
            cursor: pointer;
            color: #4b5563;
        }
        .paginate_button.current {
            background-color: #2563eb;
            color: white;
        }
        .paginate_button:hover:not(.current) {
            background-color: #f3f4f6;
        }
        /* End DataTables Styling */

        </style>
</head>
<body>
    <div class="main-card">        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="fw-bold mb-0">Permission Manager</h3>
            </div>

            <div class="search-container d-flex justify-content-end mb-3">
                <input type="text" id="userSearch" class="form-control" placeholder="Search users..." style="border-radius: 20px; padding: 0.375rem 1rem;">
            </div>

            <div class="d-flex justify-content-end gap-3 align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-shield-alt role-admin"></i>
                    <span class="role-text">Admin</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-user-tie role-team-leader"></i>
                    <span class="role-text">Team Leader</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-user role-user"></i>
                    <span class="role-text">User</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-briefcase role-hr"></i>
                    <span class="role-text">HR</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-perms align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;">Sno.</th>
                        <th style="min-width:180px;text-align:left;">Name</th>
                        <th>Add <i class="bi bi-plus-circle tooltip-icon" data-bs-toggle="tooltip" title="Add Access"></i></th>
                        <th>Edit <i class="bi bi-pencil-square tooltip-icon" data-bs-toggle="tooltip" title="Edit Access"></i></th>
                        <th>View <i class="bi bi-eye tooltip-icon" data-bs-toggle="tooltip" title="View Access"></i></th>
                        <th>Delete <i class="bi bi-trash tooltip-icon" data-bs-toggle="tooltip" title="Delete Access"></i></th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $serial = 1;
                $avatar_colors = ['avatar-blue','avatar-orange','avatar-purple','avatar-cyan'];
                while ($user = $result->fetch_assoc()): 
                    $color = $avatar_colors[($serial-1)%count($avatar_colors)];
                    $initials = strtoupper(substr($user['username'],0,1));
                    $role_icon = '';
                    switch ($user['role']) {
                        case 'ADMIN':
                            $role_icon = '<i class="fas fa-shield-alt role-admin"></i>';
                            break;
                        case 'TEAM_LEADER':
                            $role_icon = '<i class="fas fa-user-tie role-team-leader"></i>';
                            break;
                        case 'USER':
                            $role_icon = '<i class="fas fa-user role-user"></i>';
                            break;
                        case 'HR':
                            $role_icon = '<i class="fas fa-briefcase role-hr"></i>';
                            break;
                    }
                ?>
                    <tr class="user-row">
                        <td><?php echo $serial; ?></td>
                        <td style="text-align:left;">
                            <div class="d-flex align-items-center">
                                <span class="avatar <?php echo $color; ?>"> <?php echo $initials; ?></span>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold"> <?php echo htmlspecialchars($user['username']); ?></span>
                                        <?php echo $role_icon; ?>
                                    </div>
                                    <span class="text-muted" style="font-size:0.95em;"> <?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>                            <input class="form-check-input perm-checkbox" type="checkbox" 
                                   data-user-id="<?php echo $user['id']; ?>" 
                                   data-permission="can_add"
                                   <?php echo ($user['role'] === 'ADMIN' || $user['can_add']) ? 'checked' : ''; ?>>
                        </td>
                        <td>
                            <input class="form-check-input perm-checkbox" type="checkbox" 
                                   data-user-id="<?php echo $user['id']; ?>" 
                                   data-permission="can_edit"
                                   <?php echo ($user['role'] === 'ADMIN' || $user['can_edit']) ? 'checked' : ''; ?>>
                        </td>
                        <td>
                            <input class="form-check-input perm-checkbox" type="checkbox" 
                                   data-user-id="<?php echo $user['id']; ?>" 
                                   data-permission="can_view"
                                   <?php echo ($user['role'] === 'ADMIN' || $user['can_view']) ? 'checked' : ''; ?>>
                        </td>
                        <td>
                            <input class="form-check-input perm-checkbox" type="checkbox" 
                                   data-user-id="<?php echo $user['id']; ?>" 
                                   data-permission="can_delete"
                                   <?php echo ($user['role'] === 'ADMIN' || $user['can_delete']) ? 'checked' : ''; ?>>
                        </td>
                    </tr>
                <?php 
                $serial++; endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
     <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
       
        $(document).ready(function() {
           

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize DataTable
            var table = $('.table-perms').DataTable({
                "pageLength": 10,
                "searching": true,
                "ordering": true,
                "order": [[0, "asc"]],
                "dom": 't<"bottom"ip>',
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search users...",
                    "info": "Showing _START_ to _END_ of _TOTAL_ users",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                },
                "columnDefs": [
                    {
                        "targets": [2, 3, 4, 5],
                        "orderable": false
                    }
                ]
            });

            // Connect the search bar to DataTables search functionality
            $('#userSearch').on('input', function() {
                table.search(this.value).draw();
            });

            // Handle permission checkbox changes
            $('.perm-checkbox').on('click', function() {
                alert("check click");
                const userId = $(this).data('user-id');
                const permission = $(this).data('permission').replace('can_', '');
                const granted = this.checked;
                const checkbox = $(this);
                const row = checkbox.closest('tr');
                
                // Add loading state
                row.addClass('loading');
                checkbox.prop('disabled', true);

                // Add spinner
                const spinner = $('<span class="spinner-border spinner-border-sm ms-2" role="status"></span>');
                checkbox.closest('td').append(spinner);

                $.ajax({
                    url: 'update_permissions.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        userId: userId,
                        permission: permission,
                        granted: granted
                    }),
                    success: function(response) {
                        if (response.success) {
                            // Show success feedback
                            const successIcon = $('<i class="fas fa-check text-success ms-2"></i>');
                            checkbox.closest('td').append(successIcon);
                            setTimeout(() => successIcon.fadeOut('slow', function() { $(this).remove(); }), 2000);
                        } else {
                            // Revert checkbox if update failed
                            checkbox.prop('checked', !granted);
                            alert(response.message || 'Failed to update permission');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        checkbox.prop('checked', !granted);
                        alert('Error updating permission. Please try again.');
                    },
                    complete: function() {
                        // Remove loading state and spinner
                        row.removeClass('loading');
                        checkbox.prop('disabled', false);
                        spinner.remove();
                    }
                });
            });
        });
    </script>

    
</body>
</html>