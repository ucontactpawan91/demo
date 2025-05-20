<!-- <?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $address = trim($_POST['address']);
    $contact = trim($_POST['contact']);
    $gender = isset($_POST['gender']) ? $_POST['gender'] : null;
    $country_id = $_POST['country'];
    $state_id = $_POST['state'];
    $city_id = $_POST['city'];

    $errors = [];

   
    if (empty($username)) {
        $errors[] = "Name is required.";
    }


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }


    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    } else {
        $password = password_hash($password, PASSWORD_BCRYPT); // Hash the password
    }


    if (empty($address)) {
        $errors[] = "Address is required.";
    }

   
    if (!preg_match('/^[0-9]{10}$/', $contact)) {
        $errors[] = "Enter a valid 10-digit contact number.";
    }

   
    if (empty($gender)) {
        $errors[] = "Select a gender.";
    }

    if (empty($country_id)) {
        $errors[] = "Select a country.";
    }
    if (empty($state_id)) {
        $errors[] = "Select a state.";
    }
    if (empty($city_id)) {
        $errors[] = "Select a city.";
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
    } else {
       
        $sql = "INSERT INTO users (username, email, password, address, contact, gender, country_id, state_id, city_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssiii", $username, $email, $password, $address, $contact, $gender, $country_id, $state_id, $city_id);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Save hobbies
            if (!empty($_POST['hobbies'])) {
                $hobby_stmt = $conn->prepare("INSERT INTO user_hobbies (user_id, hobby_id) VALUES (?, ?)");
                foreach ($_POST['hobbies'] as $hobby_id) {
                    $hobby_stmt->bind_param("ii", $user_id, $hobby_id);
                    $hobby_stmt->execute();
                }
                $hobby_stmt->close();
            }
            
            header("Location: index.php?success=User created successfully");
            exit;
        } else {
            echo "<div class='alert alert-danger'>Error creating user: " . $stmt->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-left">Add Users</h1>
        <form action="create.php" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Name</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="text" class="form-control" id="email" name="email" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contact" class="form-label">Contact</label>
                    <input type="text" class="form-control" id="contact" name="contact" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender</label><br>
                    <input type="radio" id="male" name="gender" value="Male" required>
                    <label for="male">Male</label>
                    <input type="radio" id="female" name="gender" value="Female" required>
                    <label for="female">Female</label>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="country" class="form-label">Country</label>
                    <select class="form-select" id="country" name="country" onchange="changeState()" required>
                        <option value="" hidden>Select country</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= $country['id'] ?>"><?= $country['name'] ?></option>
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
                    <label class="form-label">Hobbies</label><br>
                    <input type="checkbox" id="hobby1" name="hobbies[]" value="1">
                    <label for="hobby1">Hobby 1</label>
                    <input type="checkbox" id="hobby2" name="hobbies[]" value="2">
                    <label for="hobby2">Hobby 2</label>
                    <input type="checkbox" id="hobby3" name="hobbies[]" value="3">
                    <label for="hobby3">Hobby 3</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById("password");
            const toggleIcon = document.getElementById("toggleIcon");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.remove("bi-eye");
                toggleIcon.classList.add("bi-eye-slash");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.remove("bi-eye-slash");
                toggleIcon.classList.add("bi-eye");
            }
        }

        const states = <?= json_encode($states) ?>;
        const cities = <?= json_encode($cities) ?>;

        function changeState() {
            const countryId = document.getElementById("country").value;
            const stateSelect = document.getElementById("state");
            stateSelect.innerHTML = '<option value="" hidden>Select state</option>';

            states.forEach(state => {
                if (state.country_id == countryId) {
                    const option = document.createElement("option");
                    option.value = state.id;
                    option.text = state.name;
                    stateSelect.appendChild(option);
                }
            });

            document.getElementById("city").innerHTML = '<option value="" hidden>Select city</option>';
        }

        function changeCity() {
            const stateId = document.getElementById("state").value;
            const citySelect = document.getElementById("city");
            citySelect.innerHTML = '<option value="" hidden>Select city</option>';

            cities.forEach(city => {
                if (city.state_id == stateId) {
                    const option = document.createElement("option");
                    option.value = city.id;
                    option.text = city.name;
                    citySelect.appendChild(option);
                }
            });
        }

        document.querySelector("form").addEventListener("submit", function (event) {
            const username = document.getElementById("username").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value.trim();
            const address = document.getElementById("address").value.trim();
            const contact = document.getElementById("contact").value.trim();
            const gender = document.querySelector('input[name="gender"]:checked');
            const country = document.getElementById("country").value;
            const state = document.getElementById("state").value;
            const city = document.getElementById("city").value;

            let isValid = true;
            let errorMessage = "";

          
            if (username === "") {
                isValid = false;
                errorMessage += "Name is required.\n";
            }

       
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                isValid = false;
                errorMessage += "Enter a valid email address.\n";
            }

            if (password.length < 6) {
                isValid = false;
                errorMessage += "Password must be at least 6 characters long.\n";
            }

          
            if (address === "") {
                isValid = false;
                errorMessage += "Address is required.\n";
            }

            const contactRegex = /^[0-9]{10}$/;
            if (!contactRegex.test(contact)) {
                isValid = false;
                errorMessage += "Enter a valid 10-digit contact number.\n";
            }

      
            if (!gender) {
                isValid = false;
                errorMessage += "Select a gender.\n";
            }

        
            if (country === "") {
                isValid = false;
                errorMessage += "Select a country.\n";
            }
            if (state === "") {
                isValid = false;
                errorMessage += "Select a state.\n";
            }
            if (city === "") {
                isValid = false;
                errorMessage += "Select a city.\n";
            }

            if (!isValid) {
                event.preventDefault();
                alert(errorMessage);
            }
        });
    </script>
</body>
</html> -->