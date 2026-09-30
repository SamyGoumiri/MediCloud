<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('chief_doctor', $conn);

// Handle patient deletion
if(isset($_GET['action']) && isset($_GET['patient_id']) && is_numeric($_GET['patient_id'])) {
    $patient_id = intval($_GET['patient_id']);
    $action = $_GET['action'];
    
    if($action == 'delete' && isset($_GET['confirm'])) {
        // Start transaction to handle cascade deletes
        $conn->begin_transaction();
        
        try {
            // Delete consultations related to patient's appointments (using subquery with prepared statement)
            $stmt = $conn->prepare("
                DELETE FROM consultation 
                WHERE id_appointment IN (
                    SELECT id_appointment FROM appointment WHERE id_patient = ?
                )
            ");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            
            // Delete appointments for this patient
            $stmt = $conn->prepare("DELETE FROM appointment WHERE id_patient = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            
            // Delete medical records for this patient
            $stmt = $conn->prepare("DELETE FROM medical_record WHERE id_patient = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            
            // Delete patient address
            $stmt = $conn->prepare("DELETE FROM patient_address WHERE id_patient = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            
            // Delete emergency contacts
            $stmt = $conn->prepare("DELETE FROM emergency_contact WHERE id_patient = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            
            // Delete the patient record
            $stmt = $conn->prepare("DELETE FROM patient WHERE id_patient = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            header("Location: chief_doctor_patients.php?success=deleted");
            exit();
        } catch(Exception $e) {
            // Rollback on error
            $conn->rollback();
            header("Location: chief_doctor_patients.php?error=delete_failed");
            exit();
        }
    }
}

$id_chief_doctor = $_SESSION["id_chief_doctor"];
$stmt = $conn->prepare("SELECT last_name, first_name FROM doctor WHERE id_doctor = ?");
$stmt->bind_param("i", $id_chief_doctor);
$stmt->execute();
$chief_doctor = $stmt->get_result()->fetch_assoc();
$chief_doctor_name = $chief_doctor['first_name'] . ' ' . $chief_doctor['last_name'];

// Fetch all doctors for selection modal
$doctors_list = [];
$stmt = $conn->prepare("SELECT id_doctor, first_name, last_name FROM doctor ORDER BY last_name");
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $doctors_list[] = $row;
}

// Search functionality
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination settings
$items_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Count total patients
if(!empty($search_query)) {
    $search_param = "%$search_query%";
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM patient
                                  WHERE last_name LIKE ? OR first_name LIKE ? OR email LIKE ?");
    $count_stmt->bind_param("sss", $search_param, $search_param, $search_param);
} else {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM patient");
}
$count_stmt->execute();
$total_patients = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_patients / $items_per_page);

// Fetch patients for current page
if(!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt = $conn->prepare("SELECT id_patient, last_name, first_name, email, phone, birth_date 
                           FROM patient 
                           WHERE last_name LIKE ? OR first_name LIKE ? OR email LIKE ?
                           ORDER BY last_name ASC
                           LIMIT ? OFFSET ?");
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $items_per_page, $offset);
} else {
    $stmt = $conn->prepare("SELECT id_patient, last_name, first_name, email, phone, birth_date 
                           FROM patient 
                           ORDER BY last_name ASC
                           LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $items_per_page, $offset);
}
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management | HippoCare</title>
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
        <h1>Patient Management</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="chief_doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="chief_doctor_doctors.php"><i class="fas fa-user-md"></i> Doctors</a>
                <a href="chief_doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="chief_doctor_patients.php" class="active"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="chief_doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="chief_doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <section class="list-container">
            <div class="section-header">
                <h2><i class="fas fa-user-injured"></i> Patients (<?php echo $total_patients; ?>)</h2>
                <a href="chief_doctor_patients_new.php" class="btn">
                    <i class="fas fa-plus-circle"></i> Add New Patient
                </a>
            </div>
            
            <?php if(isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Patient and all related data deleted successfully
                </div>
            <?php elseif(isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Error: <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="search-filter-container">
                <form method="get" action="" class="search-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
                        <button type="submit" class="btn-search btn-icon" title="Search"><i class="fas fa-search"></i></button>
                        <?php if(!empty($search_query)): ?>
                            <a href="chief_doctor_patients.php" class="btn-clear btn-icon" title="Clear search"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <?php if($total_patients > 0): ?>
                <div class="items-list">
                    <?php foreach($patients as $patient): ?>
                        <?php 
                            $initials = strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1));
                            $birth_date = new DateTime($patient['birth_date']);
                            $age = (new DateTime())->diff($birth_date)->y;
                        ?>
                        <article class="item-card">
                            <div class="item-avatar">
                                <div class="avatar"><?php echo $initials; ?></div>
                            </div>
                            <div class="item-content">
                                <h3><?php echo htmlspecialchars($patient['last_name'] . ' ' . $patient['first_name']); ?></h3>
                                <p class="item-meta">
                                    <span><i class="fas fa-birthday-cake"></i> <?php echo $birth_date->format('d/m/Y') . ' (' . $age . ' years)'; ?></span>
                                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($patient['phone']); ?></span>
                                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($patient['email']); ?></span>
                                </p>
                                <p class="item-id">ID: PAT-<?php echo sprintf('%03d', $patient['id_patient']); ?></p>
                            </div>
                            <div class="item-actions">
                                <a href="chief_doctor_app_new.php?patient_first_name=<?php echo urlencode($patient['first_name']); ?>&patient_last_name=<?php echo urlencode($patient['last_name']); ?>&patient_id=<?php echo $patient['id_patient']; ?>" class="btn btn-success btn-icon" title="Schedule Appointment">
                                    <i class="fas fa-calendar-plus"></i>
                                </a>
                                <a href="chief_doctor_patients_history.php?patient_id=<?php echo $patient['id_patient']; ?>" class="btn btn-primary btn-icon" title="View History">
                                    <i class="fas fa-history"></i>
                                </a>
                                <a href="chief_doctor_patients_medical_records.php?patient_id=<?php echo $patient['id_patient']; ?>" class="btn btn-primary btn-icon" title="Medical Records">
                                    <i class="fas fa-notes-medical"></i>
                                </a>
                                <a href="chief_doctor_patients_edit.php?patient_id=<?php echo $patient['id_patient']; ?>" class="btn btn-secondary btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="chief_doctor_patients_infos.php?patient_id=<?php echo $patient['id_patient']; ?>" class="btn btn-secondary btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="#" class="btn btn-danger btn-icon" title="Delete" onclick="return confirmDeletePatient(<?php echo $patient['id_patient']; ?>, '<?php echo addslashes($patient['first_name'] . ' ' . $patient['last_name']); ?>');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>No patients found</p>
                </div>
            <?php endif; ?>
            
            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($current_page > 1): ?>
                        <a href="?page=<?php echo ($current_page - 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-page">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <div class="page-numbers">
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if($start_page > 1): ?>
                            <a href="?page=1<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-page">1</a>
                            <?php if($start_page > 2): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                               class="btn-page <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if($end_page < $total_pages): ?>
                            <?php if($end_page < $total_pages - 1): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-page"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($current_page < $total_pages): ?>
                        <a href="?page=<?php echo ($current_page + 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-page">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="pagination-info">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $items_per_page, $total_patients); ?> of <?php echo $total_patients; ?> patients
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        function confirmDeletePatient(patientId, patientName) {
            const confirmed = window.confirm(
                '🗑️  PERMANENT DELETION WARNING:\n\n' +
                'This will permanently delete:\n' +
                '• Patient: ' + patientName + '\n' +
                '• All appointments\n' +
                '• All consultations\n' +
                '• Medical records\n' +
                '• Emergency contacts\n\n' +
                'This action CANNOT be undone.\n\n' +
                'Are you sure?'
            );
            
            if (confirmed) {
                window.location.href = 'chief_doctor_patients.php?action=delete&patient_id=' + patientId + '&confirm=yes';
            }
            return false;
        }
    </script>
</body>
</html>
