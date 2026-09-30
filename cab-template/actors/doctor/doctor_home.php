<?php
session_start();
if(!isset($_SESSION["id_doctor"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== 'doctor') {
    session_destroy();
    header("Location: doctor_auth_login.php");
    exit();
}

require_once '../../DB/connect.php';

$id_doctor = $_SESSION["id_doctor"];

$stmt = $conn->prepare("SELECT last_name, first_name, role FROM doctor WHERE id_doctor = ? AND role = 'doctor'");
$stmt->bind_param("i", $id_doctor);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    session_destroy();
    header("Location: doctor_auth_login.php?error=invalid_session");
    exit();
}

$doctor_data = $result->fetch_assoc();
$doctor_name = $doctor_data['first_name'] . ' ' . $doctor_data['last_name'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM assistant");
$stmt->execute();
$total_assistants = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM patient");
$stmt->execute();
$total_patients = $stmt->get_result()->fetch_assoc()['total'];

$today = date("Y-m-d");

$stmt = $conn->prepare("SELECT COUNT(*) as today FROM appointment WHERE appointment_date = ? AND status = 'pending' AND id_doctor = ?");
$stmt->bind_param("si", $today, $id_doctor);
$stmt->execute();
$today_appointments = $stmt->get_result()->fetch_assoc()['today'];

// Get recent appointments
$stmt = $conn->prepare("
    SELECT a.id_appointment, a.start_time, a.status,
           d.first_name as doctor_first, d.last_name as doctor_last,
           p.first_name as patient_first, p.last_name as patient_last
    FROM appointment a
    JOIN doctor d ON a.id_doctor = d.id_doctor
    JOIN patient p ON a.id_patient = p.id_patient
    WHERE a.appointment_date = ? AND a.id_doctor = ?
    ORDER BY a.start_time ASC
    LIMIT 5
");
$stmt->bind_param("si", $today, $id_doctor);
$stmt->execute();
$today_appointments_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Home | HippoCare</title>
    <link rel="stylesheet" href="css/doctor_style.css">
    <link rel="stylesheet" href="css/doctor_forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <h1>Doctor Management Dashboard</h1>
    </header> 
    
    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="doctor_home.php" class="active"><i class="fas fa-home"></i> Home</a>
                <a href="doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>
    
    <main>
        <section class="welcome-message">
            <div class="welcome-content">
                <h2>Welcome back, Dr. <?php echo htmlspecialchars($doctor_data['first_name']); ?></h2>
                <p>Manage your practice efficiently</p>
            </div>
        </section>

        <section class="stats-container">
            <article class="stat-box">
                <div class="stat-icon"><i class="fas fa-user-nurse"></i></div>
                <div class="stat-value"><?php echo $total_assistants; ?></div>
                <div class="stat-label">Assistants</div>
                <a href="doctor_assistants.php" class="stat-link">View All</a>
            </article>
            <article class="stat-box">
                <div class="stat-icon"><i class="fas fa-user-injured"></i></div>
                <div class="stat-value"><?php echo $total_patients; ?></div>
                <div class="stat-label">Patients</div>
                <a href="doctor_patients.php" class="stat-link">View All</a>
            </article>
            <article class="stat-box highlight">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-value"><?php echo $today_appointments; ?></div>
                <div class="stat-label">Today's Appointments</div>
                <a href="doctor_app.php" class="stat-link">View All</a>
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
                    <p class="appointment-patient"><strong><?php echo htmlspecialchars($apt['patient_first'] . ' ' . $apt['patient_last']); ?></strong></p>
                    <p class="appointment-doctor">Dr. <?php echo htmlspecialchars($apt['doctor_first'] . ' ' . substr($apt['doctor_last'], 0, 1)); ?></p>
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
