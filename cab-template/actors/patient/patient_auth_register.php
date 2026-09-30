<?php
session_start();

if(isset($_SESSION["id_patient"])) {
    header("Location: patient_home.php");
    exit();
}

require_once '../../DB/connect.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "❌ All fields are required";
    } elseif ($password !== $password_confirm) {
        $error = "❌ Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "❌ Password must be at least 6 characters";
    } else {
        $stmt = $conn->prepare("SELECT id_patient FROM patient WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "❌ Email already registered";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO patient (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);

            if ($stmt->execute()) {
                $success = "✅ Account created! You can now login.";
            } else {
                $error = "❌ Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - HIPPOCARE</title>
    <link rel="stylesheet" href="../../main/assets/main.css">
</head>
<body class="patient-login">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7V17C2 20.866 6.477 24 12 24C17.523 24 22 20.866 22 17V7L12 2Z" fill="#00A896"/>
                        <circle cx="12" cy="12" r="3" fill="white"/>
                        <path d="M12 9V15M9 12H15" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <h1>Create Account</h1>
                <p>Join HippoCare</p>
            </div>

            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required placeholder="John">
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required placeholder="Doe">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Minimum 6 characters">
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required placeholder="Confirm your password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-large btn-full">Create Account</button>
            </form>

            <?php if (!empty($error)) : ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)) : ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="auth-link">
                <p>Already have an account? <a href="patient_auth_login.php">Sign in here</a></p>
            </div>
        </div>

        <div class="back-to-home">
            <a href="../../main/index.php">← Back to home</a>
        </div>
    </div>

    <footer class="footer-simple">
        <div class="container">
            <p>&copy; 2025 HIPPOCARE. All rights reserved.</p>
        </div>
    </footer>
    <script src="js/validation.js"></script>
</body>
</html>
