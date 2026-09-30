<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('assistant', $conn);

if(!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    header("Location: assistant_app.php");
    exit();
}

$appointment_id = intval($_GET['appointment_id']);

// Get appointment details
$query = "SELECT a.id_appointment, a.appointment_date, a.start_time, a.end_time, a.status,
                 d.id_doctor, d.first_name as doctor_first, d.last_name as doctor_last,
                 p.id_patient, p.first_name as patient_first, p.last_name as patient_last
          FROM appointment a
          JOIN doctor d ON a.id_doctor = d.id_doctor
          JOIN patient p ON a.id_patient = p.id_patient
          WHERE a.id_appointment = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    header("Location: assistant_app.php?error=appointment_not_found");
    exit();
}

$appointment = $result->fetch_assoc();

// Check if consultation exists
$consult_query = "SELECT c.diagnosis, c.treatment, c.fee, c.questionnaire
                  FROM consultation c
                  WHERE c.id_appointment = ?";
$consult_stmt = $conn->prepare($consult_query);
$consult_stmt->bind_param("i", $appointment_id);
$consult_stmt->execute();
$consult_result = $consult_stmt->get_result();
$consultation = $consult_result->fetch_assoc();

// Get assistant name
$id_assistant = $_SESSION['id_assistant'];
$stmt_assistant = $conn->prepare("SELECT first_name, last_name FROM assistant WHERE id_assistant = ?");
$stmt_assistant->bind_param("i", $id_assistant);
$stmt_assistant->execute();
$result_assistant = $stmt_assistant->get_result();
$assistant_data = $result_assistant->fetch_assoc();
$assistant_name = $assistant_data['first_name'] . ' ' . $assistant_data['last_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - HippoCare</title>
    <link rel="stylesheet" href="css/assistant_style.css">
    <link rel="stylesheet" href="css/assistant_forms.css">
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
            border-left: 4px solid #8b5cf6;
            padding: 15px 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .info-message i {
            color: #8b5cf6;
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
            <a href="assistant_settings.php" title="Settings" class="settings-btn">
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
                <a href="assistant_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="assistant_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="assistant_app.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="assistant_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
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
                <h2><i class="fas fa-users"></i> Participants</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Doctor:</label>
                        <p>Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Patient:</label>
                        <p><?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Status:</label>
                        <p>
                            <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span>
                        </p>
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
                <a href="assistant_app.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Appointments
                </a>
                
                <?php if($appointment['status'] == 'pending'): ?>
                    <a href="assistant_app_new.php?old_appointment_id=<?php echo $appointment['id_appointment']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Reschedule Appointment
                    </a>
                <?php endif; ?>
            </div>
        </article>
    </main>

    <footer>
        <p>&copy; 2023-2026 HippoCare. All rights reserved.</p>
    </footer>
</body>
</html>
