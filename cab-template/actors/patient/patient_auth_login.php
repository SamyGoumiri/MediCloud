<?php
session_start();

if(isset($_SESSION["id_patient"])) {
    header("Location: patient_home.php");
    exit();
}

require_once '../../DB/connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id_patient, password FROM patient WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['id_patient'] = $row['id_patient'];
            $_SESSION['user_role'] = 'patient';
            header("Location: patient_home.php");
            exit();
        } else {
            $error = "❌ Invalid email or password. Please try again.";
        }
    } else {
        $error = "❌ Invalid email or password. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Login - HIPPOCARE</title>
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
                <h1>Patient Portal</h1>
                <p>Access your health records</p>
            </div>

            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Your password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-large btn-full">Sign In</button>
            </form>

            <?php if (!empty($error)) : ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="auth-link" style="text-align: center;">
                <p>Don't have an account? <a href="patient_auth_register.php" style="color: #007bff; text-decoration: underline;">Create one here</a></p>
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
