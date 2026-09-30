<?php
session_start();
if(!isset($_SESSION["id_patient"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== 'patient') {
    session_destroy();
    header("Location: patient_auth_login.php");
    exit();
}

require_once '../../DB/connect.php';

$id_patient = $_SESSION["id_patient"];
$success_message = "";
$error_message = "";

// Get patient info
$stmt = $conn->prepare("SELECT last_name, first_name, email, phone, birth_date, national_id FROM patient WHERE id_patient = ?");
$stmt->bind_param("i", $id_patient);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

$patient_name = $patient['first_name'] . ' ' . $patient['last_name'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_settings"])) {
    // Update phone
    $phone = trim($_POST["phone"]);
    
    $error_message = '';
    
    if (!empty($phone)) {
        $update_stmt = $conn->prepare("UPDATE patient SET phone = ? WHERE id_patient = ?");
        $update_stmt->bind_param("si", $phone, $id_patient);
        
        if ($update_stmt->execute()) {
            $patient['phone'] = $phone;
            $success_message = "Phone number updated successfully!";
        }
        $update_stmt->close();
    }
    
    // Update email if provided
    if (!empty($_POST["new_email"])) {
        $new_email = trim($_POST["new_email"]);
        
        if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $check_stmt = $conn->prepare("SELECT id_patient FROM patient WHERE email = ? AND id_patient != ?");
            $check_stmt->bind_param("si", $new_email, $id_patient);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "This email is already in use.";
            } else {
                $update_stmt = $conn->prepare("UPDATE patient SET email = ? WHERE id_patient = ?");
                $update_stmt->bind_param("si", $new_email, $id_patient);
                
                if ($update_stmt->execute()) {
                    $patient['email'] = $new_email;
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        } else {
            $error_message = "Please enter a valid email address.";
        }
    }
    
    // Update password if provided
    if (!empty($_POST["current_password"]) && !empty($_POST["new_password"])) {
        $current_password = $_POST["current_password"];
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_password"];
        
        if (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            $verify_stmt = $conn->prepare("SELECT password FROM patient WHERE id_patient = ?");
            $verify_stmt->bind_param("i", $id_patient);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $user_data = $verify_result->fetch_assoc();
            $verify_stmt->close();
            
            if (password_verify($current_password, $user_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE patient SET password = ? WHERE id_patient = ?");
                $update_stmt->bind_param("si", $hashed_password, $id_patient);
                
                if (!$update_stmt->execute()) {
                    $error_message = "Failed to update password.";
                }
                $update_stmt->close();
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
    }
    
    if (empty($error_message)) {
        $success_message = "Settings updated successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | HippoCare</title>
    <link rel="stylesheet" href="css/patient_style.css">
    <link rel="stylesheet" href="css/patient_forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="header-banner">
        <div class="logo-container">
            <a href="patient_home.php" class="logo-link">
                <h1 class="logo-text">HippoCare</h1>
            </a>
        </div>
        <div class="header-actions">
            <span class="user-name">
                <span class="name-part">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($patient_name); ?>
                </span>
                <span class="date-part">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date("d F Y"); ?>
                </span>
            </span>
            <a href="patient_settings.php" title="Settings" class="settings-btn">
                <i class="fas fa-cog"></i>
            </a>
            <a href="../../DB/logout.php" class="disconnect-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Disconnect</span>
            </a>
        </div>
    </div>

    <header>
        <h1>Account Settings</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="patient_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="patient_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="patient_consult.php"><i class="fas fa-stethoscope"></i> Consultations</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <form method="post" action="">
            <section class="settings-container">
                <header class="settings-header">
                    <h1><i class="fas fa-cog"></i> Account Settings</h1>
                    <p class="settings-subtitle">Manage your personal information, email, and password</p>
                </header>

                <?php if(!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div class="alert-content">
                            <strong>Success!</strong>
                            <p><?php echo $success_message; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="alert-content">
                            <strong>Error!</strong>
                            <p><?php echo $error_message; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Personal Information Section -->
                <article class="settings-block">
                    <header class="settings-block-header">
                        <h2><i class="fas fa-user"></i> Personal Information</h2>
                        <p>Your profile details</p>
                    </header>

                    <div class="settings-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="readonly-notice">(Read-only)</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($patient['last_name']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="first_name">First Name <span class="readonly-notice">(Read-only)</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($patient['first_name']); ?>" readonly>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="birth_date">Birth Date <span class="readonly-notice">(Read-only)</span></label>
                                <input type="date" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($patient['birth_date'] ?? ''); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="national_id">National ID <span class="readonly-notice">(Read-only)</span></label>
                                <input type="text" id="national_id" name="national_id" value="<?php echo htmlspecialchars($patient['national_id'] ?? ''); ?>" readonly>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Email Address Section -->
                <article class="settings-block">
                    <header class="settings-block-header">
                        <h2><i class="fas fa-envelope"></i> Email Address</h2>
                        <p>Change your email address</p>
                    </header>

                    <div class="settings-form">
                        <div class="current-email">
                            <label>Current Email</label>
                            <div class="email-display">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo htmlspecialchars($patient['email']); ?></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_email">New Email Address</label>
                            <input type="email" id="new_email" name="new_email" placeholder="Leave blank to keep current email">
                        </div>
                    </div>
                </article>

                <!-- Change Password Section -->
                <article class="settings-block">
                    <header class="settings-block-header">
                        <h2><i class="fas fa-lock"></i> Change Password</h2>
                        <p>Update your password for security</p>
                    </header>

                    <div class="settings-form">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Required to change password">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" minlength="8" placeholder="Minimum 8 characters">
                                <small class="form-help">Minimum 8 characters required</small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" minlength="8" placeholder="Confirm your new password">
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Save Button -->
                <div class="settings-save-button">
                    <button type="submit" name="save_settings" class="btn-save">
                        <i class="fas fa-save"></i>
                        <span>Save All Changes</span>
                    </button>
                </div>
            </section>
        </form>
    </main>
    
    <footer>
        <p>&copy; 2025 HIPPOCARE. All rights reserved.</p>
    </footer>
    
    <script src="js/validation.js"></script>
</body>
</html>
