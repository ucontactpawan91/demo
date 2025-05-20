<?php
include 'db.php';

if(isset($_GET['id'])){
    $id = $_GET['id'];

    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if($stmt->execute()){
        header("Location: index.php?success=User deleted successfully");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: index.php?error=Invalid delete request");
    exit;
}
?>