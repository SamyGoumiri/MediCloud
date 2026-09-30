<?php
session_start();
require_once "../../DB/connect.php";
require_once "../../DB/secure_page.php";

secure_page('patient', $conn);

$id_patient = $_SESSION["id_patient"];
$id_appointment = isset($_GET['appointment']) ? intval($_GET['appointment']) : 0;
$is_edit_mode = isset($_GET['edit']) && $_GET['edit'] == 1;

if ($id_appointment <= 0) {
    header("Location: patient_consult.php");
    exit;
}

$stmt = $conn->prepare("SELECT a.id_appointment, a.appointment_date, a.start_time, a.end_time, 
                        a.status, c.id_consult, c.diagnosis, c.treatment,
                        d.first_name as doctor_first_name, d.last_name as doctor_last_name,
                        p.first_name, p.last_name
                        FROM appointment a 
                        JOIN consultation c ON a.id_appointment = c.id_appointment
                        JOIN doctor d ON a.id_doctor = d.id_doctor
                        JOIN patient p ON a.id_patient = p.id_patient
                        WHERE a.id_appointment = ? AND a.id_patient = ? AND a.status = 'completed'");
$stmt->bind_param("ii", $id_appointment, $id_patient);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: patient_consult.php");
    exit;
}

$appointment = $result->fetch_assoc();
$stmt->close();

// Check if feedback exists
$stmt = $conn->prepare("SELECT cf.id as feedback_id, cf.accueil, cf.ponctualite, cf.disponibilite, 
                        cf.competence, cf.experience, cf.equipement, cf.hygiene, 
                        cf.securite, cf.parking, cf.cout, cf.comments
                        FROM consultation_feedback cf 
                        WHERE cf.id_consultation = ?");
$stmt->bind_param("i", $appointment['id_consult']);
$stmt->execute();
$result = $stmt->get_result();
$existing_feedback = $result->fetch_assoc();
$stmt->close();

// If feedback exists and not in edit mode, redirect to view page
if ($existing_feedback && !$is_edit_mode) {
    header("Location: patient_feedback_view.php?appointment=" . $id_appointment);
    exit;
}

$patient_name = $appointment['first_name'] . ' ' . $appointment['last_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Consultation | HippoCare</title>
    <link rel="stylesheet" href="css/patient_style.css">
    <link rel="stylesheet" href="css/patient_forms.css">
    <link rel="stylesheet" href="css/feedback.css">
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
        <h1><?php echo $is_edit_mode ? 'Edit Your Feedback' : 'Rate Your Consultation'; ?></h1>
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
        <div class="feedback-container">
        <div class="feedback-header">
            <h1><?php echo $is_edit_mode ? 'Edit Your Feedback' : 'Rate Your Consultation'; ?></h1>
            <p><?php echo $is_edit_mode ? 'Update your rating for this consultation.' : 'Your feedback helps us improve our services. Please rate the following aspects.'; ?></p>
        </div>

        <div class="consultation-details">
            <h3>Consultation Details</h3>
            <div class="consultation-info-row">
                <div class="consultation-info-item">
                    <i class="fas fa-user-md"></i>
                    <span>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></span>
                </div>
                <div class="consultation-info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date("F j, Y", strtotime($appointment['appointment_date'])); ?></span>
                </div>
                <div class="consultation-info-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo date("H:i", strtotime($appointment['start_time'])); ?> - <?php echo date("H:i", strtotime($appointment['end_time'])); ?></span>
                </div>
            </div>
            <?php if (!empty($appointment['diagnosis'])): ?>
                <div class="consultation-info-row">
                    <div class="consultation-info-item">
                        <i class="fas fa-notes-medical"></i>
                        <span><strong>Diagnosis:</strong> <?php echo htmlspecialchars($appointment['diagnosis']); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <form class="feedback-form" id="feedbackForm">
            <input type="hidden" name="id_consultation" value="<?php echo $appointment['id_consult']; ?>">
            <input type="hidden" name="id_appointment" value="<?php echo $appointment['id_appointment']; ?>">
            <?php if($is_edit_mode && $existing_feedback): ?>
            <input type="hidden" name="feedback_id" value="<?php echo $existing_feedback['feedback_id']; ?>">
            <input type="hidden" name="is_edit" value="1">
            <?php endif; ?>

            <div class="rating-group">
                <h3>Reception</h3>
                <p>Was the reception pleasant and professional?</p>
                <div class="rating-buttons" data-rating-group="accueil">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?php echo $i; ?>" <?php echo ($is_edit_mode && $existing_feedback && $existing_feedback['accueil'] == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="accueil" value="<?php echo $is_edit_mode && $existing_feedback ? $existing_feedback['accueil'] : ''; ?>" required>
            </div>

            <div class="rating-group">
                <h3>Punctuality</h3>
                <p>Did the appointment start on time?</p>
                <div class="rating-buttons" data-rating-group="ponctualite">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?php echo $i; ?>" <?php echo ($is_edit_mode && $existing_feedback && $existing_feedback['ponctualite'] == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="ponctualite" value="<?php echo $is_edit_mode && $existing_feedback ? $existing_feedback['ponctualite'] : ''; ?>" required>
            </div>

            <div class="rating-group">
                <h3>Availability</h3>
                <p>Were you able to get an appointment easily?</p>
                <div class="rating-buttons" data-rating-group="disponibilite">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?php echo $i; ?>" <?php echo ($is_edit_mode && $existing_feedback && $existing_feedback['disponibilite'] == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="disponibilite" value="<?php echo $is_edit_mode && $existing_feedback ? $existing_feedback['disponibilite'] : ''; ?>" required>
            </div>

            <div class="rating-group">
                <h3>Competence</h3>
                <p>Did the doctor seem competent and attentive?</p>
                <div class="rating-buttons" data-rating-group="competence">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?php echo $i; ?>" <?php echo ($is_edit_mode && $existing_feedback && $existing_feedback['competence'] == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="competence" value="<?php echo $is_edit_mode && $existing_feedback ? $existing_feedback['competence'] : ''; ?>" required>
            </div>

            <div class="rating-group">
                <h3>Experience</h3>
                <p>Did you feel the doctor had sufficient experience in medical care?</p>
                <div class="rating-buttons" data-rating-group="experience">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?php echo $i; ?>" <?php echo ($is_edit_mode && $existing_feedback && $existing_feedback['experience'] == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="experience" value="<?php echo $is_edit_mode && $existing_feedback ? $existing_feedback['experience'] : ''; ?>" required>
            </div>

            <div class="rating-group">
                <h3>Equipment</h3>
                <p>Did the medical equipment seem appropriate?</p>
                <div class="rating-buttons" data-rating-group="equipement">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?php echo $i; ?>" <?php echo ($is_edit_mode && $existing_feedback && $existing_feedback['equipement'] == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="equipement" value="<?php echo $is_edit_mode && $existing_feedback ? $existing_feedback['equipement'] : ''; ?>" required>
            </div>

            <div class="rating-group">
                <h3>Hygiene</h3>
                <p>Was the clinic clean and following hygiene standards?</p>
                <div class="rating-buttons" data-rating-group="hygiene">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?php echo $i; ?>" <?php echo ($is_edit_mode && $existing_feedback && $existing_feedback['hygiene'] == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="hygiene" value="<?php echo $is_edit_mode && $existing_feedback ? $existing_feedback['hygiene'] : ''; ?>" required>
            </div>

            <div class="rating-group">
                <h3>Location</h3>
                <p>Did you feel safe at the location?</p>
                <div class="rating-buttons" data-rating-group="securite">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?php echo $i; ?>" <?php echo ($is_edit_mode && $existing_feedback && $existing_feedback['securite'] == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="securite" value="<?php echo $is_edit_mode && $existing_feedback ? $existing_feedback['securite'] : ''; ?>" required>
            </div>

            <div class="rating-group">
                <h3>Parking</h3>
                <p>Was it easy to find parking?</p>
                <div class="rating-buttons" data-rating-group="parking">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?php echo $i; ?>" <?php echo ($is_edit_mode && $existing_feedback && $existing_feedback['parking'] == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="parking" value="<?php echo $is_edit_mode && $existing_feedback ? $existing_feedback['parking'] : ''; ?>" required>
            </div>

            <div class="rating-group">
                <h3>Cost</h3>
                <p>Are you satisfied with the value for money?</p>
                <div class="rating-buttons" data-rating-group="cout">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?php echo $i; ?>" <?php echo ($is_edit_mode && $existing_feedback && $existing_feedback['cout'] == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="cout" value="<?php echo $is_edit_mode && $existing_feedback ? $existing_feedback['cout'] : ''; ?>" required>
            </div>

            <div class="comments-group">
                <label for="comments">Additional Comments (Optional)</label>
                <textarea id="comments" name="comments" rows="5" placeholder="Your suggestions or additional feedback..."><?php echo $is_edit_mode && $existing_feedback ? htmlspecialchars($existing_feedback['comments']) : ''; ?></textarea>
            </div>

            <div class="form-actions">
                <a href="patient_consult.php" class="btn-back">Back</a>
                <button type="submit" class="btn-submit">Submit Rating</button>
            </div>
        </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2023-2026 HippoCare. All rights reserved.</p>
    </footer>

    <script src="js/validation.js"></script>
    <script src="js/feedback.js"></script>
</body>
</html>
