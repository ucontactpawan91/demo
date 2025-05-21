<?php
include 'db.php';

// Delete existing test user if any
$stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
$email = "test@test.com";
$stmt->bind_param("s", $email);
$stmt->execute();

// Create new test user
$username = "Test User";
$password = password_hash("test123", PASSWORD_BCRYPT);
$address = "Test Address";
$contact = "1234567890";
$gender = "Male";
$country_id = 1;
$state_id = 1;
$city_id = 1;

$sql = "INSERT INTO users (username, email, password, address, contact, gender, country_id, state_id, city_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssiis", $username, $email, $password, $address, $contact, $gender, $country_id, $state_id, $city_id);

if ($stmt->execute()) {
    echo "Test user created successfully!\n";
    echo "Email: test@test.com\n";
    echo "Password: test123\n";
} else {
    echo "Error creating test user: " . $stmt->error;
}
?>
