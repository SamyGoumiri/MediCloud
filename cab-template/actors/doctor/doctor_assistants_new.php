<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('doctor', $conn);

$id_doctor = $_SESSION["id_doctor"];
$stmt = $conn->prepare("SELECT last_name, first_name FROM doctor WHERE id_doctor = ?");
$stmt->bind_param("i", $id_doctor);
$stmt->execute();
$doctor_data = $stmt->get_result()->fetch_assoc();
$doctor_name = $doctor_data['first_name'] . ' ' . $doctor_data['last_name'];

$success_message = '';
$error_message = '';

// Get list of doctors for dropdown
$doctors = [];
$stmt = $conn->prepare("SELECT id_doctor, first_name, last_name FROM doctor ORDER BY last_name");
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

$today = new DateTime('today');
$assistant_birth_min = (clone $today)->modify('-100 years')->format('Y-m-d');
$assistant_birth_max = (clone $today)->modify('-18 years')->format('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $last_name = trim($_POST["last_name"]);
    $first_name = trim($_POST["first_name"]);
    $phone = trim($_POST["phone"]);
    $birth_date = trim($_POST["birth_date"]);
    $email = trim($_POST["email"]);
    $national_id = trim($_POST["national_id"]);
    $national_id = ($national_id === '') ? NULL : $national_id;
    $recruitment_date = !empty($_POST["recruitment_date"]) ? $_POST["recruitment_date"] : NULL;
    $id_doctor = intval($_POST["id_doctor"]);
    $password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);
    
    $birth_date_is_valid = true;
    $birth_dt = DateTime::createFromFormat('Y-m-d', $birth_date);
    $birth_dt_errors = DateTime::getLastErrors();
    if (!$birth_dt || !empty($birth_dt_errors['warning_count']) || !empty($birth_dt_errors['error_count'])) {
        $birth_date_is_valid = false;
    } else {
        $birth_dt->setTime(0, 0, 0);
        $min_dt = DateTime::createFromFormat('Y-m-d', $assistant_birth_min);
        $max_dt = DateTime::createFromFormat('Y-m-d', $assistant_birth_max);
        if ($birth_dt < $min_dt || $birth_dt > $max_dt) {
            $birth_date_is_valid = false;
        }
    }

    if (!$birth_date_is_valid) {
        $error_message = "Birth date must make the assistant between 18 and 100 years old.";
    }

    if (empty($error_message)) {
        $check_email = $conn->prepare("SELECT id_assistant FROM assistant WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();

        if ($check_email->get_result()->num_rows > 0) {
            $error_message = "This email is already registered. Please use a different email.";
        }
        $check_email->close();
    }

    if (empty($error_message) && $national_id !== NULL) {
        $check_national_id = $conn->prepare("SELECT id_assistant FROM assistant WHERE national_id = ? LIMIT 1");
        $check_national_id->bind_param("s", $national_id);
        $check_national_id->execute();
        if ($check_national_id->get_result()->num_rows > 0) {
            $error_message = "This National ID is already registered. Please use a different National ID.";
        }
        $check_national_id->close();
    }

    if (empty($error_message)) {
        $conn->begin_transaction();
        
        $insert_assistant = $conn->prepare("INSERT INTO assistant (last_name, first_name, phone, birth_date, email, national_id, recruitment_date, password, id_doctor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_assistant->bind_param("ssssssssi", $last_name, $first_name, $phone, $birth_date, $email, $national_id, $recruitment_date, $password, $id_doctor);
        $insert_assistant->execute();
        
        $conn->commit();
        $success_message = "Assistant has been successfully added!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Assistant | HippoCare</title>
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
        <h1>Add New Assistant</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="doctor_assistants.php" class="active"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-user-plus"></i> Add New Assistant</h2>
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
                            <input type="date" id="birth_date" name="birth_date" required>
                        </div>
                        <div class="form-group">
                            <label for="national_id">National ID</label>
                            <input type="text" id="national_id" name="national_id">
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
                            <label for="id_doctor">Assigned Doctor*</label>
                            <select id="id_doctor" name="id_doctor" required>
                                <option value="">Select a doctor</option>
                                <?php foreach($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['id_doctor']; ?>">
                                        Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="recruitment_date">Recruitment Date</label>
                            <input type="date" id="recruitment_date" name="recruitment_date">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="password">Password*</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="doctor_assistants.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Assistants</a>
                    <button type="reset" class="btn-reset"><i class="fas fa-undo"></i> Reset Form</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-user-plus"></i> Add Assistant</button>
                </div>
            </form>
        </div>
    </main>
<script src="js/validation.js"></script>
</body>
</html>

