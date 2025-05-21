<?php
require_once('includes/db.php');

// Create user_permissions table
$sql = "CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission VARCHAR(50) NOT NULL,
    granted BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_permission (user_id, permission),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table user_permissions created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create initial permissions for existing users
$sql = "SELECT id, role FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $userId = $row['id'];
        $isAdmin = $row['role'] === 'ADMIN';
        
        // Define permissions
        $permissions = ['view', 'add', 'update', 'delete'];
        
        foreach($permissions as $permission) {
            $stmt = $conn->prepare("
                INSERT IGNORE INTO user_permissions (user_id, permission, granted)
                VALUES (?, ?, ?)
            ");
            
            $granted = $isAdmin ? 1 : 0;
            $stmt->bind_param("isi", $userId, $permission, $granted);
            $stmt->execute();
        }
    }
    echo "Initial permissions created successfully\n";
}

$conn->close();
