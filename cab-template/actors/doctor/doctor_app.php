<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('doctor', $conn);

// Handle status change
if(isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Get current appointment status for validation
    $get_status = $conn->prepare("SELECT status FROM appointment WHERE id_appointment = ?");
    $get_status->bind_param("i", $appointment_id);
    $get_status->execute();
    $status_result = $get_status->get_result()->fetch_assoc();
    
    if (!$status_result) {
        header("Location: doctor_app.php?error=appointment_not_found");
        exit();
    }
    
    $current_status = $status_result['status'];
    
    // Validate state transitions
    $valid_transitions = [
        'pending' => ['completed', 'canceled', 'missed'],
        'completed' => [],  // Cannot change from completed
        'canceled' => [],   // Cannot change from canceled
        'missed' => []      // Cannot change from missed
    ];
    
    // Map action to target status
    $action_to_status = [
        'cancel' => 'canceled',
        'complete' => 'completed',
        'mark_missed' => 'missed'
    ];
    
    if($action == 'cancel' && isset($_GET['confirm'])) {
        $target_status = 'canceled';
        
        // Validate transition
        if (!in_array($target_status, $valid_transitions[$current_status])) {
            header("Location: doctor_app.php?error=invalid_transition&from=" . $current_status . "&to=" . $target_status);
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE appointment SET status = 'canceled' WHERE id_appointment = ?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        header("Location: doctor_app.php?success=canceled");
        exit();
    } elseif($action == 'complete') {
        $target_status = 'completed';
        
        // Validate transition
        if (!in_array($target_status, $valid_transitions[$current_status])) {
            header("Location: doctor_app.php?error=invalid_transition&from=" . $current_status . "&to=" . $target_status);
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE appointment SET status = 'completed' WHERE id_appointment = ?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        header("Location: doctor_app.php?success=completed");
        exit();
    } elseif($action == 'start') {
        // Start consultation - redirect to consultation page (only for pending)
        if ($current_status !== 'pending') {
            header("Location: doctor_app.php?error=cannot_start_consultation&status=" . $current_status);
            exit();
        }
        header("Location: doctor_consult.php?appointment_id=" . $appointment_id);
        exit();
    } elseif($action == 'mark_missed') {
        $target_status = 'missed';
        
        // Validate transition
        if (!in_array($target_status, $valid_transitions[$current_status])) {
            header("Location: doctor_app.php?error=invalid_transition&from=" . $current_status . "&to=" . $target_status);
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE appointment SET status = 'missed' WHERE id_appointment = ?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        header("Location: doctor_app.php?success=missed");
        exit();
    }
}

$id_doctor = $_SESSION["id_doctor"];
$stmt = $conn->prepare("SELECT last_name, first_name FROM doctor WHERE id_doctor = ?");
$stmt->bind_param("i", $id_doctor);
$stmt->execute();
$doctor_data = $stmt->get_result()->fetch_assoc();
$doctor_name = $doctor_data['first_name'] . ' ' . $doctor_data['last_name'];

// Search and filters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Pagination settings
$items_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Build query conditions
$where_conditions = array();
$bind_params = array();
$param_types = "";

// Filter by logged-in doctor
$where_conditions[] = "a.id_doctor = ?";
$bind_params[] = $id_doctor;
$param_types .= "i";

if(!empty($search_query)) {
    $search_param = "%$search_query%";
    $where_conditions[] = "(d.first_name LIKE ? OR d.last_name LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?)";
    array_push($bind_params, $search_param, $search_param, $search_param, $search_param);
    $param_types .= "ssss";
}

if(!empty($status_filter)) {
    if($status_filter == 'pending_today') {
        $where_conditions[] = "a.status = 'pending' AND a.appointment_date = CURDATE()";
    } elseif($status_filter == 'pending_upcoming') {
        $where_conditions[] = "a.status = 'pending' AND a.appointment_date > CURDATE()";
    } elseif(in_array($status_filter, array('pending', 'completed', 'canceled', 'missed'))) {
        $where_conditions[] = "a.status = ?";
        $bind_params[] = $status_filter;
        $param_types .= "s";
    }
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Count total appointments
$count_query = "SELECT COUNT(*) as total FROM appointment a 
                JOIN doctor d ON a.id_doctor = d.id_doctor 
                JOIN patient p ON a.id_patient = p.id_patient " . $where_clause;
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($param_types, ...$bind_params);
$count_stmt->execute();
$total_appointments = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_appointments / $items_per_page);

// Fetch appointments for current page
$query = "SELECT a.id_appointment, a.appointment_date, a.start_time, a.end_time, a.status,
                 d.id_doctor, d.first_name as doctor_first, d.last_name as doctor_last,
                 p.id_patient, p.first_name as patient_first, p.last_name as patient_last
          FROM appointment a
          JOIN doctor d ON a.id_doctor = d.id_doctor
          JOIN patient p ON a.id_patient = p.id_patient
          " . $where_clause . "
          ORDER BY a.appointment_date DESC, a.start_time DESC
          LIMIT ? OFFSET ?";
          
$stmt = $conn->prepare($query);
$bind_params[] = $items_per_page;
$bind_params[] = $offset;
$stmt->bind_param($param_types . "ii", ...$bind_params);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | HippoCare</title>
    <link rel="stylesheet" href="css/doctor_style.css">
    <link rel="stylesheet" href="css/doctor_forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="header-banner">
        <div class="logo-container">
            <a href="doctor_home.php" class="logo-link">
                <h1 class="logo-text">HippoCare</h1>
            </a>
        </div>
        <div class="header-actions">
            <span class="user-name">
                <span class="name-part">
                    <i class="fas fa-user-circle"></i>
                    Dr. <?php echo htmlspecialchars($doctor_name); ?>
                </span>
                <span class="date-part">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date("d F Y"); ?>
                </span>
            </span>
            <a href="doctor_settings.php" title="Settings" class="settings-btn">
                <i class="fas fa-cog"></i>
            </a>
            <a href="../../DB/logout.php" class="disconnect-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Disconnect</span>
            </a>
        </div>
    </div>

    <header>
        <h1>Appointments Management</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_app.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <section class="list-container">
            <div class="section-header">
                <h2><i class="fas fa-calendar-check"></i> Appointments (<?php echo $total_appointments; ?>)</h2>
                <a href="doctor_app_new.php" class="btn">
                    <i class="fas fa-plus-circle"></i> Add New Appointment
                </a>
            </div>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                        if($_GET['success'] == 'canceled') echo "Appointment has been canceled.";
                        elseif($_GET['success'] == 'completed') echo "Appointment marked as completed.";
                        elseif($_GET['success'] == 'missed') echo "Appointment marked as missed.";
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                        if($_GET['error'] == 'appointment_not_found') echo "Appointment not found.";
                        elseif($_GET['error'] == 'invalid_transition') echo "Cannot change appointment from " . htmlspecialchars($_GET['from'] ?? 'unknown') . " to " . htmlspecialchars($_GET['to'] ?? 'unknown') . " status.";
                        elseif($_GET['error'] == 'cannot_start_consultation') echo "Cannot start consultation for " . htmlspecialchars($_GET['status'] ?? 'unknown') . " appointment. Only pending appointments can be started.";
                        else echo htmlspecialchars($_GET['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filter Section -->
            <div class="search-filter-container">
                <form method="get" action="" class="search-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search by doctor or patient name..." 
                               value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
                        
                        <select name="status" class="search-input status-select">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending (All)</option>
                            <option value="pending_today" <?php echo $status_filter == 'pending_today' ? 'selected' : ''; ?>>Pending Today</option>
                            <option value="pending_upcoming" <?php echo $status_filter == 'pending_upcoming' ? 'selected' : ''; ?>>Pending Upcoming</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="canceled" <?php echo $status_filter == 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                            <option value="missed" <?php echo $status_filter == 'missed' ? 'selected' : ''; ?>>Missed</option>
                        </select>
                        
                        <button type="submit" class="btn-search btn-icon" title="Filter"><i class="fas fa-search"></i></button>
                        <?php if(!empty($search_query) || !empty($status_filter)): ?>
                            <a href="doctor_app.php" class="btn-clear btn-icon" title="Clear filters"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if($total_appointments > 0): ?>
                <div class="items-list">
                    <?php foreach($appointments as $appointment): ?>
                        <?php
                            // Calculate if appointment is today
                            $appointment_date = new DateTime($appointment['appointment_date']);
                            $today = new DateTime();
                            $today->setTime(0, 0, 0);
                            $appointment_date->setTime(0, 0, 0);
                            $is_today = $appointment_date == $today;
                            $is_past = $appointment_date < $today;
                            
                            // Determine appointment state
                            $state = 'pending_past';
                            if ($is_today) {
                                $state = 'pending_today';
                            } elseif ($appointment_date > $today) {
                                $state = 'pending_upcoming';
                            }
                        ?>
                        <article class="item-card">
                            <div class="item-content">
                                <h3><i class="fas fa-calendar-check"></i> Appointment #<?php echo sprintf('%04d', $appointment['id_appointment']); ?></h3>
                                <p class="item-meta">
                                    <span><i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></span>
                                    <span><i class="fas fa-user-injured"></i> <?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?></span>
                                </p>
                                <p class="item-meta">
                                    <span><i class="fas fa-calendar"></i> <?php echo (new DateTime($appointment['appointment_date']))->format('d/m/Y'); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($appointment['start_time']) . ' - ' . htmlspecialchars($appointment['end_time']); ?></span>
                                </p>
                            </div>
                            <div class="item-actions">
                                <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                    <?php echo ucfirst($appointment['status']); ?> (<?php echo $state === 'pending_today' ? 'Today' : ($state === 'pending_upcoming' ? 'Upcoming' : 'Past'); ?>)
                                </span>
                                
                                <?php if($appointment['status'] == 'pending'): ?>
                                    <?php if($is_today): ?>
                                        <a href="?action=start&id=<?php echo $appointment['id_appointment']; ?>" class="btn btn-success btn-icon" title="Start Consultation">
                                            <i class="fas fa-play"></i>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary btn-icon btn-disabled" title="Cannot start consultation for future appointments" disabled>
                                            <i class="fas fa-clock"></i>
                                        </button>
                                    <?php endif; ?>
                                    <a href="doctor_app_new.php?old_appointment_id=<?php echo $appointment['id_appointment']; ?>" class="btn btn-secondary btn-icon" title="Reschedule">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn btn-danger btn-icon" title="Cancel" onclick="return confirmCancel(<?php echo $appointment['id_appointment']; ?>);">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php elseif($appointment['status'] == 'completed'): ?>
                                    <a href="doctor_consult.php?appointment_id=<?php echo $appointment['id_appointment']; ?>" class="btn btn-secondary btn-icon" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php elseif($appointment['status'] == 'canceled'): ?>
                                    <a href="doctor_consult.php?appointment_id=<?php echo $appointment['id_appointment']; ?>" class="btn btn-secondary btn-icon" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="doctor_consult.php?appointment_id=<?php echo $appointment['id_appointment']; ?>" class="btn btn-secondary btn-icon" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>No appointments found</p>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($current_page > 1): ?>
                        <a href="?page=<?php echo ($current_page - 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn-page">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <div class="page-numbers">
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if($start_page > 1): ?>
                            <a href="?page=1<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn-page">1</a>
                            <?php if($start_page > 2): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                               class="btn-page <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if($end_page < $total_pages): ?>
                            <?php if($end_page < $total_pages - 1): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn-page"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($current_page < $total_pages): ?>
                        <a href="?page=<?php echo ($current_page + 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn-page">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="pagination-info">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $items_per_page, $total_appointments); ?> of <?php echo $total_appointments; ?> appointments
                </div>
            <?php endif; ?>
        </section>
    </main>
    
    <script>
    function confirmCancel(appointmentId) {
        if(confirm('Are you sure you want to cancel this appointment?\n\nThis action cannot be undone!')) {
            window.location.href = '?action=cancel&id=' + appointmentId + '&confirm=yes';
            return true;
        }
        return false;
    }
    </script>
</body>
</html>
