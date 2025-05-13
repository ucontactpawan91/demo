<?php
include 'db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $gender = $_POST['gender'];
    $state = $_POST['state'];
    $city = $_POST['city'];

    $sql = "INSERT INTO users (username, email, password, address, contact, gender, state, city ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $username, $email, $password,  $address, $contact, $gender, $state, $city);

    if ($stmt->execute()) {
        header("Location: read.php?success=User added successfully");
    }else{
        echo "Error" . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}

?>