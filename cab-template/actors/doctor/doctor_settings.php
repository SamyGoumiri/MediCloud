<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('doctor', $conn);

$id_doctor = $_SESSION["id_doctor"];
$success_message = "";
$error_message = "";

$stmt = $conn->prepare("SELECT id_doctor, last_name, first_name, email, phone, birth_date, national_id, speciality, consultation_fee, recruitment_date, practice_start_year FROM doctor WHERE id_doctor = ?");
$stmt->bind_param("i", $id_doctor);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

$doctor_name = $doctor['first_name'] . ' ' . $doctor['last_name'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_settings"])) {
    // Update personal information (only editable fields)
    $phone = trim($_POST["phone"]);
    
    // Validate fields that can be edited
    $error_message = '';
    
    // Update phone
    if (!empty($phone)) {
        $update_stmt = $conn->prepare("UPDATE doctor SET phone = ? WHERE id_doctor = ?");
        $update_stmt->bind_param("si", $phone, $id_doctor);
        
        if ($update_stmt->execute()) {
            $doctor['phone'] = $phone;
            $success_message = "Phone number updated successfully!";
        }
        $update_stmt->close();
    }
    
    // Update email if provided
    if (!empty($_POST["new_email"])) {
        $new_email = trim($_POST["new_email"]);
        
        if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $check_stmt = $conn->prepare("SELECT id_doctor FROM doctor WHERE email = ? AND id_doctor != ?");
            $check_stmt->bind_param("si", $new_email, $id_doctor);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "This email is already in use.";
            } else {
                $update_stmt = $conn->prepare("UPDATE doctor SET email = ? WHERE id_doctor = ?");
                $update_stmt->bind_param("si", $new_email, $id_doctor);
                
                if ($update_stmt->execute()) {
                    $doctor['email'] = $new_email;
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
            $verify_stmt = $conn->prepare("SELECT password FROM doctor WHERE id_doctor = ?");
            $verify_stmt->bind_param("i", $id_doctor);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $user_data = $verify_result->fetch_assoc();
            $verify_stmt->close();
            
            if (password_verify($current_password, $user_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE doctor SET password = ? WHERE id_doctor = ?");
                $update_stmt->bind_param("si", $hashed_password, $id_doctor);
                
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
    <link rel="stylesheet" href="css/doctor_style.css">
    <link rel="stylesheet" href="css/doctor_forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="js/validation-errors.css">
</head>
<body>
    <div class="header-banner">
        <div class="logo-container">
            <a href="doctor_home.php" class="logo-link">
                <h1 class="logo-text">HippoCare</h1>
            </a>
        </div>
        <div class="header-actions">
            <span class="user-name">
                <span class="name-part">
                    <i class="fas fa-user-circle"></i>
                    Dr. <?php echo htmlspecialchars($doctor_name); ?>
                </span>
                <span class="date-part">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date("d F Y"); ?>
                </span>
            </span>
            <a href="doctor_settings.php" title="Settings" class="settings-btn">
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
                <a href="doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
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
                        <p>Update your profile details</p>
                    </header>

                    <div class="settings-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="readonly-notice">(Read-only)</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($doctor['last_name']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="first_name">First Name <span class="readonly-notice">(Read-only)</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($doctor['first_name']); ?>" readonly>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="birth_date">Birth Date <span class="readonly-notice">(Read-only)</span></label>
                                <input type="date" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($doctor['birth_date'] ?? ''); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="national_id">National ID <span class="readonly-notice">(Read-only)</span></label>
                                <input type="text" id="national_id" name="national_id" value="<?php echo htmlspecialchars($doctor['national_id'] ?? ''); ?>" readonly>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="speciality">Speciality <span class="readonly-notice">(Read-only)</span></label>
                                <input type="text" id="speciality" name="speciality" value="<?php echo htmlspecialchars($doctor['speciality'] ?? ''); ?>" readonly>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="consultation_fee">Consultation Fee (DZD) <span class="readonly-notice">(Read-only)</span></label>
                                <input type="number" id="consultation_fee" name="consultation_fee" step="0.01" min="0" value="<?php echo htmlspecialchars($doctor['consultation_fee'] ?? ''); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="recruitment_date">Recruitment Date <span class="readonly-notice">(Read-only)</span></label>
                                <input type="date" id="recruitment_date" name="recruitment_date" value="<?php echo htmlspecialchars($doctor['recruitment_date'] ?? ''); ?>" readonly>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="practice_start_year">Started Practice (Year) <span class="readonly-notice">(Read-only)</span></label>
                                <input type="number" id="practice_start_year" name="practice_start_year" min="1950" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($doctor['practice_start_year'] ?? ''); ?>" readonly>
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
                                <span><?php echo htmlspecialchars($doctor['email']); ?></span>
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

<script src="js/validation.js"></script>
</body>
</html>

