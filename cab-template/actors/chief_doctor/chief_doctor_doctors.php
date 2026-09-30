<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('chief_doctor', $conn);

$delete_message = '';
$delete_error = '';

if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['doctor_id']) && isset($_GET['confirm'])) {
    if(!is_numeric($_GET['doctor_id'])) {
        header("Location: chief_doctor_doctors.php");
        exit();
    }
    
    $doctor_id = intval($_GET['doctor_id']);
    
    // Prevent chief doctor from deleting themselves
    if($doctor_id != $_SESSION["id_chief_doctor"]) {
        // Start transaction for cascade delete
        $conn->begin_transaction();
        
        try {
            // Delete consultations related to doctor's appointments (using prepared statement)
            $stmt = $conn->prepare("
                DELETE FROM consultation 
                WHERE id_appointment IN (
                    SELECT id_appointment FROM appointment WHERE id_doctor = ?
                )
            ");
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            
            // Delete appointments for this doctor
            $stmt = $conn->prepare("DELETE FROM appointment WHERE id_doctor = ?");
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            
            // Delete schedule entries for this doctor
            $stmt = $conn->prepare("DELETE FROM schedule WHERE id_doctor = ?");
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            
            // Delete the doctor record
            $stmt = $conn->prepare("DELETE FROM doctor WHERE id_doctor = ?");
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            $delete_message = "Doctor and all related data have been successfully deleted.";
        } catch(Exception $e) {
            // Rollback on error
            $conn->rollback();
            $delete_error = "Error deleting doctor: " . $e->getMessage();
        }
    }
    
    header("Location: chief_doctor_doctors.php" . ($delete_message ? "?success=deleted" : ""));
    exit();
}

$id_chief_doctor = $_SESSION["id_chief_doctor"];
$stmt = $conn->prepare("SELECT last_name, first_name FROM doctor WHERE id_doctor = ?");
$stmt->bind_param("i", $id_chief_doctor);
$stmt->execute();
$chief_doctor_data = $stmt->get_result()->fetch_assoc();
$chief_doctor_name = $chief_doctor_data['first_name'] . ' ' . $chief_doctor_data['last_name'];

// Search functionality
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination settings
$items_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Count total doctors
if(!empty($search_query)) {
    $search_param = "%$search_query%";
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM doctor
                                  WHERE last_name LIKE ? OR first_name LIKE ? OR email LIKE ? OR speciality LIKE ?");
    $count_stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
} else {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM doctor");
}
$count_stmt->execute();
$total_doctors = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_doctors / $items_per_page);

// Fetch doctors for current page
if(!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt = $conn->prepare("SELECT id_doctor, last_name, first_name, email, phone, birth_date, speciality 
                           FROM doctor
                           WHERE last_name LIKE ? OR first_name LIKE ? OR email LIKE ? OR speciality LIKE ?
                           ORDER BY last_name ASC
                           LIMIT ? OFFSET ?");
    $stmt->bind_param("ssssii", $search_param, $search_param, $search_param, $search_param, $items_per_page, $offset);
} else {
    $stmt = $conn->prepare("SELECT id_doctor, last_name, first_name, email, phone, birth_date, speciality 
                           FROM doctor
                           ORDER BY last_name ASC
                           LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $items_per_page, $offset);
}
$stmt->execute();
$doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management | HippoCare</title>
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
        <h1>Doctor Management</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="chief_doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="chief_doctor_doctors.php" class="active"><i class="fas fa-user-md"></i> Doctors</a>
                <a href="chief_doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="chief_doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="chief_doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="chief_doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <section class="list-container">
            <div class="section-header">
                <h2><i class="fas fa-user-md"></i> Doctors (<?php echo $total_doctors; ?>)</h2>
                <a href="chief_doctor_doctors_new.php" class="btn">
                    <i class="fas fa-plus-circle"></i> Add Doctor
                </a>
            </div>
            
            <div class="search-filter-container">
                <form method="get" action="" class="search-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search by name, email, or speciality..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
                        <button type="submit" class="btn-search btn-icon" title="Search"><i class="fas fa-search"></i></button>
                        <?php if(!empty($search_query)): ?>
                            <a href="chief_doctor_doctors.php" class="btn-clear btn-icon" title="Clear search"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <?php if(isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Doctor has been successfully deleted.
                </div>
            <?php endif; ?>
            
            <?php if($total_doctors > 0): ?>
                <div class="items-list">
                    <?php foreach($doctors as $doctor): ?>
                        <?php 
                            $initials = strtoupper(substr($doctor['first_name'], 0, 1) . substr($doctor['last_name'], 0, 1));
                            $birth_date = new DateTime($doctor['birth_date']);
                            $age = (new DateTime())->diff($birth_date)->y;
                            
                            // Get data that will be deleted with this doctor
                            $stmt_count = $conn->prepare("SELECT 
                                (SELECT COUNT(*) FROM appointment WHERE id_doctor = ?) as appointments,
                                (SELECT COUNT(*) FROM assistant WHERE id_doctor = ?) as assistants,
                                (SELECT COUNT(*) FROM schedule WHERE id_doctor = ?) as schedules
                            ");
                            $stmt_count->bind_param("iii", $doctor['id_doctor'], $doctor['id_doctor'], $doctor['id_doctor']);
                            $stmt_count->execute();
                            $impact = $stmt_count->get_result()->fetch_assoc();
                        ?>
                        <article class="item-card">
                            <div class="item-avatar">
                                <div class="avatar"><?php echo $initials; ?></div>
                            </div>
                            <div class="item-content">
                                <h3><?php echo htmlspecialchars($doctor['last_name'] . ' ' . $doctor['first_name']); ?></h3>
                                <p class="item-meta">
                                    <span><i class="fas fa-birthday-cake"></i> <?php echo $birth_date->format('d/m/Y') . ' (' . $age . ' years)'; ?></span>
                                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($doctor['phone']); ?></span>
                                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($doctor['email']); ?></span>
                                </p>
                                <p class="item-id">ID: DR-<?php echo sprintf('%03d', $doctor['id_doctor']); ?></p>
                            </div>
                            <div class="item-actions">
                                <a href="chief_doctor_doctors_infos.php?doctor_id=<?php echo $doctor['id_doctor']; ?>" class="btn btn-secondary btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($doctor['id_doctor'] != $_SESSION["id_chief_doctor"]): ?>
                                    <?php
                                        $warning_msg = "⚠️ WARNING: This will permanently delete:\\n\\n";
                                        $warning_msg .= "• Dr. " . $doctor['first_name'] . " " . $doctor['last_name'] . "\\n";
                                        if($impact['appointments'] > 0) $warning_msg .= "• " . $impact['appointments'] . " appointment(s) (with consultations & feedbacks)\\n";
                                        if($impact['assistants'] > 0) $warning_msg .= "• " . $impact['assistants'] . " assistant(s) will be unassigned\\n";
                                        if($impact['schedules'] > 0) $warning_msg .= "• " . $impact['schedules'] . " schedule(s)\\n";
                                        $warning_msg .= "\\nThis action CANNOT be undone!\\n\\nType 'DELETE' to confirm:";
                                    ?>
                                    <a href="#" class="btn btn-danger btn-icon" title="Delete" onclick="return confirmDelete(<?php echo $doctor['id_doctor']; ?>, '<?php echo addslashes($doctor['first_name'] . ' ' . $doctor['last_name']); ?>', <?php echo $impact['appointments']; ?>, <?php echo $impact['assistants']; ?>, <?php echo $impact['schedules']; ?>);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>No doctors found</p>
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
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $items_per_page, $total_doctors); ?> of <?php echo $total_doctors; ?> doctors
                </div>
            <?php endif; ?>
        </section>
    </main>
    
    <script>
    function confirmDelete(doctorId, doctorName, appointments, assistants, schedules) {
        let message = '⚠️ WARNING: This will permanently delete:\n\n';
        message += '• Dr. ' + doctorName + '\n';
        if(appointments > 0) message += '• ' + appointments + ' appointment(s) (including all consultations & feedback)\n';
        if(assistants > 0) message += '• ' + assistants + ' assistant(s) will be unassigned\n';
        if(schedules > 0) message += '• ' + schedules + ' schedule(s)\n';
        message += '\nThis action CANNOT be undone!\n\n';
        message += 'Are you absolutely sure you want to delete this doctor?';
        
        if(confirm(message)) {
            window.location.href = 'chief_doctor_doctors.php?action=delete&doctor_id=' + doctorId + '&confirm=yes';
            return true;
        }
        return false;
    }
    </script>
</body>
</html>
