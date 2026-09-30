<?php
session_start();
if(!isset($_SESSION["id_patient"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== 'patient') {
    session_destroy();
    header("Location: patient_auth_login.php");
    exit();
}

require_once '../../DB/connect.php';

$id_appointment = isset($_GET['appointment']) ? intval($_GET['appointment']) : 0;

if($id_appointment <= 0) {
    header("Location: patient_consult.php?error=invalid_appointment");
    exit();
}

$id_patient = $_SESSION['id_patient'];

// Get patient name from database
$stmt_patient = $conn->prepare("SELECT first_name, last_name FROM patient WHERE id_patient = ?");
$stmt_patient->bind_param("i", $id_patient);
$stmt_patient->execute();
$patient_result = $stmt_patient->get_result();
$patient_data = $patient_result->fetch_assoc();
$patient_name = $patient_data ? ($patient_data['first_name'] . ' ' . $patient_data['last_name']) : 'Patient';

// Get consultation and feedback details
$query = "SELECT 
            a.id_appointment, a.appointment_date, a.start_time,
            d.first_name as doctor_first, d.last_name as doctor_last, d.speciality,
            c.id_consult, c.diagnosis, c.treatment,
            cf.id as feedback_id, cf.accueil, cf.ponctualite, cf.disponibilite, 
            cf.competence, cf.experience, cf.equipement, cf.hygiene, 
            cf.securite, cf.parking, cf.cout, cf.comments
          FROM appointment a
          JOIN consultation c ON a.id_appointment = c.id_appointment
          JOIN doctor d ON a.id_doctor = d.id_doctor
          LEFT JOIN consultation_feedback cf ON c.id_consult = cf.id_consultation
          WHERE a.id_appointment = ? AND a.id_patient = ? AND a.status = 'completed'";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id_appointment, $id_patient);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    header("Location: patient_consult.php?error=consultation_not_found");
    exit();
}

$data = $result->fetch_assoc();

// Check if feedback exists
if(!$data['feedback_id']) {
    header("Location: patient_feedback.php?appointment=" . $id_appointment);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback | HippoCare</title>
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
        <h1>Feedback Details</h1>
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
            <!-- Consultation Details -->
            <div class="consultation-details">
                <h3><i class="fas fa-info-circle"></i> Consultation Information</h3>
                <div class="consultation-info-row">
                    <div class="consultation-info-item">
                        <i class="fas fa-calendar"></i>
                        <span><strong>Date:</strong> <?php echo date('F d, Y', strtotime($data['appointment_date'])); ?></span>
                    </div>
                    <div class="consultation-info-item">
                        <i class="fas fa-clock"></i>
                        <span><strong>Time:</strong> <?php echo date('H:i', strtotime($data['start_time'])); ?></span>
                    </div>
                </div>
                <div class="consultation-info-row">
                    <div class="consultation-info-item">
                        <i class="fas fa-user-md"></i>
                        <span><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($data['doctor_first'] . ' ' . $data['doctor_last']); ?></span>
                    </div>
                    <div class="consultation-info-item">
                        <i class="fas fa-stethoscope"></i>
                        <span><strong>Speciality:</strong> <?php echo htmlspecialchars($data['speciality']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Feedback Display -->
            <div class="feedback-form">
                <h2 style="text-align: center; color: var(--primary-color); margin-bottom: 2rem;">
                    <i class="fas fa-star"></i> Your Rating
                </h2>

                <?php
                $criteria = [
                    'accueil' => ['label' => 'Reception', 'icon' => 'fa-smile'],
                    'ponctualite' => ['label' => 'Punctuality', 'icon' => 'fa-clock'],
                    'disponibilite' => ['label' => 'Availability', 'icon' => 'fa-calendar-check'],
                    'competence' => ['label' => 'Competence', 'icon' => 'fa-user-graduate'],
                    'experience' => ['label' => 'Experience', 'icon' => 'fa-briefcase'],
                    'equipement' => ['label' => 'Equipment', 'icon' => 'fa-laptop-medical'],
                    'hygiene' => ['label' => 'Hygiene', 'icon' => 'fa-hand-sparkles'],
                    'securite' => ['label' => 'Location', 'icon' => 'fa-map-marker-alt'],
                    'parking' => ['label' => 'Parking', 'icon' => 'fa-parking'],
                    'cout' => ['label' => 'Cost', 'icon' => 'fa-dollar-sign']
                ];

                foreach($criteria as $key => $info):
                    $rating = $data[$key];
                ?>
                <div class="rating-group">
                    <h3><i class="fas <?php echo $info['icon']; ?>"></i> <?php echo $info['label']; ?></h3>
                    <div class="rating-display">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <span class="rating-star <?php echo $i <= $rating ? 'filled' : ''; ?>">
                                <i class="fas fa-star"></i>
                            </span>
                        <?php endfor; ?>
                        <span class="rating-value"><?php echo $rating; ?>/5</span>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Comments -->
                <?php if(!empty($data['comments'])): ?>
                <div class="comments-display">
                    <h3><i class="fas fa-comment"></i> Your Comments</h3>
                    <div class="comments-text">
                        <?php echo nl2br(htmlspecialchars($data['comments'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <a href="patient_consult.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Consultations
                    </a>
                    <a href="patient_feedback.php?appointment=<?php echo $id_appointment; ?>&edit=1" class="btn-submit">
                        <i class="fas fa-edit"></i> Edit Rating
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 HippoCare Medical Platform. All Rights Reserved.</p>
    </footer>
</body>
</html>
