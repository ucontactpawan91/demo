<?php
session_start();
require_once('includes/access_control.php');

// Check if user is logged in and has update permission
if (!isset($_SESSION['user_id']) || !hasPermission($_SESSION['user_id'], 'update')) {
    header('Location: index.php');
    exit();
}

include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // First get the user details with joins
    $sql = "SELECT u.*, 
                   c.name AS country_name, 
                   s.name AS state_name,
                   ct.name AS city_name,
                   c.id AS country_id,
                   s.id AS state_id,
                   ct.id AS city_id
            FROM users u 
            LEFT JOIN countries c ON u.country_id = c.id 
            LEFT JOIN state s ON u.state_id = s.id 
            LEFT JOIN city ct ON u.city_id = ct.id 
            WHERE u.id = ?";
            
    if (!($stmt = $conn->prepare($sql))) {
        die("Prepare failed: " . $conn->error);
    }
    if (!$stmt->bind_param("i", $id)) {
        die("Binding parameters failed: " . $stmt->error);
    }
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        header("Location: index.php?error=User not found");
        exit;
    }
} else {
  
    header("Location: index.php?error=Invalid request");
    exit;
}

// Get user's hobbies
$userHobbies = [];
if (!empty($user['hobbies'])) {
    $userHobbies = explode(',', $user['hobbies']);
}

// Get all hobbies
$sql = "SELECT id, name FROM hobbies";
$result = $conn->query($sql);
$hobbies = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $hobbies[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_GET['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $gender = $_POST['gender'];
    $country_id = $_POST['country'];
    $state_id = $_POST['state'];
    $city_id = $_POST['city'];
    $hobby_ids = isset($_POST['hobbies']) ? $_POST['hobbies'] : [];

    $sql = "UPDATE users SET username = ?, email = ?, address = ?, contact = ?, gender = ?, country_id = ?, state_id = ?, city_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL error: " . $conn->error);
    }
    $stmt->bind_param("sssssiiii", $username, $email, $address, $contact, $gender, $country_id, $state_id, $city_id, $id);

    if ($stmt->execute()) {
        // Update the hobbies in users table
        $hobbies_string = !empty($_POST['hobbies']) ? implode(',', $_POST['hobbies']) : '';
        $sql = "UPDATE users SET hobbies = ? WHERE id = ?";
        if ($hobby_stmt = $conn->prepare($sql)) {
            $hobby_stmt->bind_param("si", $hobbies_string, $id);
            if (!$hobby_stmt->execute()) {
                echo "Error updating hobbies: " . $hobby_stmt->error;
            }
            $hobby_stmt->close();
        } else {
            echo "Error preparing hobby update: " . $conn->error;
        }

        header("Location: index.php?success=User updated successfully");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}

$sql = "SELECT id, name FROM countries";
$result = $conn->query($sql);
$countries = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $countries[] = $row;
    }
}


$sql = "SELECT id, name, country_id FROM state WHERE status = 'active'";
$result = $conn->query($sql);
$states = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $states[] = $row;
    }
}

$sql = "SELECT id, name, state_id FROM city WHERE status = '1'";
$result = $conn->query($sql);
$cities = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cities[] = $row;
    }
}

$preselectedCities = [];
if (!empty($user['state_id'])) {
    foreach ($cities as $city) {
        if ($city['state_id'] == $user['state_id']) {
            $preselectedCities[] = $city;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        .hobbies-group {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 15px;
            margin-top: 5px;
        }

        .hobbies-group.is-invalid {
            border-color: #dc3545;
        }

        .form-check {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-left">Edit User</h1>
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Name</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo $user['address']; ?></textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contact" class="form-label">Contact</label>
                    <input type="text" class="form-control" id="contact" name="contact" value="<?php echo $user['contact']; ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender</label>
                    <input type="radio" id="male" name="gender" value="male" <?php echo ($user['gender'] == 'Male') ? 'checked' : ''; ?> required>
                    <label for="male">Male</label>
                    <input type="radio" id="female" name="gender" value="female" <?php echo ($user['gender'] == 'Female') ? 'checked' : ''; ?> required>
                    <label for="female">Female</label>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="country" class="form-label">Country</label>
                    <select class="form-select" id="country" name="country" onchange="changeState()" required>
                        <option value="" hidden>Select country</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo $country['id']; ?>" <?php echo ($user['country_id'] == $country['id']) ? 'selected' : ''; ?>>
                                <?php echo $country['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="state" class="form-label">State</label>
                    <select class="form-select" id="state" name="state" onchange="changeCity()" required>
                        <option value="" hidden>Select state</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="city" class="form-label">City</label>
                    <select class="form-select" id="city" name="city" required>
                        <option value="" hidden>Select city</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Choose your hobbies:</label>
                    <div class="hobbies-group">
                        <?php foreach ($hobbies as $hobby): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="hobbies[]"
                                    id="hobby<?= $hobby['id'] ?>" value="<?= $hobby['id'] ?>"
                                    <?= in_array($hobby['id'], $userHobbies) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="hobby<?= $hobby['id'] ?>">
                                    <?= htmlspecialchars($hobby['name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="invalid-feedback hobbies-error"></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    <script>
        const states = <?= json_encode($states) ?>;
        const cities = <?= json_encode($cities) ?>;

        document.addEventListener("DOMContentLoaded", function () {
            populateStates();
        });

        function populateStates() {
            const countryId = document.getElementById("country").value;
            const stateSelect = document.getElementById("state");
            stateSelect.innerHTML = '<option value="" hidden>Select state</option>';

            states.forEach(state => {
                if (state.country_id == countryId) {
                    const option = document.createElement("option");
                    option.value = state.id;
                    option.text = state.name;
                    option.selected = state.id == <?= json_encode($user['state_id']); ?>; 
                    stateSelect.appendChild(option);
                }
            });


            populateCities();
        }

        function changeState() {
            populateStates();
        }

        function populateCities() {
            const stateId = document.getElementById("state").value;
            const citySelect = document.getElementById("city");
            citySelect.innerHTML = '<option value="" hidden>Select city</option>';

            cities.forEach(city => {
                if (city.state_id == stateId) {
                    const option = document.createElement("option");
                    option.value = city.id;
                    option.text = city.name;
                    option.selected = city.id == <?= json_encode($user['city_id']); ?>; 
                    citySelect.appendChild(option);
                }
            });
        }

        function changeCity() {
            populateCities();
        }

        function validateHobbies() {
            const checkedHobbies = $("input[name='hobbies[]']:checked").length;
            if (checkedHobbies === 0) {
                $('.hobbies-error').text('Please choose at least one hobby');
                $('.hobbies-group').addClass('is-invalid');
                return false;
            }
            $('.hobbies-error').text('');
            $('.hobbies-group').removeClass('is-invalid');
            return true;
        }

        $(document).ready(function() {
            // Add validation for hobbies
            $("input[name='hobbies[]']").change(function() {
                validateHobbies();
            });

            // Add hobbies validation to form submission
            $('form').on('submit', function(e) {
                let isValid = true;
                if (!validateHobbies()) isValid = false;

                return isValid;
            });
        });
    </script>
</body>
</html>