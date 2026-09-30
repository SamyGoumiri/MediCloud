<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('assistant', $conn);

$id_assistant = $_SESSION["id_assistant"];
$stmt = $conn->prepare("SELECT last_name, first_name, id_doctor FROM assistant WHERE id_assistant = ?");
$stmt->bind_param("i", $id_assistant);
$stmt->execute();
$assistant_data = $stmt->get_result()->fetch_assoc();
$assistant_name = $assistant_data['first_name'] . ' ' . $assistant_data['last_name'];

// Get doctor info if assigned
$doctor_name = 'Not Assigned';
if ($assistant_data['id_doctor']) {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM doctor WHERE id_doctor = ?");
    $stmt->bind_param("i", $assistant_data['id_doctor']);
    $stmt->execute();
    $doctor_result = $stmt->get_result();
    if ($doctor_result->num_rows > 0) {
        $doctor_data = $doctor_result->fetch_assoc();
        $doctor_name = $doctor_data['first_name'] . ' ' . $doctor_data['last_name'];
    }
}

if(!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    header("Location: assistant_patients.php");
    exit();
}

$patient_id = (int)$_GET['patient_id'];
$error_message = '';
$success_message = '';

// Fetch patient info
$patient_stmt = $conn->prepare("SELECT id_patient, last_name, first_name FROM patient WHERE id_patient = ?");
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient = $patient_stmt->get_result()->fetch_assoc();

if(!$patient) {
    header("Location: assistant_patients.php");
    exit();
}

$patient_name = $patient['first_name'] . ' ' . $patient['last_name'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $medical_history = trim($_POST["medical_history"] ?? '');
    $current_treatment = trim($_POST["current_treatment"] ?? '');
    $medical_notes = trim($_POST["medical_notes"] ?? '');
    
    // Check if medical record exists
    $check_stmt = $conn->prepare("SELECT id_record FROM medical_record WHERE id_patient = ?");
    $check_stmt->bind_param("i", $patient_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $update_stmt = $conn->prepare("UPDATE medical_record SET medical_history = ?, current_treatment = ?, medical_notes = ? WHERE id_patient = ?");
        $update_stmt->bind_param("sssi", $medical_history, $current_treatment, $medical_notes, $patient_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Medical records updated successfully!";
        } else {
            $error_message = "Error updating medical records.";
        }
        $update_stmt->close();
    } else {
        // Create new record
        $insert_stmt = $conn->prepare("INSERT INTO medical_record (id_patient, medical_history, current_treatment, medical_notes) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("isss", $patient_id, $medical_history, $current_treatment, $medical_notes);
        
        if ($insert_stmt->execute()) {
            $success_message = "Medical records created successfully!";
        } else {
            $error_message = "Error creating medical records.";
        }
        $insert_stmt->close();
    }
}

// Fetch existing medical records
$record_stmt = $conn->prepare("SELECT medical_history, current_treatment, medical_notes FROM medical_record WHERE id_patient = ?");
$record_stmt->bind_param("i", $patient_id);
$record_stmt->execute();
$medical_record = $record_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - <?php echo htmlspecialchars($patient_name); ?> | HippoCare</title>
    <link rel="stylesheet" href="css/assistant_style.css">
    <link rel="stylesheet" href="css/assistant_forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="js/validation-errors.css">
    <style>
        .textarea-field { min-height: 100px; }
    </style>
</head>
<body>
    <div class="header-banner">
        <div class="logo-container">
            <a href="assistant_home.php" class="logo-link">
                <h1 class="logo-text">HippoCare</h1>
            </a>
        </div>
        <div class="header-actions">
            <span class="user-name">
                <span class="name-part">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($assistant_name); ?>
                </span>
                <span class="date-part">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date("d F Y"); ?>
                </span>
            </span>
            <a href="../../DB/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    <div class="main-container">
        <aside class="sidebar">
            <nav class="nav-menu">
                <a href="assistant_home.php" class="nav-item">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="assistant_patients.php" class="nav-item">
                    <i class="fas fa-users"></i> Patients
                </a>
                <a href="assistant_app.php" class="nav-item">
                    <i class="fas fa-calendar"></i> Appointments
                </a>
                <a href="assistant_schedule.php" class="nav-item">
                    <i class="fas fa-clock"></i> Schedule
                </a>
                <a href="assistant_settings.php" class="nav-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </nav>
        </aside>

        <main class="content">
            <div class="breadcrumb">
                <a href="assistant_patients.php">Patients</a>
                <span>/</span>
                <a href="assistant_patients_infos.php?patient_id=<?php echo $patient_id; ?>">
                    <?php echo htmlspecialchars($patient_name); ?>
                </a>
                <span>/</span>
                <span>Medical Records</span>
            </div>

            <article class="settings-block">
                <header class="settings-block-header">
                    <h2><i class="fas fa-file-medical"></i> Medical Records</h2>
                    <p>Patient: <?php echo htmlspecialchars($patient_name); ?></p>
                </header>

                <?php if($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="settings-form">
                    <div class="form-group">
                        <label for="medical_history">Medical History</label>
                        <textarea id="medical_history" name="medical_history" class="textarea-field" placeholder="Past medical conditions, surgeries, allergies, etc.">
<?php echo htmlspecialchars($medical_record['medical_history'] ?? ''); ?>
                        </textarea>
                    </div>

                    <div class="form-group">
                        <label for="current_treatment">Current Treatment</label>
                        <textarea id="current_treatment" name="current_treatment" class="textarea-field" placeholder="Current medications, ongoing treatments, therapies, etc.">
<?php echo htmlspecialchars($medical_record['current_treatment'] ?? ''); ?>
                        </textarea>
                    </div>

                    <div class="form-group">
                        <label for="medical_notes">Medical Notes</label>
                        <textarea id="medical_notes" name="medical_notes" class="textarea-field" placeholder="Additional medical notes and observations">
<?php echo htmlspecialchars($medical_record['medical_notes'] ?? ''); ?>
                        </textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Medical Records
                        </button>
                        <a href="assistant_patients_infos.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Patient Info
                        </a>
                    </div>
                </form>
            </article>
        </main>
    </div>

<script src="js/validation.js"></script>
</body>
</html>
