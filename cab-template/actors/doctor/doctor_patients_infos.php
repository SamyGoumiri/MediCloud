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

if(!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    header("Location: doctor_patients.php");
    exit();
}

$patient_id = (int)$_GET['patient_id'];

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
    header("Location: doctor_patients.php");
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
    <title>Patient Information | HippoCare</title>
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
        <h1>Patient Information</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="doctor_patients.php" class="active"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <nav class="breadcrumb">
            <a href="doctor_patients.php">Patients</a>
            <span class="separator">&gt;</span>
            <span><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></span>
        </nav>

        <article class="patient-info-container">
            <header class="patient-header">
                <div class="patient-avatar-large">
                    <span><?php echo strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)); ?></span>
                </div>
                <div class="patient-header-info">
                    <h2><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>
                    <p class="patient-id"><i class="fas fa-id-card"></i> ID: PAT-<?php echo sprintf('%03d', $patient['id_patient']); ?></p>
                </div>
            </header>

            <section class="info-section">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>First Name:</label>
                        <p><?php echo htmlspecialchars($patient['first_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Last Name:</label>
                        <p><?php echo htmlspecialchars($patient['last_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Birth Date:</label>
                        <p>
                            <?php 
                                if(!empty($patient['birth_date'])) {
                                    echo (new DateTime($patient['birth_date']))->format('d/m/Y');
                                } else {
                                    echo 'Not provided';
                                }
                            ?>
                        </p>
                    </div>
                    <div class="info-item">
                        <label>Age:</label>
                        <p>
                            <?php 
                                if(!empty($patient['birth_date'])) {
                                    echo (new DateTime())->diff(new DateTime($patient['birth_date']))->y . ' years';
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </p>
                    </div>
                </div>
            </section>

            <section class="info-section">
                <h3><i class="fas fa-phone"></i> Contact Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Email:</label>
                        <p><a href="mailto:<?php echo htmlspecialchars($patient['email']); ?>"><?php echo htmlspecialchars($patient['email']); ?></a></p>
                    </div>
                    <div class="info-item">
                        <label>Phone:</label>
                        <p><?php echo htmlspecialchars($patient['phone']); ?></p>
                    </div>
                </div>
            </section>

            <section class="info-section">
                <h3><i class="fas fa-id-card"></i> Identification</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>National ID:</label>
                        <p><?php echo !empty($patient['national_id']) ? htmlspecialchars($patient['national_id']) : 'Not provided'; ?></p>
                    </div>
                </div>
            </section>

            <?php if($address): ?>
                <section class="info-section">
                    <h3><i class="fas fa-home"></i> Address</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Street:</label>
                            <p><?php echo htmlspecialchars($address['street'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>City:</label>
                            <p><?php echo htmlspecialchars($address['city'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Region:</label>
                            <p><?php echo htmlspecialchars($address['region'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Postal Code:</label>
                            <p><?php echo htmlspecialchars($address['postal_code'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Country:</label>
                            <p><?php echo htmlspecialchars($address['country'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <footer class="action-buttons">
                <a href="doctor_patients.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Patients
                </a>
                <a href="doctor_patients_medical_records.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary">
                    <i class="fas fa-notes-medical"></i> Medical Records
                </a>
                <a href="doctor_patients_edit.php?patient_id=<?php echo $patient_id; ?>" class="btn-primary">
                    <i class="fas fa-edit"></i> Edit Patient
                </a>
            </footer>
        </article>
    </main>


<script src="js/validation.js"></script>
</body>
</html>
