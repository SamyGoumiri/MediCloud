<?php
session_start();

if(isset($_SESSION["id_chief_doctor"])) {
    header("Location: chief_doctor_home.php");
    exit();
}

require_once '../../DB/connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id_doctor, password, role FROM doctor WHERE email = ? AND role = 'chief_doctor' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['id_chief_doctor'] = $row['id_doctor'];
            $_SESSION['user_role'] = 'chief_doctor';
            header("Location: chief_doctor_home.php");
            exit();
        } else {
            $error = "❌ Invalid email or password. Please try again.";
        }
    } else {
        $error = "❌ Invalid email or password. (Not authorized as Chief Doctor)";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médecin Chef Login - HIPPOCARE</title>
    <link rel="stylesheet" href="../../main/assets/main.css">
</head>
<body class="chief-doctor-login">
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
                <h1>Espace Médecin Chef</h1>
                <p>Accédez à votre tableau de bord de gestion</p>
            </div>

            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="votre@email.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required placeholder="Votre mot de passe">
                </div>
                
                <button type="submit" class="btn btn-primary btn-large btn-full">Se connecter</button>
            </form>

            <?php if (!empty($error)) : ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </div>

        <div class="back-to-home">
            <a href="../../main/index.php">← Retour à l'accueil</a>
        </div>
    </div>

    <footer class="footer-simple">
        <div class="container">
            <p>&copy; 2025 HIPPOCARE. Tous droits réservés.</p>
        </div>
    </footer>
    <script src="js/validation.js"></script>
</body>
</html>

