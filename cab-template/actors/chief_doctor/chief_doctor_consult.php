<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('chief_doctor', $conn);

if(!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    header("Location: chief_doctor_app.php");
    exit();
}

$appointment_id = intval($_GET['appointment_id']);

// Check if method is POST (form submission)
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Only allow form submission if appointment is pending
    $status_check = $conn->prepare("SELECT status FROM appointment WHERE id_appointment = ?");
    $status_check->bind_param("i", $appointment_id);
    $status_check->execute();
    $status_result = $status_check->get_result()->fetch_assoc();
    
    if ($status_result['status'] !== 'pending') {
        header("Location: chief_doctor_app.php?error=cannot_edit_consultation&status=" . $status_result['status']);
        exit();
    }
    
    $diagnosis = isset($_POST['diagnosis']) ? trim($_POST['diagnosis']) : '';
    $treatment = isset($_POST['treatment']) ? trim($_POST['treatment']) : '';
    $questionnaire = isset($_POST['questionnaire']) ? trim($_POST['questionnaire']) : '';
    
    // Collect ALL validation errors
    $errors = [];
    
    if(empty($diagnosis)) {
        $errors[] = "Diagnosis is required.";
    } elseif(strlen($diagnosis) < 10) {
        $errors[] = "Diagnosis must be at least 10 characters long.";
    } elseif(strlen($diagnosis) > 5000) {
        $errors[] = "Diagnosis must not exceed 5000 characters.";
    }
    
    if(empty($treatment)) {
        $errors[] = "Treatment is required.";
    } elseif(strlen($treatment) < 10) {
        $errors[] = "Treatment must be at least 10 characters long.";
    } elseif(strlen($treatment) > 5000) {
        $errors[] = "Treatment must not exceed 5000 characters.";
    }
    
    if(!empty($questionnaire) && strlen($questionnaire) > 5000) {
        $errors[] = "Questionnaire must not exceed 5000 characters.";
    }
    
    // Only proceed if no validation errors
    if(empty($errors)) {
        // Get doctor ID from appointment
        $doc_stmt = $conn->prepare("SELECT id_doctor FROM appointment WHERE id_appointment = ?");
        $doc_stmt->bind_param("i", $appointment_id);
        $doc_stmt->execute();
        $doc_result = $doc_stmt->get_result()->fetch_assoc();
        $id_doctor = $doc_result['id_doctor'];
        
        // Get consultation fee from doctor's profile
        $fee_stmt = $conn->prepare("SELECT consultation_fee FROM doctor WHERE id_doctor = ?");
        $fee_stmt->bind_param("i", $id_doctor);
        $fee_stmt->execute();
        $fee_result = $fee_stmt->get_result()->fetch_assoc();
        $fee = $fee_result['consultation_fee'];
        
        // Use INSERT ... ON DUPLICATE KEY UPDATE to prevent race conditions
        // This atomically inserts if new, updates if exists
        $conn->begin_transaction();
        
        try {
            // Add UNIQUE constraint requirement: id_appointment must be unique in consultation table
            $upsert_stmt = $conn->prepare(
                "INSERT INTO consultation (id_appointment, diagnosis, treatment, fee, questionnaire, id_doctor)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                 diagnosis = VALUES(diagnosis),
                 treatment = VALUES(treatment),
                 fee = VALUES(fee),
                 questionnaire = VALUES(questionnaire)"
            );
            $upsert_stmt->bind_param("issdsi", $appointment_id, $diagnosis, $treatment, $fee, $questionnaire, $id_doctor);
            $upsert_stmt->execute();
            
            // Mark appointment as completed
            $app_stmt = $conn->prepare("UPDATE appointment SET status = 'completed' WHERE id_appointment = ?");
            $app_stmt->bind_param("i", $appointment_id);
            $app_stmt->execute();
            
            $conn->commit();
            
            header("Location: chief_doctor_app.php?success=consultation_saved");
            exit();
        } catch(Exception $e) {
            $conn->rollback();
            $errors[] = "Error saving consultation. Please try again.";
            error_log("Consultation save failed: " . $e->getMessage());
        }
    }
}

// Fetch appointment details
$apt_stmt = $conn->prepare("
    SELECT a.id_appointment, a.appointment_date, a.start_time, a.end_time, a.status,
           d.id_doctor, d.first_name as doctor_first, d.last_name as doctor_last, d.consultation_fee,
           p.id_patient, p.first_name as patient_first, p.last_name as patient_last
    FROM appointment a
    JOIN doctor d ON a.id_doctor = d.id_doctor
    JOIN patient p ON a.id_patient = p.id_patient
    WHERE a.id_appointment = ?
    LIMIT 1
");
$apt_stmt->bind_param("i", $appointment_id);
$apt_stmt->execute();
$appointment = $apt_stmt->get_result()->fetch_assoc();

if(!$appointment) {
    header("Location: chief_doctor_app.php");
    exit();
}

// Fetch existing consultation if any
$consult_stmt = $conn->prepare("SELECT * FROM consultation WHERE id_appointment = ?");
$consult_stmt->bind_param("i", $appointment_id);
$consult_stmt->execute();
$consultation = $consult_stmt->get_result()->fetch_assoc();

// Determine if form should be read-only
$is_readonly = in_array($appointment['status'], ['completed', 'canceled', 'missed']);

// Get chief doctor details
$id_chief_doctor = $_SESSION["id_chief_doctor"];
$chief_stmt = $conn->prepare("SELECT last_name, first_name FROM doctor WHERE id_doctor = ?");
$chief_stmt->bind_param("i", $id_chief_doctor);
$chief_stmt->execute();
$chief_doctor_data = $chief_stmt->get_result()->fetch_assoc();
$chief_doctor_name = $chief_doctor_data['first_name'] . ' ' . $chief_doctor_data['last_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation | HippoCare</title>
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
        <h1>Medical Consultation</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="chief_doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="chief_doctor_doctors.php"><i class="fas fa-user-md"></i> Doctors</a>
                <a href="chief_doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="chief_doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="chief_doctor_app.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="chief_doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <article class="info-card">
            <section class="info-section">
                <h2><i class="fas fa-file-medical"></i> Appointment Details</h2>
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

            <section class="form-section">
                <h2><i class="fas fa-notes-medical"></i> Consultation Details</h2>
                
                <?php if(!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="error-list">
                            <?php foreach($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if($appointment['status'] !== 'pending'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        This appointment has a status of <strong><?php echo ucfirst(htmlspecialchars($appointment['status'])); ?></strong> and cannot be edited.
                    </div>
                    
                    <?php if($consultation): ?>
                        <div class="form-view-only">
                            <div class="form-row">
                                <div class="form-control full-width">
                                    <label for="diagnosis">Diagnosis</label>
                                    <p class="view-only-text"><?php echo nl2br(htmlspecialchars($consultation['diagnosis'] ?? '')); ?></p>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-control full-width">
                                    <label for="treatment">Treatment</label>
                                    <p class="view-only-text"><?php echo nl2br(htmlspecialchars($consultation['treatment'] ?? '')); ?></p>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-control">
                                    <label>Consultation Fee (DZD)</label>
                                    <p class="view-only-text"><?php echo number_format($consultation['fee'] ?? 0, 2, ',', ' '); ?> DZD</p>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-control full-width">
                                    <label for="questionnaire">Patient Questionnaire</label>
                                    <p class="view-only-text"><?php echo nl2br(htmlspecialchars($consultation['questionnaire'] ?? '')); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p><em>No consultation record for this appointment.</em></p>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <a href="chief_doctor_app.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Appointments
                        </a>
                    </div>
                <?php else: ?>
                    <!-- EDITABLE FORM - only shown for pending appointments -->
                    <form method="POST" class="form-group" id="consultationForm" onsubmit="return validateConsultationForm()">
                        <div class="form-row">
                            <div class="form-control full-width">
                                <label for="diagnosis">Diagnosis <span class="required">*</span></label>
                                <textarea id="diagnosis" name="diagnosis" class="form-input" rows="4" required 
                                          placeholder="Enter patient diagnosis..."><?php echo htmlspecialchars($consultation['diagnosis'] ?? ''); ?></textarea>
                                <small class="char-count" id="diagnosis-count"></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-control full-width">
                                <label for="treatment">Treatment <span class="required">*</span></label>
                                <textarea id="treatment" name="treatment" class="form-input" rows="4" required 
                                          placeholder="Enter prescribed treatment..."><?php echo htmlspecialchars($consultation['treatment'] ?? ''); ?></textarea>
                                <small class="char-count" id="treatment-count"></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-control">
                                <label>Consultation Fee (DZD)</label>
                                <p class="fee-display"><?php echo number_format($appointment['consultation_fee'] ?? 0, 2, ',', ' '); ?> DZD</p>
                                <small class="fee-note"><i class="fas fa-info-circle"></i> Automatically pulled from doctor's profile</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-control full-width">
                                <label for="questionnaire">Patient Questionnaire</label>
                                <textarea id="questionnaire" name="questionnaire" class="form-input" rows="3"
                                          placeholder="Any additional patient information or responses..."><?php echo htmlspecialchars($consultation['questionnaire'] ?? ''); ?></textarea>
                                <small class="char-count" id="questionnaire-count"></small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="chief_doctor_app.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Appointments
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Consultation
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        </article>
    </main>

    <script src="js/validation.js"></script>
    <script>
        // Character count and validation for consultation fields
        const maxChars = 5000;
        const minChars = 10;
        
        function updateCharCount(fieldId, countId) {
            const field = document.getElementById(fieldId);
            const count = document.getElementById(countId);
            if (field && count) {
                const length = field.value.length;
                count.textContent = length + ' / ' + maxChars + ' characters';
                if (length > maxChars) {
                    count.style.color = 'red';
                } else if (length < minChars && length > 0) {
                    count.style.color = 'orange';
                } else {
                    count.style.color = '#666';
                }
            }
        }
        
        function validateConsultationForm() {
            const diagnosis = document.getElementById('diagnosis').value.trim();
            const treatment = document.getElementById('treatment').value.trim();
            const questionnaire = document.getElementById('questionnaire').value.trim();
            
            const errors = [];
            
            if (!diagnosis) {
                errors.push('Diagnosis is required.');
            } else if (diagnosis.length < minChars) {
                errors.push('Diagnosis must be at least ' + minChars + ' characters long.');
            } else if (diagnosis.length > maxChars) {
                errors.push('Diagnosis must not exceed ' + maxChars + ' characters.');
            }
            
            if (!treatment) {
                errors.push('Treatment is required.');
            } else if (treatment.length < minChars) {
                errors.push('Treatment must be at least ' + minChars + ' characters long.');
            } else if (treatment.length > maxChars) {
                errors.push('Treatment must not exceed ' + maxChars + ' characters.');
            }
            
            if (questionnaire && questionnaire.length > maxChars) {
                errors.push('Questionnaire must not exceed ' + maxChars + ' characters.');
            }
            
            if (errors.length > 0) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }
            return true;
        }
        
        // Add event listeners for character count
        document.addEventListener('DOMContentLoaded', function() {
            const diagnosisField = document.getElementById('diagnosis');
            const treatmentField = document.getElementById('treatment');
            const questionnaireField = document.getElementById('questionnaire');
            
            if (diagnosisField) {
                diagnosisField.addEventListener('input', function() {
                    updateCharCount('diagnosis', 'diagnosis-count');
                });
                updateCharCount('diagnosis', 'diagnosis-count');
            }
            
            if (treatmentField) {
                treatmentField.addEventListener('input', function() {
                    updateCharCount('treatment', 'treatment-count');
                });
                updateCharCount('treatment', 'treatment-count');
            }
            
            if (questionnaireField) {
                questionnaireField.addEventListener('input', function() {
                    updateCharCount('questionnaire', 'questionnaire-count');
                });
                updateCharCount('questionnaire', 'questionnaire-count');
            }
        });
    </script>
</body>
</html>