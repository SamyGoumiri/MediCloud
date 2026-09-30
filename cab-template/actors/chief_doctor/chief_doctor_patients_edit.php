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

if(!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    header("Location: chief_doctor_patients.php");
    exit();
}

$patient_id = (int)$_GET['patient_id'];

$success_message = '';
$error_message = '';

$today = new DateTime('today');
$patient_birth_min = (clone $today)->modify('-100 years')->format('Y-m-d');
$patient_birth_max = (clone $today)->modify('-1 year')->format('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $last_name = trim($_POST["last_name"]);
    $first_name = trim($_POST["first_name"]);
    $phone = trim($_POST["phone"]);
    $birth_date = trim($_POST["birth_date"]);
    $email = trim($_POST["email"]);
    $national_id = trim($_POST["national_id"]);
    
    $street = trim($_POST["street"]);
    $city = trim($_POST["city"]);
    $region = trim($_POST["region"]);
    $postal_code = trim($_POST["postal_code"]);
    $country = trim($_POST["country"]);
    
    $birth_date_is_valid = true;
    $birth_dt = DateTime::createFromFormat('Y-m-d', $birth_date);
    $birth_dt_errors = DateTime::getLastErrors();
    if (!$birth_dt || !empty($birth_dt_errors['warning_count']) || !empty($birth_dt_errors['error_count'])) {
        $birth_date_is_valid = false;
    } else {
        $birth_dt->setTime(0, 0, 0);
        $min_dt = DateTime::createFromFormat('Y-m-d', $patient_birth_min);
        $max_dt = DateTime::createFromFormat('Y-m-d', $patient_birth_max);
        if ($birth_dt < $min_dt || $birth_dt > $max_dt) {
            $birth_date_is_valid = false;
        }
    }

    if (!$birth_date_is_valid) {
        $error_message = "Birth date must be between $patient_birth_min and $patient_birth_max.";
    }

    if (empty($error_message) && $national_id === '') {
        $error_message = "National ID is required.";
    }

    if (empty($error_message)) {
        $check_national_id = $conn->prepare("SELECT id_patient FROM patient WHERE national_id = ? AND id_patient != ? LIMIT 1");
        $check_national_id->bind_param("si", $national_id, $patient_id);
        $check_national_id->execute();
        if ($check_national_id->get_result()->num_rows > 0) {
            $error_message = "This National ID is already registered by another patient.";
        }
        $check_national_id->close();
    }

    if (empty($error_message)) {
        $check_email = $conn->prepare("SELECT id_patient FROM patient WHERE email = ? AND id_patient != ?");
        $check_email->bind_param("si", $email, $patient_id);
        $check_email->execute();

        if ($check_email->get_result()->num_rows > 0) {
            $error_message = "This email is already registered by another patient.";
        }
        $check_email->close();
    }

    if (empty($error_message)) {
        $conn->begin_transaction();
        try {
            $update_patient = $conn->prepare("UPDATE patient SET last_name = ?, first_name = ?, phone = ?, birth_date = ?, email = ?, national_id = ? WHERE id_patient = ?");
            $update_patient->bind_param("ssssssi", $last_name, $first_name, $phone, $birth_date, $email, $national_id, $patient_id);
            $update_patient->execute();
            
            if (!empty($street) && !empty($city)) {
                $check_address = $conn->prepare("SELECT id_address FROM patient_address WHERE id_patient = ?");
                $check_address->bind_param("i", $patient_id);
                $check_address->execute();
                $address_exists = $check_address->get_result()->num_rows > 0;
                $check_address->close();
                
                if ($address_exists) {
                    $update_address = $conn->prepare("UPDATE patient_address SET street = ?, city = ?, region = ?, postal_code = ?, country = ? WHERE id_patient = ?");
                    $update_address->bind_param("sssssi", $street, $city, $region, $postal_code, $country, $patient_id);
                    $update_address->execute();
                } else {
                    $insert_address = $conn->prepare("INSERT INTO patient_address (id_patient, street, city, region, postal_code, country) VALUES (?, ?, ?, ?, ?, ?)");
                    $insert_address->bind_param("isssss", $patient_id, $street, $city, $region, $postal_code, $country);
                    $insert_address->execute();
                }
            }
            
            $conn->commit();
            $success_message = "Patient information has been successfully updated!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating patient: " . $e->getMessage();
        }
    }
}

$patient_stmt = $conn->prepare("
    SELECT id_patient, last_name, first_name, email, phone, 
           birth_date, national_id 
    FROM patient
    WHERE id_patient = ?
    LIMIT 1
");
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient = $patient_stmt->get_result()->fetch_assoc();

if(!$patient) {
    header("Location: chief_doctor_patients.php");
    exit();
}

$address_stmt = $conn->prepare("SELECT * FROM patient_address WHERE id_patient = ?");
$address_stmt->bind_param("i", $patient_id);
$address_stmt->execute();
$address = $address_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient | HippoCare</title>
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
        <h1>Edit Patient</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="chief_doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="chief_doctor_doctors.php"><i class="fas fa-user-md"></i> Doctors</a>
                <a href="chief_doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="chief_doctor_patients.php" class="active"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="chief_doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="chief_doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <nav class="breadcrumb">
            <a href="chief_doctor_patients.php">Patients</a>
            <span class="separator">&gt;</span>
            <a href="chief_doctor_patients_infos.php?patient_id=<?php echo $patient_id; ?>"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></a>
            <span class="separator">&gt;</span>
            <span>Edit</span>
        </nav>

        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-user-edit"></i> Edit Patient Information</h2>
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
                            <input type="date" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($patient['birth_date']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="national_id">National ID <span class="readonly-notice">(Read-only)</span></label>
                            <input type="text" id="national_id" name="national_id" value="<?php echo htmlspecialchars($patient['national_id']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number*</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address*</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Address Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="street">Street</label>
                            <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($address['street'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($address['city'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="region">Region</label>
                            <input type="text" id="region" name="region" value="<?php echo htmlspecialchars($address['region'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="postal_code">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($address['postal_code'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($address['country'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="chief_doctor_patients_infos.php?patient_id=<?php echo $patient_id; ?>" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script src="js/validation.js"></script>
</body>
</html>
