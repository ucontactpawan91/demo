<?php
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

//  hobbies from dastabase 
$sql = "SELECT id, name FROM hobbies";
$result = $conn->query($sql);
$hobbies = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hobbies[] = $row;
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
        $password = password_hash($password, PASSWORD_BCRYPT);
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

    if (empty($_POST['hobbies'])) {
        $errors[] = "Please select at least one hobby.";
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
    <title>Document</title>
</head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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

<body>

    <div class="container mt-5">
        <h1 class="text-left">Add Users</h1>
        <form id="addUserForm" action="create.php" method="POST" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" name="username" id="name">
                    <div class="invalid-feedback name-error"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="text" class="form-control" name="email" id="email">
                    <div class="invalid-feedback email-error"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password">
                        <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                        <div class="invalid-feedback password-error"></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    <div class="invalid-feedback address-error"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contact" class="form-label">Contact</label>
                    <input type="text" class="form-control" id="contact" name="contact">
                    <div class="invalid-feedback contact-error"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender</label><br>
                    <input type="radio" id="male" name="gender" value="Male">
                    <label for="male">Male</label>
                    <input type="radio" id="female" name="gender" value="Female">
                    <label for="female">Female</label>
                    <div class="invalid-feedback gender-error" style="display:block;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="country" class="form-label">Country</label>
                    <select class="form-select" id="country" name="country" onchange="changeState()">
                        <option value="" hidden>Select country</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= $country['id'] ?>"><?= $country['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback country-error"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="state" class="form-label">State</label>
                    <select class="form-select" id="state" name="state" onchange="changeCity()">
                        <option value="" hidden>Select state</option>
                    </select>
                    <div class="invalid-feedback state-error"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="city" class="form-label">City</label>
                    <select class="form-select" id="city" name="city">
                        <option value="" hidden>Select city</option>
                    </select>
                    <div class="invalid-feedback city-error"></div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">choose your hobbies:</label>
                    <div class="hobbies-group">
                        <?php foreach ($hobbies as $hobby): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="hobbies[]"
                                    id="hobby<?= $hobby['id'] ?>" value="<?= $hobby['id'] ?>">
                                <label class="form-check-label" for="hobby<?= $hobby['id'] ?>">
                                    <?= htmlspecialchars($hobby['name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="invalid-feedback hobbies-error"></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
            <div class="mt-4">
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
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

            // Reset city dropdown
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


        $("#name").keyup(function () {
            if (isEmpty("name")) {
                validateName("name");
            }
        });
        $("#email").keyup(function () {
            if (isEmpty("email")) {
                validateEmail("email");
            }
        })
        $("#password").keyup(function () {
            isEmpty("password");
            validatePassword("password");
        });

        $("#address").keyup(function () {
            isEmpty("address");
            validateAddress("address");
        });

        $("#contact").keyup(function () {
            isEmpty("contact");
            validateContact("contact");
        });
        $("input[name='gender']").change(function () {
            validateGender("gender");
        });


        $("#country").change(function () {
            validateCountry("country");
        });

        $("#state").change(function () {
            validateState("state");
        });

        $("#city").change(function () {
            validateCity("city");
        });



        function isEmpty(id) {

            const name = $('#' + id);

            if (!name.val().trim()) {
                name.addClass('is-invalid');
                $('.' + id + "-error").text('this field can not be empty');
                return false;
            } else {
                name.removeClass('is-invalid');
                $('.' + id + "-error").text('');
                return true;
            }
        }

        function validateName(id) {

            const name = $('#' + id);

            const nameRegex = /^[A-Za-z\s]{1,20}$/;


            if (!nameRegex.test(name.val())) {
                name.addClass('is-invalid');
                $('.' + id + "-error").text('Only use Characters');
                return false;
            } else {
                name.removeClass('is-invalid');
                $('.' + id + "-error").text('');
                return true;
            }
        }


        function validateEmail(id) {

            const email = $('#' + id);

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailRegex.test(email.val())) {
                email.addClass('is-invalid');
                $('.' + id + "-error").text('Enter Valid email');
                return false;
            } else {
                email.removeClass('is-invalid');
                $('.' + id + "-error").text('');
                return true;
            }
        }

        function validatePassword(id) {
            const password = $('#' + id);
            const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;

            if (!passwordRegex.test(password.val())) {
                password.addClass('is-invalid');
                $('.' + id + "-error").text('Password must be at least 8 characters, include letters and numbers.');
                return false;
            } else {
                password.removeClass('is-invalid');
                $('.' + id + "-error").text('');
                return true;
            }
        }

        function validateAddress(id) {
            const address = $("#" + id);
            const addressRegex = /^[A-Za-z0-9\s,.'-]{3,}$/;

            if (!addressRegex.test(address.val())) {
                address.addClass('is-invalid');
                $('.' + id + "-error").text('Enter Valid Address');
                return false;
            } else {
                address.removeClass('is-invalid');
                $('.' + id + "-error").text('');
                return true;
            }
        }

        function validateContact(id) {
            const contact = $("#" + id);
            const contactRegex = /^[0-9]{10}$/;

            if (!contactRegex.test(contact.val())) {
                contact.addClass('is-invalid');
                $('.' + id + "-error").text('Enter Valid Contact');
                return false;
            } else {
                contact.removeClass('is-invalid');
                $('.' + id + "-error").text('');
                return true;
            }
        }

        function validateGender(id) {
            const gender = $("input[name='" + id + "']:checked");
            const errorDiv = $('.' + id + "-error");
            if (!gender.val()) {
                errorDiv.text('Select a gender');
                // Add red border to both radio buttons
                $("input[name='" + id + "']").addClass('is-invalid');
                errorDiv.show();
                return false;
            } else {
                errorDiv.text('');
                $("input[name='" + id + "']").removeClass('is-invalid');
                errorDiv.hide();
                return true;
            }
        }
        function validateCountry(id) {
            const country = $("#" + id);

            if (!country.val()) {
                country.addClass('is-invalid');
                $('.' + id + "-error").text('Select a country');
                return false;
            } else {
                country.removeClass('is-invalid');
                $('.' + id + "-error").text('');
                return true;
            }
        }

        function validateState(id) {
            const state = $("#" + id);

            if (!state.val()) {
                state.addClass('is-invalid');
                $('.' + id + "-error").text('Select a state');
                return false;
            } else {
                state.removeClass('is-invalid');
                $('.' + id + "-error").text('');
                return true;
            }
        }

        function validateCity(id) {
            const city = $("#" + id);

            if (!city.val()) {
                city.addClass('is-invalid');
                $('.' + id + "-error").text('Select a city');
                return false;
            } else {
                city.removeClass('is-invalid');
                $('.' + id + "-error").text('');
                return true;
            }
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

        $(document).ready(function () {

            $(document).on('change', "input[name='gender']", function () {
                validateGender("gender");
            });
            $(document).on('change', "#country", function () {
                validateCountry("country");
            });
            $(document).on('change', "#state", function () {
                validateState("state");
            });
            $(document).on('change', "#city", function () {
                validateCity("city");
            });
            $("input[name='hobbies[]']").change(function () {
                validateHobbies();
            });

            $('#addUserForm').on('submit', function (e) {
                let valid = true;
                if (!isEmpty('name') || !validateName('name')) valid = false;
                if (!isEmpty('email') || !validateEmail('email')) valid = false;
                if (!isEmpty('password') || !validatePassword('password')) valid = false;
                if (!isEmpty('address') || !validateAddress('address')) valid = false;
                if (!isEmpty('contact') || !validateContact('contact')) valid = false;
                if (!validateGender('gender')) valid = false;
                if (!validateCountry('country')) valid = false;
                if (!validateState('state')) valid = false;
                if (!validateCity('city')) valid = false;
                if (!validateHobbies()) valid = false; 
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>

</body>

</html>