<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT users.*, countries.name AS country_name, countries.id AS country_id, 
                   state.name AS state_name, city.name AS city_name 
            FROM users 
            LEFT JOIN countries ON users.country_id = countries.id 
            LEFT JOIN state ON users.state_id = state.id 
            LEFT JOIN city ON users.city_id = city.id 
            WHERE users.id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL error: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
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

    $sql = "UPDATE users SET username = ?, email = ?, address = ?, contact = ?, gender = ?, country_id = ?, state_id = ?, city_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL error: " . $conn->error);
    }
    $stmt->bind_param("sssssiiii", $username, $email, $address, $contact, $gender, $country_id, $state_id, $city_id, $id);

    if ($stmt->execute()) {
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
    </script>
</body>
</html>