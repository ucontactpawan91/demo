<?php
session_start();
include 'db.php';
include __DIR__ . '/includes/access_control.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = getUserRole($_SESSION['user_id']);

// Get all hobbies to create a lookup map
$hobbiesMap = [];
$hobbiesResult = $conn->query("SELECT id, name FROM hobbies");
if ($hobbiesResult && $hobbiesResult->num_rows > 0) {
    while ($hobby = $hobbiesResult->fetch_assoc()) {
        $hobbiesMap[$hobby['id']] = $hobby['name'];
    }
}

// Fetch users based on role with all necessary joins
$sql = "SELECT u.*, c.name as country_name, s.name as state_name, ci.name as city_name 
        FROM users u 
        LEFT JOIN countries c ON u.country_id = c.id 
        LEFT JOIN state s ON u.state_id = s.id 
        LEFT JOIN city ci ON u.city_id = ci.id";

// Add role-based conditions
if ($user_role === 'USER') {
    $sql .= " WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
    }
} elseif ($user_role === 'TEAM_LEADER') {
    $sql .= " LEFT JOIN team_members tm ON u.id = tm.user_id 
              LEFT JOIN teams t ON tm.team_id = t.id 
              WHERE t.team_leader_id = ? OR u.id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
    }
} else {
    // For ADMIN and HR roles - show all users
    $stmt = $conn->prepare($sql);
}

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center"><b>User Dashboard</b></h1>
        <div class="d-flex justify-content-between mt-4">
            <?php if (getUserRole($_SESSION['user_id']) === 'ADMIN'): ?>
                <div>
                    <a href="adduser.php" class="btn btn-primary mx-2">Add New User</a>
                    <a href="manage_access.php" class="btn btn-info mx-2">Manage User Access</a>
                </div>
            <?php else: ?>
                <a href="adduser.php" class="btn btn-primary mx-2">Add New User</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-danger mx-2">Logout</a>
        </div>
        <div class="mt-4">
            <?php
            
            if (isset($_GET['error'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                echo htmlspecialchars($_GET['error']);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
            }
            ?>

            <?php            
                $stmt->execute();
                $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo '<table class="table table-bordered mt-4">';
                echo '<thead><tr><th>ID</th><th>User Name</th><th>Email</th><th>Address</th><th>Contact</th><th>Gender</th><th>Country</th><th>State</th><th>City</th><th>Hobbies</th><th>Actions</th></tr></thead>';
                echo '<tbody>';
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $row["id"] . '</td>';
                    echo '<td>' . $row["username"] . '</td>';
                    echo '<td>' . $row["email"] . '</td>';
                    echo '<td>' . $row["address"] . '</td>';
                    echo '<td>' . $row["contact"] . '</td>';
                    echo '<td>' . $row["gender"] . '</td>';
                    echo '<td>' . $row["country_name"] . '</td>';
                    echo '<td>' . $row["state_name"] . '</td>';
                    echo '<td>' . $row["city_name"] . '</td>';
                    // Convert hobby IDs to names
                    $hobbyNames = [];
                    if (!empty($row["hobbies"])) {
                        $hobbyIds = explode(',', $row["hobbies"]);
                        foreach ($hobbyIds as $id) {
                            if (isset($hobbiesMap[$id])) {
                                $hobbyNames[] = $hobbiesMap[$id];
                            }
                        }
                    }
                    echo '<td>' . implode(', ', $hobbyNames) . '</td>';
                    echo '<td><a href="edit.php?id=' . $row['id'] . '" class="btn btn-warning btn-sm">Edit</a> ';
                    echo '<a href="delete.php?id=' . $row['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this user? \')">Delete</a></td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p class="text-center">No users found</p>';
            }

            $conn->close();
            ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 