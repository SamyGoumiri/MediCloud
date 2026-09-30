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

if(!isset($_GET['assistant_id']) || !is_numeric($_GET['assistant_id'])) {
    header("Location: chief_doctor_assistants.php");
    exit();
}

$assistant_id = (int)$_GET['assistant_id'];

$assistant_stmt = $conn->prepare("
    SELECT a.id_assistant, a.last_name, a.first_name, a.email, a.phone, 
           a.birth_date, a.national_id, a.recruitment_date, a.id_doctor,
           d.first_name as doctor_first_name, d.last_name as doctor_last_name
    FROM assistant a
    LEFT JOIN doctor d ON a.id_doctor = d.id_doctor
    WHERE a.id_assistant = ?
    LIMIT 1
");
$assistant_stmt->bind_param("i", $assistant_id);
$assistant_stmt->execute();
$assistant = $assistant_stmt->get_result()->fetch_assoc();

if(!$assistant) {
    header("Location: chief_doctor_assistants.php");
    exit();
}

// Get assigned doctor info from JOIN result
$doctor_name = "Unassigned";
if($assistant['id_doctor'] && $assistant['doctor_first_name']) {
    $doctor_name = $assistant['doctor_first_name'] . ' ' . $assistant['doctor_last_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant Information | HippoCare</title>
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
        <h1>Assistant Information</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="chief_doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="chief_doctor_doctors.php"><i class="fas fa-user-md"></i> Doctors</a>
                <a href="chief_doctor_assistants.php" class="active"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="chief_doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="chief_doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="chief_doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <nav class="breadcrumb">
            <a href="chief_doctor_assistants.php">Assistants</a>
            <span class="separator">&gt;</span>
            <span><?php echo htmlspecialchars($assistant['first_name'] . ' ' . $assistant['last_name']); ?></span>
        </nav>

        <article class="assistant-info-container">
            <header class="assistant-header">
                <div class="assistant-avatar-large">
                    <span><?php echo strtoupper(substr($assistant['first_name'], 0, 1) . substr($assistant['last_name'], 0, 1)); ?></span>
                </div>
                <div class="assistant-header-info">
                    <h2><?php echo htmlspecialchars($assistant['first_name'] . ' ' . $assistant['last_name']); ?></h2>
                    <p class="assistant-id"><i class="fas fa-id-card"></i> ID: AST-<?php echo sprintf('%03d', $assistant['id_assistant']); ?></p>
                </div>
            </header>

            <section class="info-section">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>First Name:</label>
                        <p><?php echo htmlspecialchars($assistant['first_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Last Name:</label>
                        <p><?php echo htmlspecialchars($assistant['last_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Birth Date:</label>
                        <p>
                            <?php 
                                if(!empty($assistant['birth_date'])) {
                                    echo (new DateTime($assistant['birth_date']))->format('d/m/Y');
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
                                if(!empty($assistant['birth_date'])) {
                                    echo (new DateTime())->diff(new DateTime($assistant['birth_date']))->y . ' years';
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
                        <p><a href="mailto:<?php echo htmlspecialchars($assistant['email']); ?>"><?php echo htmlspecialchars($assistant['email']); ?></a></p>
                    </div>
                    <div class="info-item">
                        <label>Phone:</label>
                        <p><?php echo htmlspecialchars($assistant['phone']); ?></p>
                    </div>
                </div>
            </section>

            <section class="info-section">
                <h3><i class="fas fa-briefcase"></i> Employment Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Assigned Doctor:</label>
                        <p><?php echo htmlspecialchars($doctor_name); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Recruitment Date:</label>
                        <p>
                            <?php 
                                if(!empty($assistant['recruitment_date'])) {
                                    echo (new DateTime($assistant['recruitment_date']))->format('d/m/Y');
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
                        <p><?php echo !empty($assistant['national_id']) ? htmlspecialchars($assistant['national_id']) : 'Not provided'; ?></p>
                    </div>
                </div>
            </section>

            <footer class="action-buttons">
                <a href="chief_doctor_assistants.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Assistants
                </a>
                <a href="chief_doctor_assistants_edit.php?assistant_id=<?php echo $assistant_id; ?>" class="btn-primary">
                    <i class="fas fa-edit"></i> Edit Assistant
                </a>
            </footer>
        </article>
    </main>
</body>
</html>
