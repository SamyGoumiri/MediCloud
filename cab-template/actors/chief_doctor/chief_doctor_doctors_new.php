<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('chief_doctor', $conn);

$id_chief_doctor = $_SESSION["id_chief_doctor"];
$stmt = $conn->prepare("SELECT last_name, first_name FROM doctor WHERE id_doctor = ?");
$stmt->bind_param("i", $id_chief_doctor);
$stmt->execute();
$chief_doctor_data = $stmt->get_result()->fetch_assoc();
$chief_doctor_name = $chief_doctor_data['first_name'] . ' ' . $chief_doctor_data['last_name'];

$success_message = '';
$error_message = '';

$today = new DateTime('today');
$doctor_birth_min = (clone $today)->modify('-100 years')->format('Y-m-d');
$doctor_birth_max = (clone $today)->modify('-18 years')->format('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $last_name = trim($_POST["last_name"]);
    $first_name = trim($_POST["first_name"]);
    $phone = trim($_POST["phone"]);
    $birth_date = trim($_POST["birth_date"]);
    $email = trim($_POST["email"]);
    $national_id = trim($_POST["national_id"]);
    $speciality = trim($_POST["speciality"]);
    $consultation_fee = !empty($_POST["consultation_fee"]) ? floatval($_POST["consultation_fee"]) : NULL;
    $recruitment_date = !empty($_POST["recruitment_date"]) ? $_POST["recruitment_date"] : NULL;
    $practice_start_year = !empty($_POST["practice_start_year"]) ? intval($_POST["practice_start_year"]) : NULL;
    $password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);
    
    // Validate birth date
    $birth_date_is_valid = true;
    $birth_dt = DateTime::createFromFormat('Y-m-d', $birth_date);
    $birth_dt_errors = DateTime::getLastErrors();
    if (!$birth_dt || !empty($birth_dt_errors['warning_count']) || !empty($birth_dt_errors['error_count'])) {
        $birth_date_is_valid = false;
    } else {
        $birth_dt->setTime(0, 0, 0);
        $min_dt = DateTime::createFromFormat('Y-m-d', $doctor_birth_min);
        $max_dt = DateTime::createFromFormat('Y-m-d', $doctor_birth_max);
        if ($birth_dt < $min_dt || $birth_dt > $max_dt) {
            $birth_date_is_valid = false;
        }
    }

    if (!$birth_date_is_valid) {
        $error_message = "Birth date must make the doctor between 18 and 100 years old.";
    }

    // Validate practice_start_year - must be at least 20 years after birth date
    if (empty($error_message) && !empty($practice_start_year)) {
        $birth_year = intval($birth_date);
        $min_practice_year = intval(substr($birth_date, 0, 4)) + 20;
        
        if ($practice_start_year < $min_practice_year) {
            $error_message = "Practice start year must be at least 20 years after birth year (minimum year: $min_practice_year).";
        }
    }

    // Validate recruitment_date - must be at least 20 years after birth date and >= practice_start_year
    if (empty($error_message) && !empty($recruitment_date)) {
        $recruitment_dt = DateTime::createFromFormat('Y-m-d', $recruitment_date);
        if ($recruitment_dt) {
            $recruitment_year = intval($recruitment_dt->format('Y'));
            $birth_year = intval(substr($birth_date, 0, 4));
            $min_recruitment_year = $birth_year + 20;
            
            if ($recruitment_year < $min_recruitment_year) {
                $error_message = "Recruitment date must be at least 20 years after birth year (minimum year: $min_recruitment_year).";
            } elseif (!empty($practice_start_year) && $recruitment_year < $practice_start_year) {
                $error_message = "Recruitment date must be on or after the practice start year ($practice_start_year).";
            }
        } else {
            $error_message = "Invalid recruitment date format.";
        }
    }

    if (empty($error_message) && $national_id === '') {
        $error_message = "National ID is required.";
    }

    // Check national ID uniqueness
    if (empty($error_message)) {
        $check_national_id = $conn->prepare("SELECT id_doctor FROM doctor WHERE national_id = ? LIMIT 1");
        $check_national_id->bind_param("s", $national_id);
        $check_national_id->execute();
        if ($check_national_id->get_result()->num_rows > 0) {
            $error_message = "This National ID is already registered. Please use a different National ID.";
        }
        $check_national_id->close();
    }
    
    // Check email uniqueness
    if (empty($error_message)) {
        $check_email = $conn->prepare("SELECT id_doctor FROM doctor WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows > 0) {
            $error_message = "This email is already registered. Please use a different email.";
        }
        $check_email->close();
    }
    
    if (empty($error_message)) {
        $conn->begin_transaction();
        try {
            $insert_doctor = $conn->prepare("INSERT INTO doctor (last_name, first_name, phone, birth_date, email, national_id, speciality, consultation_fee, recruitment_date, practice_start_year, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'doctor')");
            $insert_doctor->bind_param("sssssssdsiss", $last_name, $first_name, $phone, $birth_date, $email, $national_id, $speciality, $consultation_fee, $recruitment_date, $practice_start_year, $password);
            $insert_doctor->execute();
            
            $conn->commit();
            $success_message = "Doctor has been successfully added!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error adding doctor: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Doctor | HippoCare</title>
    <link rel="stylesheet" href="css/chief_doctor_style.css">
    <link rel="stylesheet" href="css/chief_doctor_forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="js/validation-errors.css">
</head>
<body>
    <div class="header-banner">
        <div class="logo-container">
            <a href="chief_doctor_home.php" class="logo-link">
                <h1 class="logo-text">HippoCare</h1>
            </a>
        </div>
        <div class="header-actions">
            <span class="user-name">
                <span class="name-part">
                    <i class="fas fa-user-circle"></i>
                    Dr. Chief <?php echo htmlspecialchars($chief_doctor_name); ?>
                </span>
                <span class="date-part">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date("d F Y"); ?>
                </span>
            </span>
            <a href="chief_doctor_settings.php" title="Settings" class="settings-btn">
                <i class="fas fa-cog"></i>
            </a>
            <a href="../../DB/logout.php" class="disconnect-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Disconnect</span>
            </a>
        </div>
    </div>

    <header>
        <h1>Add New Doctor</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="chief_doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="chief_doctor_doctors.php" class="active"><i class="fas fa-user-md"></i> Doctors</a>
                <a href="chief_doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="chief_doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="chief_doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="chief_doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-user-plus"></i> Add New Doctor</h2>
            </div>
            
            <?php if(!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="last_name">Last Name*</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label for="first_name">First Name*</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="birth_date">Birth Date*</label>
                            <input type="date" id="birth_date" name="birth_date" min="<?php echo htmlspecialchars($doctor_birth_min); ?>" max="<?php echo htmlspecialchars($doctor_birth_max); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="national_id">National ID*</label>
                            <input type="text" id="national_id" name="national_id" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number*</label>
                            <input type="text" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address*</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="speciality">Speciality*</label>
                            <select id="speciality" name="speciality" required>
                                <option value="">Select a speciality</option>
                                <option value="General Practitioner">General Practitioner</option>
                                <option value="Cardiologist">Cardiologist</option>
                                <option value="Pediatrician">Pediatrician</option>
                                <option value="Dermatologist">Dermatologist</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="consultation_fee">Consultation Fee (DZD)</label>
                            <input type="number" id="consultation_fee" name="consultation_fee" step="0.01" min="0" placeholder="e.g., 3000.00">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="recruitment_date">Recruitment Date</label>
                            <input type="date" id="recruitment_date" name="recruitment_date">
                        </div>
                        <div class="form-group">
                            <label for="practice_start_year">Started Practice (Year)</label>
                            <input type="number" id="practice_start_year" name="practice_start_year" min="1950" max="<?php echo date('Y'); ?>" placeholder="e.g., <?php echo date('Y') - 5; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password*</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="chief_doctor_doctors.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Doctors</a>
                    <button type="reset" class="btn-reset"><i class="fas fa-undo"></i> Reset Form</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-user-md"></i> Add Doctor</button>
                </div>
            </form>
        </div>
    </main>
<script src="js/validation.js"></script>
</body>
</html>

