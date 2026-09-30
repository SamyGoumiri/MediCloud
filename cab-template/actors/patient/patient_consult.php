<?php
session_start();
if(!isset($_SESSION["id_patient"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== 'patient') {
    session_destroy();
    header("Location: patient_auth_login.php");
    exit();
}

require_once '../../DB/connect.php';

$id_patient = $_SESSION["id_patient"];

// Get patient info
$stmt = $conn->prepare("SELECT last_name, first_name FROM patient WHERE id_patient = ?");
$stmt->bind_param("i", $id_patient);
$stmt->execute();
$patient_data = $stmt->get_result()->fetch_assoc();
$patient_name = $patient_data['first_name'] . ' ' . $patient_data['last_name'];

// Get only completed consultations for this patient with feedback status
$stmt = $conn->prepare("
    SELECT a.id_appointment, a.appointment_date, a.start_time, a.status,
           d.id_doctor, d.first_name as doctor_first, d.last_name as doctor_last, d.speciality,
           c.id_consult, 
           cf.id as feedback_id
    FROM appointment a
    JOIN doctor d ON a.id_doctor = d.id_doctor
    LEFT JOIN consultation c ON a.id_appointment = c.id_appointment
    LEFT JOIN consultation_feedback cf ON c.id_consult = cf.id_consultation
    WHERE a.id_patient = ? AND a.status = 'completed'
    ORDER BY a.appointment_date DESC, a.start_time DESC
");
$stmt->bind_param("i", $id_patient);
$stmt->execute();
$consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations | HippoCare</title>
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
        <h1>Consultation History</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="patient_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="patient_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="patient_consult.php" class="active"><i class="fas fa-stethoscope"></i> Consultations</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <section class="list-container">
            <div class="section-header">
                <h2><i class="fas fa-stethoscope"></i> My Consultations (<?php echo count($consultations); ?>)</h2>
            </div>
            
            <?php if (count($consultations) > 0): ?>
                <div class="items-list">
                    <?php foreach ($consultations as $consultation): 
                        $appointment_date = new DateTime($consultation['appointment_date']);
                        $status_class = strtolower($consultation['status']);
                    ?>
                    <article class="item-card">
                        <div class="item-content">
                            <h3><i class="fas fa-calendar-check"></i> Consultation #<?php echo sprintf('%04d', $consultation['id_appointment']); ?></h3>
                            <p class="item-meta">
                                <span><i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($consultation['doctor_first'] . ' ' . $consultation['doctor_last']); ?></span>
                                <span><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($consultation['speciality']); ?></span>
                            </p>
                            <p class="item-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo $appointment_date->format('d/m/Y'); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($consultation['start_time']); ?></span>
                            </p>
                        </div>
                        <div class="item-actions">
                            <a href="patient_app_infos.php?appointment_id=<?php echo $consultation['id_appointment']; ?>" class="btn btn-secondary btn-icon" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if($consultation['feedback_id']): ?>
                                <a href="patient_feedback_view.php?appointment=<?php echo $consultation['id_appointment']; ?>" class="btn btn-primary btn-icon" title="View Rating">
                                    <i class="fas fa-star"></i>
                                </a>
                                <a href="patient_feedback.php?appointment=<?php echo $consultation['id_appointment']; ?>&edit=1" class="btn btn-secondary btn-icon" title="Edit Rating">
                                    <i class="fas fa-edit"></i>
                                </a>
                            <?php else: ?>
                                <a href="patient_feedback.php?appointment=<?php echo $consultation['id_appointment']; ?>" class="btn btn-primary btn-icon" title="Rate Consultation">
                                    <i class="fas fa-star"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-stethoscope"></i>
                    <p>No consultation history yet</p>
                    <p style="color: #666; font-size: 0.9rem;">Completed appointments will appear here</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    
</body>
</html>
