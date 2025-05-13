<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>



  <!-- <div class="mb-3">
                <label for="username" class="form-lebel">Name</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo $user['username']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-lebel">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
            </div>
             <div class="mb-3">
                <label for="address" class="form-lebel">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo $user['address']; ?></textarea>
            </div>
              <div class="mb-3">
                <label for="contact" class="form-lebel">Contact</label>
                <input type="text" class="form-control" id="contact" name="contact" value="<?php echo $user['contact']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Gender</label><br>
                <input type="radio" id="male" name="gender" value="male" 
                <?php echo ($user['gender']== 'Male') ? 'checked' : ''; ?>  required>
                <label for="male">Male</label>
                <input type="radio" id="female" name="gender" value="female"
                <?php echo ($user['gender']== 'Female') ? 'checked' : ''; ?>  required>
                <label for="female">Female</label>
            </div>
             <div class="mb-3">
                <label for="state" class="form-label">State</label>
                <select class="form-select" id="state" name="state" required>
                    <option value="Bihar" <?php echo($user['state']=='Bihar')? 'selected' : ''; ?>>Bihar</option>
                    <option value="Uttarpradesh" <?php echo($user['state']=='Uttarpradesh')? 'selected' : ''; ?>>Uttarpradesh</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="city" class="form-label">City</label>
                <select class="form-select" id="city" name="city" required>
                    <option value="patna"<?php echo($user['city']=='patna')? 'selected' : ''; ?>>Patna</option>
                    <option value="kanpur"<?php echo($user['city']=='kanpur')? 'selected' : ''; ?>>Kanpur</option>
                </select>
            </div> -->

            CREATE TABLE city (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    status ENUM('1', '0') DEFAULT '1',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES state(id) ON DELETE CASCADE
);





            CREATE TABLE state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

    


);
    
</body>
</html>