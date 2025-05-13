<?php
include 'db.php';

if(isset($_GET['id'])){
    $id = $_GET['id'];
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result(); 
    $user = $result->fetch_assoc();

    if(!$user){
        echo"user not found!";
        exit;
    }
}else{
    echo"Invalid request";
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = $_POST['username'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $gender = $_POST['gender'];
    $state = $_POST['state'];
    $city = $_POST['city'];


$sql = "UPDATE users SET username = ?, email = ?, address = ?, contact = ?, gender = ?, state = ?, city = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssi", $username, $email, $address, $contact, $gender, $state, $city, $id);

if($stmt->execute()){
    header("Location: read.php?success=User upadate successfully");
} else{
    echo "Error" . $stmt->error;
}

$stmt->close();
$conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
     <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-left">Edit User</h1>
        <form method="POST">
        
            <div class="row">
                <div class="col-md-6 mb-3">
                     <label for="username" class="form-lebel">Name</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-lebel">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="address" class="form-lebel">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo $user['address']; ?></textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contact" class="form-lebel">Contact</label>
                    <input type="text" class="form-control" id="contact" name="contact" value="<?php echo $user['contact']; ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender</label>
                    <input type="radio" id="male" name="gender" value="male"
                    <?php echo ($user['gender']== 'Male') ? 'checked' : ''; ?>  required>
                    <label for="male">Male</label>
                     <input type="radio" id="female" name="gender" value="female"
                     <?php echo ($user['gender']== 'Female') ? 'checked' : ''; ?>  required>
                     <label for="female">Female</label>
            </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="state" class="form-label">State</label>
                    <select class="form-select" id="state" name="state" required>
                        <option value="Bihar" <?php echo($user['state']=='Maharashtra')? 'selected' : ''; ?>>Maharashtra</option>
                        <option value="Uttarpradesh" <?php echo($user['state']=='Delhi')? 'selected' : ''; ?>>Delhi</option>
                    </select>
                </div>
                </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="city" class="form-label">City</label>
                    <select class="form-select" id="city" name="city" required>
                        <option value="patna"<?php echo($user['city']=='pune')? 'selected' : ''; ?>>Pune</option>
                        <option value="kanpur"<?php echo($user['city']=='noida')? 'selected' : ''; ?>>Noida</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
    
</body>
</html>