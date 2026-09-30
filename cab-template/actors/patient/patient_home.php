<?php
session_start();
if(!isset($_SESSION["id_patient"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== 'patient') {
    session_destroy();
    header("Location: patient_auth_login.php");
    exit();
}

require_once '../../DB/connect.php';

$id_patient = $_SESSION["id_patient"];

$stmt = $conn->prepare("SELECT last_name, first_name FROM patient WHERE id_patient = ?");
$stmt->bind_param("i", $id_patient);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    session_destroy();
    header("Location: patient_auth_login.php?error=invalid_session");
    exit();
}

$patient_data = $result->fetch_assoc();
$patient_name = $patient_data['first_name'] . ' ' . $patient_data['last_name'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointment WHERE id_patient = ?");
$stmt->bind_param("i", $id_patient);
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['total'];

$today = date("Y-m-d");

$stmt = $conn->prepare("SELECT COUNT(*) as today FROM appointment WHERE appointment_date = ? AND status = 'pending' AND id_patient = ?");
$stmt->bind_param("si", $today, $id_patient);
$stmt->execute();
$today_appointments = $stmt->get_result()->fetch_assoc()['today'];

// Get recent appointments
$stmt = $conn->prepare("
    SELECT a.id_appointment, a.start_time, a.status,
           d.first_name as doctor_first, d.last_name as doctor_last
    FROM appointment a
    JOIN doctor d ON a.id_doctor = d.id_doctor
    WHERE a.appointment_date = ? AND a.id_patient = ?
    ORDER BY a.start_time ASC
    LIMIT 5
");
$stmt->bind_param("si", $today, $id_patient);
$stmt->execute();
$today_appointments_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Home | HippoCare</title>
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
        <h1>Patient Management Dashboard</h1>
    </header> 
    
    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="patient_home.php" class="active"><i class="fas fa-home"></i> Home</a>
                <a href="patient_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="patient_consult.php"><i class="fas fa-stethoscope"></i> Consultations</a>
            </div>
        </fieldset>
    </nav>
    
    <main>
        <section class="welcome-message">
            <div class="welcome-content">
                <h2>Welcome back, <?php echo htmlspecialchars($patient_data['first_name']); ?></h2>
                <p>Manage your health appointments and records</p>
            </div>
        </section>

        <section class="stats-container">
            <article class="stat-box">
                <div class="stat-icon"><i class="fas fa-calendar"></i></div>
                <div class="stat-value"><?php echo $total_appointments; ?></div>
                <div class="stat-label">Total Appointments</div>
                <a href="patient_app.php" class="stat-link">View All</a>
            </article>
            <article class="stat-box highlight">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-value"><?php echo $today_appointments; ?></div>
                <div class="stat-label">Today's Appointments</div>
                <a href="patient_app.php" class="stat-link">View All</a>
            </article>
            <article class="stat-box">
                <div class="stat-icon"><i class="fas fa-user-md"></i></div>
                <div class="stat-value">Book</div>
                <div class="stat-label">New Appointment</div>
                <a href="patient_app_new.php" class="stat-link">Book Now</a>
            </article>
        </section>

        <?php if(count($today_appointments_list) > 0): ?>
        <section class="appointments-list">
            <h3>Today's Schedule</h3>
            <?php foreach($today_appointments_list as $apt): ?>
            <div class="appointment-item">
                <div class="appointment-time">
                    <strong><?php echo substr($apt['start_time'], 0, 5); ?></strong>
                </div>
                <div class="appointment-details">
                    <p class="appointment-patient"><strong>Dr. <?php echo htmlspecialchars($apt['doctor_first'] . ' ' . $apt['doctor_last']); ?></strong></p>
                </div>
                <div class="appointment-status">
                    <span class="status-badge status-<?php echo strtolower($apt['status']); ?>"><?php echo ucfirst($apt['status']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>
    </main>

</body>
</html>
