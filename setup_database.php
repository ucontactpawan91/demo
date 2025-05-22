<?php
require_once('db.php');

// Create or update the user_permissions table
$sql = "DROP TABLE IF EXISTS `user_permissions`";
$conn->query($sql);

$sql = "CREATE TABLE `user_permissions` (
    `id` INT PRIMARY KEY,
    `add` TINYINT(1) DEFAULT 0,
    `edit` TINYINT(1) DEFAULT 0,
    `view` TINYINT(1) DEFAULT 0,
    `delete` TINYINT(1) DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`id`) REFERENCES users(`id`) ON DELETE CASCADE
) ENGINE=InnoDB";

if ($conn->query($sql)) {
    echo "User permissions table created successfully\n";
} else {
    die("Error creating table: " . $conn->error);
}

// Set default permissions for admin users
$sql = "INSERT INTO `user_permissions` (`id`, `add`, `edit`, `view`, `delete`)
        SELECT `id`, 1, 1, 1, 1
        FROM users
        WHERE role = 'ADMIN'";

if ($conn->query($sql)) {
    echo "Admin permissions set successfully\n";
} else {
    echo "Error setting admin permissions: " . $conn->error . "\n";
}

$conn->close();
echo "Setup completed!\n";
?>
