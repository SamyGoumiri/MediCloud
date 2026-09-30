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

if(!isset($_GET['doctor_id']) || !is_numeric($_GET['doctor_id'])) {
    header("Location: chief_doctor_doctors.php");
    exit();
}

$doctor_id = (int)$_GET['doctor_id'];

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
        $birth_year = intval(substr($birth_date, 0, 4));
        $min_practice_year = $birth_year + 20;
        
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

    if (empty($error_message)) {
        $check_national_id = $conn->prepare("SELECT id_doctor FROM doctor WHERE national_id = ? AND id_doctor != ? LIMIT 1");
        $check_national_id->bind_param("si", $national_id, $doctor_id);
        $check_national_id->execute();
        if ($check_national_id->get_result()->num_rows > 0) {
            $error_message = "This National ID is already registered by another doctor.";
        }
        $check_national_id->close();
    }

    if (empty($error_message)) {
        $check_email = $conn->prepare("SELECT id_doctor FROM doctor WHERE email = ? AND id_doctor != ?");
        $check_email->bind_param("si", $email, $doctor_id);
        $check_email->execute();

        if ($check_email->get_result()->num_rows > 0) {
            $error_message = "This email is already registered by another doctor.";
        }
        $check_email->close();
    }

    if (empty($error_message)) {
        $conn->begin_transaction();
        try {
            $update_doctor = $conn->prepare("UPDATE doctor SET last_name = ?, first_name = ?, phone = ?, birth_date = ?, email = ?, national_id = ?, speciality = ?, consultation_fee = ?, recruitment_date = ?, practice_start_year = ? WHERE id_doctor = ?");
            $update_doctor->bind_param("sssssssdsi", $last_name, $first_name, $phone, $birth_date, $email, $national_id, $speciality, $consultation_fee, $recruitment_date, $practice_start_year, $doctor_id);
            $update_doctor->execute();
            
            $conn->commit();
            $success_message = "Doctor information has been successfully updated!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating doctor: " . $e->getMessage();
        }
    }
}

$doctor_stmt = $conn->prepare("
    SELECT id_doctor, last_name, first_name, email, phone, 
           birth_date, national_id, speciality, consultation_fee, 
           recruitment_date, practice_start_year, role
    FROM doctor
    WHERE id_doctor = ?
    LIMIT 1
");
$doctor_stmt->bind_param("i", $doctor_id);
$doctor_stmt->execute();
$doctor = $doctor_stmt->get_result()->fetch_assoc();

if(!$doctor) {
    header("Location: chief_doctor_doctors.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor | HippoCare</title>
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
        <h1>Edit Doctor</h1>
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
        <nav class="breadcrumb">
            <a href="chief_doctor_doctors.php">Doctors</a>
            <span class="separator">&gt;</span>
            <a href="chief_doctor_doctors_infos.php?doctor_id=<?php echo $doctor_id; ?>"><?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></a>
            <span class="separator">&gt;</span>
            <span>Edit</span>
        </nav>

        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-user-edit"></i> Edit Doctor Information</h2>
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
                            <input type="date" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($doctor['birth_date']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="national_id">National ID <span class="readonly-notice">(Read-only)</span></label>
                            <input type="text" id="national_id" name="national_id" value="<?php echo htmlspecialchars($doctor['national_id']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number*</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address*</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="speciality">Speciality</label>
                            <select id="speciality" name="speciality">
                                <option value="">Select a speciality</option>
                                <option value="General Practitioner" <?php echo ($doctor['speciality'] ?? '') === 'General Practitioner' ? 'selected' : ''; ?>>General Practitioner</option>
                                <option value="Cardiologist" <?php echo ($doctor['speciality'] ?? '') === 'Cardiologist' ? 'selected' : ''; ?>>Cardiologist</option>
                                <option value="Pediatrician" <?php echo ($doctor['speciality'] ?? '') === 'Pediatrician' ? 'selected' : ''; ?>>Pediatrician</option>
                                <option value="Dermatologist" <?php echo ($doctor['speciality'] ?? '') === 'Dermatologist' ? 'selected' : ''; ?>>Dermatologist</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="consultation_fee">Consultation Fee (DZD)</label>
                            <input type="number" id="consultation_fee" name="consultation_fee" step="0.01" min="0" value="<?php echo htmlspecialchars($doctor['consultation_fee'] ?? ''); ?>" placeholder="e.g., 3000.00">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="recruitment_date">Recruitment Date</label>
                            <input type="date" id="recruitment_date" name="recruitment_date" value="<?php echo htmlspecialchars($doctor['recruitment_date'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="practice_start_year">Started Practice (Year)</label>
                            <input type="number" id="practice_start_year" name="practice_start_year" min="1950" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($doctor['practice_start_year'] ?? ''); ?>" placeholder="e.g., <?php echo date('Y') - 5; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="chief_doctor_doctors_infos.php?doctor_id=<?php echo $doctor_id; ?>" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script src="js/validation.js"></script>
</body>
</html>
