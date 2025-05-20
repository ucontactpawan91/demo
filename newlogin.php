<!-- <?php
// session_start();
// include 'db.php';

// if (isset($_SESSION['user_id'])) {
//     header("Location: index.php");
//     exit;
// }

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $email = $_POST['email'];
//     $password = $_POST['password'];

//     $sql = "SELECT * FROM users WHERE email = ?";
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("s", $email);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $user = $result->fetch_assoc();

//     if ($user && password_verify($password, $user['password'])) {
//         $_SESSION['user_id'] = $user['id'];
//         $_SESSION['username'] = $user['username'];
//         header("Location: index.php");
//         exit;
//     } else {
//         $error = "Invalid email or password.";
//     }
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
    <div class="login-container">
        <h1>Log in</h1>
        <div class="social-login">
            <a href="google_login.php" class="google"><i class="bi bi-google"></i></a>
            <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
            <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
            <a href="#" class="github"><i class="bi bi-github"></i></a>
        </div>
        <div class="divider"><span>or</span></div>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div id="emailError" class="text-danger"></div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </span>
                </div>
                <div id="passwordError" class="text-danger"></div>
            </div>
            <div class="remember-me">
                <div>
                    <input type="checkbox" id="rememberMe" name="rememberMe">
                    <label for="rememberMe">Remember Me</label>
                </div>
                <a href="forgot_password.php">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-3">Log In</button>
        </form>
        <div class="register-link">
            <p>New user? <a href="register.php">Register Now</a></p>
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

        document.querySelector("form").addEventListener("submit", function(event) {
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value.trim();

            const emailError = document.getElementById("emailError");
            const passwordError = document.getElementById("passwordError");

            let isValid = true;

            emailError.textContent = "";
            passwordError.textContent = "";

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                isValid = false;
                emailError.textContent = "Enter a valid email address.";
            }

            if (password.length < 6) {
                isValid = false;
                passwordError.textContent = "Password must be at least 6 characters long.";
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html> -->





