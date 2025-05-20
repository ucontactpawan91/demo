<?php
include 'db.php';

// Fetch all users
$sql = "SELECT id, password FROM users";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $plain_password = $row['password'];
    $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);

    // Update the password with the hashed version
    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $hashed_password, $id);
    $stmt->execute();
}

echo "Passwords have been hashed successfully.";

$conn->close();
?>