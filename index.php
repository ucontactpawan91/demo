<?php
session_start();
include 'db.php'; // Ensure this file connects to your database

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['errors']['login'] = "Email and password are required.";
        header("Location: login.php");
        exit;
    }

    // Check credentials in the database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php"); // Redirect to a dashboard or home page
        exit;
    } else {
        // Invalid credentials
        $_SESSION['errors']['login'] = "Invalid email or password.";
        header("Location: login.php");
        exit;
    }
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
            <a href="adduser.php" class="btn btn-primary mx-2">Add New User</a>
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

            <?php            // Get all hobbies to create a lookup map
            $hobbiesMap = [];
            $hobbiesResult = $conn->query("SELECT id, name FROM hobbies");
            if ($hobbiesResult->num_rows > 0) {
                while ($hobby = $hobbiesResult->fetch_assoc()) {
                    $hobbiesMap[$hobby['id']] = $hobby['name'];
                }
            }

            $sql = "SELECT users.*, countries.name as country_name, state.name as state_name, city.name as city_name 
                    FROM users 
                    LEFT JOIN countries ON users.country_id = countries.id 
                    LEFT JOIN state ON users.state_id = state.id 
                    LEFT JOIN city ON users.city_id = city.id";
            $result = $conn->query($sql);

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