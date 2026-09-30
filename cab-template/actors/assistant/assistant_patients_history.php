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

// Get patient info
$patient_stmt = $conn->prepare("
    SELECT id_patient, last_name, first_name, email, phone, birth_date 
    FROM patient
    WHERE id_patient = ?
    LIMIT 1
");
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient = $patient_stmt->get_result()->fetch_assoc();

if(!$patient) {
    header("Location: assistant_patients.php");
    exit();
}

// Pagination settings
$items_per_page = 15;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Count total appointments for this patient
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointment WHERE id_patient = ?");
$count_stmt->bind_param("i", $patient_id);
$count_stmt->execute();
$total_appointments = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_appointments / $items_per_page);

// Get all appointments for this patient with doctor info
$appointments_stmt = $conn->prepare("
    SELECT a.id_appointment, a.appointment_date, a.start_time, a.end_time, a.status,
           d.id_doctor, d.first_name as doctor_first, d.last_name as doctor_last,
           c.diagnosis, c.treatment, c.questionnaire
    FROM appointment a
    JOIN doctor d ON a.id_doctor = d.id_doctor
    LEFT JOIN consultation c ON a.id_appointment = c.id_appointment
    WHERE a.id_patient = ?
    ORDER BY a.appointment_date DESC, a.start_time DESC
    LIMIT ? OFFSET ?
");
$appointments_stmt->bind_param("iii", $patient_id, $items_per_page, $offset);
$appointments_stmt->execute();
$appointments = $appointments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate patient age
$birth_date = new DateTime($patient['birth_date']);
$age = (new DateTime())->diff($birth_date)->y;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient History | HippoCare</title>
    <link rel="stylesheet" href="css/assistant_style.css">
    <link rel="stylesheet" href="css/assistant_forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <h1>Patient History</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="assistant_home.php"><i class="fas fa-home"></i> Home</a>
                
                <a href="assistant_patients.php" class="active"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="assistant_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="assistant_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <nav class="breadcrumb">
            <a href="assistant_home.php"><i class="fas fa-home"></i> Home</a>
            <span class="separator">/</span>
            <a href="assistant_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
            <span class="separator">/</span>
            <span class="current"><i class="fas fa-history"></i> Patient History</span>
        </nav>

        <div class="patient-info-header">
            <h2>
                <i class="fas fa-user"></i>
                <?php echo htmlspecialchars($patient['last_name'] . ' ' . $patient['first_name']); ?>
            </h2>
            <div class="patient-meta">
                <span>
                    <i class="fas fa-birthday-cake"></i>
                    <?php echo $birth_date->format('d/m/Y') . ' (' . $age . ' years)'; ?>
                </span>
                <span>
                    <i class="fas fa-phone"></i>
                    <?php echo htmlspecialchars($patient['phone']); ?>
                </span>
                <span>
                    <i class="fas fa-envelope"></i>
                    <?php echo htmlspecialchars($patient['email']); ?>
                </span>
                <span>
                    <i class="fas fa-id-card"></i>
                    PAT-<?php echo sprintf('%03d', $patient['id_patient']); ?>
                </span>
            </div>
        </div>

        <?php
        // Calculate statistics
        $completed = 0;
        $pending = 0;
        $canceled = 0;
        $missed = 0;
        foreach($appointments as $app) {
            switch($app['status']) {
                case 'completed': $completed++; break;
                case 'pending': $pending++; break;
                case 'canceled': $canceled++; break;
                case 'missed': $missed++; break;
            }
        }
        ?>

        <div class="stats-summary">
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_appointments; ?></span>
                <span class="stat-label">Total Appointments</span>
            </div>
            <div class="stat-card">
                <span class="stat-number success"><?php echo $completed; ?></span>
                <span class="stat-label">Completed</span>
            </div>
            <div class="stat-card">
                <span class="stat-number primary"><?php echo $pending; ?></span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-card">
                <span class="stat-number gray"><?php echo $canceled; ?></span>
                <span class="stat-label">Canceled</span>
            </div>
        </div>

        <?php if(count($appointments) > 0): ?>
            <div class="history-list">
                <?php foreach($appointments as $appointment): 
                    $status_class = $appointment['status'];
                    $status_icon = [
                        'pending' => 'fa-clock',
                        'completed' => 'fa-check-circle',
                        'canceled' => 'fa-times-circle',
                        'missed' => 'fa-exclamation-circle'
                    ][$appointment['status']];
                    
                    $status_label = ucfirst($appointment['status']);
                    
                    $app_date = new DateTime($appointment['appointment_date']);
                ?>
                <div class="history-card <?php echo $status_class; ?>">
                    <div class="history-header">
                        <div>
                            <div class="history-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo $app_date->format('l, F d, Y'); ?>
                            </div>
                            <div class="history-time">
                                <i class="fas fa-clock"></i>
                                <?php echo date('H:i', strtotime($appointment['start_time'])) . ' - ' . date('H:i', strtotime($appointment['end_time'])); ?>
                            </div>
                            <div class="history-doctor">
                                <i class="fas fa-user-md"></i>
                                <?php echo htmlspecialchars($appointment['doctor_last'] . ' ' . $appointment['doctor_first']); ?>
                            </div>
                        </div>
                        <div>
                            <span class="status-badge status-<?php echo $status_class; ?>">
                                <i class="fas <?php echo $status_icon; ?>"></i>
                                <?php echo $status_label; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if($appointment['diagnosis'] || $appointment['treatment'] || $appointment['questionnaire']): ?>
                    <div class="consultation-details">
                        <h4><i class="fas fa-stethoscope"></i> Consultation Details</h4>
                        
                        <?php if($appointment['diagnosis']): ?>
                        <p>
                            <strong>Diagnosis:</strong><br>
                            <?php echo nl2br(htmlspecialchars($appointment['diagnosis'])); ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if($appointment['treatment']): ?>
                        <p>
                            <strong>Treatment:</strong><br>
                            <?php echo nl2br(htmlspecialchars($appointment['treatment'])); ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if($appointment['questionnaire']): ?>
                        <p>
                            <strong>Questionnaire / Notes:</strong><br>
                            <?php echo nl2br(htmlspecialchars($appointment['questionnaire'])); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="no-consultation">
                        <i class="fas fa-info-circle"></i>
                        No consultation details recorded
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($current_page > 1): ?>
                        <a href="?patient_id=<?php echo $patient_id; ?>&page=<?php echo ($current_page - 1); ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if($i == 1 || $i == $total_pages || ($i >= $current_page - 2 && $i <= $current_page + 2)): ?>
                            <a href="?patient_id=<?php echo $patient_id; ?>&page=<?php echo $i; ?>" 
                               class="page-link <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php elseif($i == $current_page - 3 || $i == $current_page + 3): ?>
                            <span class="page-dots">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if($current_page < $total_pages): ?>
                        <a href="?patient_id=<?php echo $patient_id; ?>&page=<?php echo ($current_page + 1); ?>" class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <p>No appointment history for this patient</p>
            </div>
        <?php endif; ?>
        
        <div class="mt-lg" style="text-align: center;">
            <a href="assistant_patients.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Patients
            </a>
            <a href="assistant_patients_medical_records.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary">
                <i class="fas fa-notes-medical"></i> Medical Records
            </a>
            <a href="assistant_patients_infos.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary">
                <i class="fas fa-eye"></i> View Full Details
            </a>
        </div>
    </main>
</body>
</html>
