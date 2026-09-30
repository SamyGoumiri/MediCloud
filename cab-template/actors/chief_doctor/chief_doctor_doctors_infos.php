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

$doctor_stmt = $conn->prepare("
    SELECT id_doctor, last_name, first_name, email, phone, 
           birth_date, national_id, speciality, consultation_fee, 
           recruitment_date, practice_start_year
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
    <title>Doctor Information | HippoCare</title>
    <link rel="stylesheet" href="css/chief_doctor_style.css">
    <link rel="stylesheet" href="css/chief_doctor_forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <h1>Doctor Information</h1>
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
            <span><?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></span>
        </nav>

        <article class="doctor-info-container">
            <header class="doctor-header">
                <div class="doctor-avatar-large">
                    <span><?php echo strtoupper(substr($doctor['first_name'], 0, 1) . substr($doctor['last_name'], 0, 1)); ?></span>
                </div>
                <div class="doctor-header-info">
                    <h2><?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h2>
                    <p class="doctor-id"><i class="fas fa-id-card"></i> ID: DR-<?php echo sprintf('%03d', $doctor['id_doctor']); ?></p>
                </div>
            </header>

            <section class="info-section">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>First Name:</label>
                        <p><?php echo htmlspecialchars($doctor['first_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Last Name:</label>
                        <p><?php echo htmlspecialchars($doctor['last_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Birth Date:</label>
                        <p>
                            <?php 
                                if(!empty($doctor['birth_date'])) {
                                    echo (new DateTime($doctor['birth_date']))->format('d/m/Y');
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
                                if(!empty($doctor['birth_date'])) {
                                    echo (new DateTime())->diff(new DateTime($doctor['birth_date']))->y . ' years';
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
                        <p><a href="mailto:<?php echo htmlspecialchars($doctor['email']); ?>"><?php echo htmlspecialchars($doctor['email']); ?></a></p>
                    </div>
                    <div class="info-item">
                        <label>Phone:</label>
                        <p><?php echo htmlspecialchars($doctor['phone']); ?></p>
                    </div>
                </div>
            </section>

            <section class="info-section">
                <h3><i class="fas fa-briefcase"></i> Professional Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Speciality:</label>
                        <p><?php echo !empty($doctor['speciality']) ? htmlspecialchars($doctor['speciality']) : 'Not specified'; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Consultation Fee:</label>
                        <p><?php echo !empty($doctor['consultation_fee']) ? number_format($doctor['consultation_fee'], 2) . ' DZD' : 'Not set'; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Practice Start Year:</label>
                        <p><?php echo !empty($doctor['practice_start_year']) ? htmlspecialchars($doctor['practice_start_year']) : 'Not provided'; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Recruitment Date:</label>
                        <p>
                            <?php 
                                if(!empty($doctor['recruitment_date'])) {
                                    echo (new DateTime($doctor['recruitment_date']))->format('d/m/Y');
                                } else {
                                    echo 'Not provided';
                                }
                            ?>
                        </p>
                    </div>
                </div>
            </section>

            <section class="info-section">
                <h3><i class="fas fa-id-card"></i> Identification</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>National ID:</label>
                        <p><?php echo !empty($doctor['national_id']) ? htmlspecialchars($doctor['national_id']) : 'Not provided'; ?></p>
                    </div>
                </div>
            </section>

            <footer class="action-buttons">
                <a href="chief_doctor_doctors.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Doctors
                </a>
                <a href="chief_doctor_doctors_edit.php?doctor_id=<?php echo $doctor_id; ?>" class="btn-primary">
                    <i class="fas fa-edit"></i> Edit Doctor
                </a>
            </footer>
        </article>
    </main>
</body>
</html>
