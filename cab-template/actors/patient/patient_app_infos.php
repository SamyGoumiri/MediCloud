<?php
session_start();
if(!isset($_SESSION["id_patient"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== 'patient') {
    session_destroy();
    header("Location: patient_auth_login.php");
    exit();
}

require_once '../../DB/connect.php';

if(!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    header("Location: patient_app.php");
    exit();
}

$appointment_id = intval($_GET['appointment_id']);
$id_patient = $_SESSION["id_patient"];

// Get appointment details - ensure it belongs to this patient
$query = "SELECT a.id_appointment, a.appointment_date, a.start_time, a.end_time, a.status,
                 d.id_doctor, d.first_name as doctor_first, d.last_name as doctor_last, d.speciality
          FROM appointment a
          JOIN doctor d ON a.id_doctor = d.id_doctor
          WHERE a.id_appointment = ? AND a.id_patient = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $appointment_id, $id_patient);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    header("Location: patient_app.php?error=appointment_not_found");
    exit();
}

$appointment = $result->fetch_assoc();

// Check if consultation exists
$consult_query = "SELECT c.diagnosis, c.treatment, c.fee, c.questionnaire, c.id_consult
                  FROM consultation c
                  WHERE c.id_appointment = ?";
$consult_stmt = $conn->prepare($consult_query);
$consult_stmt->bind_param("i", $appointment_id);
$consult_stmt->execute();
$consult_result = $consult_stmt->get_result();
$consultation = $consult_result->fetch_assoc();

// Check if feedback exists for this consultation
$feedback_exists = false;
if($consultation) {
    $feedback_stmt = $conn->prepare("SELECT id FROM consultation_feedback WHERE id_consultation = ?");
    $feedback_stmt->bind_param("i", $consultation['id_consult']);
    $feedback_stmt->execute();
    $feedback_result = $feedback_stmt->get_result();
    $feedback_exists = $feedback_result->num_rows > 0;
}

// Get patient name
$stmt_patient = $conn->prepare("SELECT first_name, last_name FROM patient WHERE id_patient = ?");
$stmt_patient->bind_param("i", $id_patient);
$stmt_patient->execute();
$result_patient = $stmt_patient->get_result();
$patient_data = $result_patient->fetch_assoc();
$patient_name = $patient_data['first_name'] . ' ' . $patient_data['last_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - HippoCare</title>
    <link rel="stylesheet" href="css/patient_style.css">
    <link rel="stylesheet" href="css/patient_forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .readonly-field {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 12px;
            min-height: 50px;
        }
        .readonly-field p {
            margin: 0;
            color: #495057;
            line-height: 1.6;
        }
        .info-message {
            background-color: #e7f3ff;
            border-left: 4px solid var(--primary-color);
            padding: 15px 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .info-message i {
            color: var(--primary-color);
            font-size: 1.5em;
        }
        .info-message p {
            margin: 0;
            color: #495057;
        }
    </style>
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
        <h1>Appointment Details</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="patient_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="patient_app.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="patient_consult.php"><i class="fas fa-stethoscope"></i> Consultations</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <article class="info-card">
            <section class="info-section">
                <h2><i class="fas fa-file-medical"></i> Appointment Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Appointment #:</label>
                        <p><?php echo sprintf('%04d', $appointment['id_appointment']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Status:</label>
                        <p>
                            <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="info-item">
                        <label>Date:</label>
                        <p><?php echo (new DateTime($appointment['appointment_date']))->format('d/m/Y'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Time:</label>
                        <p><?php echo htmlspecialchars($appointment['start_time'] . ' - ' . $appointment['end_time']); ?></p>
                    </div>
                </div>
            </section>

            <section class="info-section">
                <h2><i class="fas fa-user-md"></i> Doctor</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Doctor:</label>
                        <p>Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Speciality:</label>
                        <p><?php echo htmlspecialchars($appointment['speciality']); ?></p>
                    </div>
                </div>
            </section>

            <?php if($consultation && $appointment['status'] == 'completed'): ?>
                <section class="info-section">
                    <h2><i class="fas fa-notes-medical"></i> Consultation Details</h2>
                    
                    <div class="form-group">
                        <label><i class="fas fa-stethoscope"></i> Diagnosis:</label>
                        <div class="readonly-field">
                            <p><?php echo nl2br(htmlspecialchars($consultation['diagnosis'])); ?></p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-pills"></i> Treatment:</label>
                        <div class="readonly-field">
                            <p><?php echo nl2br(htmlspecialchars($consultation['treatment'])); ?></p>
                        </div>
                    </div>

                    <?php if(!empty($consultation['questionnaire'])): ?>
                        <div class="form-group">
                            <label><i class="fas fa-clipboard-list"></i> Notes/Questionnaire:</label>
                            <div class="readonly-field">
                                <p><?php echo nl2br(htmlspecialchars($consultation['questionnaire'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label><i class="fas fa-dollar-sign"></i> Consultation Fee:</label>
                        <div class="readonly-field">
                            <p><strong><?php echo number_format($consultation['fee'], 2); ?> DZD</strong></p>
                        </div>
                    </div>
                </section>
            <?php elseif($appointment['status'] == 'pending'): ?>
                <section class="info-section">
                    <div class="info-message">
                        <i class="fas fa-info-circle"></i>
                        <p>This appointment is pending. Consultation details will be available after the appointment is completed by the doctor.</p>
                    </div>
                </section>
            <?php elseif($appointment['status'] == 'canceled'): ?>
                <section class="info-section">
                    <div class="info-message">
                        <i class="fas fa-ban"></i>
                        <p>This appointment has been canceled.</p>
                    </div>
                </section>
            <?php elseif($appointment['status'] == 'missed'): ?>
                <section class="info-section">
                    <div class="info-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>This appointment was missed.</p>
                    </div>
                </section>
            <?php endif; ?>

            <div class="form-actions">
                <a href="patient_app.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Appointments
                </a>
                
                <?php if($appointment['status'] == 'pending'): ?>
                    <a href="patient_app_new.php?old_appointment_id=<?php echo $appointment['id_appointment']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Reschedule Appointment
                    </a>
                <?php elseif($appointment['status'] == 'completed' && $consultation): ?>
                    <?php if($feedback_exists): ?>
                        <a href="patient_feedback_view.php?appointment=<?php echo $appointment['id_appointment']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Rating
                        </a>
                        <a href="patient_feedback.php?appointment=<?php echo $appointment['id_appointment']; ?>&edit=1" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Edit Rating
                        </a>
                    <?php else: ?>
                        <a href="patient_feedback.php?appointment=<?php echo $appointment['id_appointment']; ?>" class="btn btn-primary">
                            <i class="fas fa-star"></i> Rate Consultation
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
    </main>

    <footer>
        <p>&copy; 2023-2026 HippoCare. All rights reserved.</p>
    </footer>
</body>
</html>
