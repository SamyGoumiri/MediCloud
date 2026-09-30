<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('doctor', $conn);

if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['assistant_id']) && isset($_GET['confirm'])) {
    if(!is_numeric($_GET['assistant_id'])) {
        header("Location: doctor_assistants.php");
        exit();
    }
    
    $assistant_id = intval($_GET['assistant_id']);
    
    // Start transaction for cascade delete
    $conn->begin_transaction();
    
    try {
        // No cascading deletes needed for assistants - they don't have dependent records
        // Assistants are linked to doctors but not critical to appointment workflow
        
        $stmt = $conn->prepare("DELETE FROM assistant WHERE id_assistant = ?");
        $stmt->bind_param("i", $assistant_id);
        
        if($stmt->execute()) {
            $conn->commit();
            header("Location: doctor_assistants.php?success=deleted");
            exit();
        } else {
            $conn->rollback();
            header("Location: doctor_assistants.php");
            exit();
        }
    } catch(Exception $e) {
        $conn->rollback();
        header("Location: doctor_assistants.php");
        exit();
    }
    exit();
}

$id_doctor = $_SESSION["id_doctor"];
$stmt = $conn->prepare("SELECT last_name, first_name FROM doctor WHERE id_doctor = ?");
$stmt->bind_param("i", $id_doctor);
$stmt->execute();
$doctor_data = $stmt->get_result()->fetch_assoc();
$doctor_name = $doctor_data['first_name'] . ' ' . $doctor_data['last_name'];

// Search functionality
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Filter functionality
$filter = isset($_GET['filter']) && $_GET['filter'] === 'all' ? 'all' : 'mine';

// Pagination settings
$items_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Count total assistants
if(!empty($search_query)) {
    $search_param = "%$search_query%";
    if($filter === 'mine') {
        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM assistant
                                      WHERE (last_name LIKE ? OR first_name LIKE ? OR email LIKE ?) AND id_doctor = ?");
        $count_stmt->bind_param("sssi", $search_param, $search_param, $search_param, $id_doctor);
    } else {
        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM assistant
                                      WHERE last_name LIKE ? OR first_name LIKE ? OR email LIKE ?");
        $count_stmt->bind_param("sss", $search_param, $search_param, $search_param);
    }
} else {
    if($filter === 'mine') {
        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM assistant WHERE id_doctor = ?");
        $count_stmt->bind_param("i", $id_doctor);
    } else {
        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM assistant");
    }
}
$count_stmt->execute();
$total_assistants = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_assistants / $items_per_page);

// Fetch assistants for current page
if(!empty($search_query)) {
    $search_param = "%$search_query%";
    if($filter === 'mine') {
        $stmt = $conn->prepare("SELECT a.id_assistant, a.last_name, a.first_name, a.email, a.phone, a.birth_date, a.id_doctor, 
                                       d.first_name as doctor_first_name, d.last_name as doctor_last_name
                                FROM assistant a
                                LEFT JOIN doctor d ON a.id_doctor = d.id_doctor
                                WHERE (a.last_name LIKE ? OR a.first_name LIKE ? OR a.email LIKE ?) AND a.id_doctor = ?
                                ORDER BY a.last_name ASC
                                LIMIT ? OFFSET ?");
        $stmt->bind_param("sssiii", $search_param, $search_param, $search_param, $id_doctor, $items_per_page, $offset);
    } else {
        $stmt = $conn->prepare("SELECT a.id_assistant, a.last_name, a.first_name, a.email, a.phone, a.birth_date, a.id_doctor, 
                                       d.first_name as doctor_first_name, d.last_name as doctor_last_name
                                FROM assistant a
                                LEFT JOIN doctor d ON a.id_doctor = d.id_doctor
                                WHERE a.last_name LIKE ? OR a.first_name LIKE ? OR a.email LIKE ?
                                ORDER BY a.last_name ASC
                                LIMIT ? OFFSET ?");
        $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $items_per_page, $offset);
    }
} else {
    if($filter === 'mine') {
        $stmt = $conn->prepare("SELECT a.id_assistant, a.last_name, a.first_name, a.email, a.phone, a.birth_date, a.id_doctor, 
                                       d.first_name as doctor_first_name, d.last_name as doctor_last_name
                                FROM assistant a
                                LEFT JOIN doctor d ON a.id_doctor = d.id_doctor
                                WHERE a.id_doctor = ?
                                ORDER BY a.last_name ASC
                                LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $id_doctor, $items_per_page, $offset);
    } else {
        $stmt = $conn->prepare("SELECT a.id_assistant, a.last_name, a.first_name, a.email, a.phone, a.birth_date, a.id_doctor, 
                                       d.first_name as doctor_first_name, d.last_name as doctor_last_name
                                FROM assistant a
                                LEFT JOIN doctor d ON a.id_doctor = d.id_doctor
                                ORDER BY a.last_name ASC
                                LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $items_per_page, $offset);
    }
}
$stmt->execute();
$assistants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant Management | HippoCare</title>
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
        <h1>Assistant Management</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="doctor_assistants.php" class="active"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <section class="list-container">
            <div class="section-header">
                <h2><i class="fas fa-user-nurse"></i> Assistants (<?php echo $total_assistants; ?>)</h2>
                <a href="doctor_assistants_new.php" class="btn">
                    <i class="fas fa-plus-circle"></i> Add Assistant
                </a>
            </div>
                        
            <div class="filter-tabs">
                <a href="?filter=mine<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="filter-tab <?php echo $filter === 'mine' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> My Assistants
                </a>
                <a href="?filter=all<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> All Assistants
                </a>
            </div>
            
            <div class="search-filter-container">
                <form method="get" action="" class="search-form">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
                        <button type="submit" class="btn-search btn-icon" title="Search"><i class="fas fa-search"></i></button>
                        <?php if(!empty($search_query)): ?>
                            <a href="doctor_assistants.php?filter=<?php echo htmlspecialchars($filter); ?>" class="btn-clear btn-icon" title="Clear search"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <?php if(isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Assistant has been successfully deleted.
                </div>
            <?php endif; ?>
                        <?php if($total_assistants > 0): ?>
                <div class="items-list">
                    <?php foreach($assistants as $assistant): ?>
                        <?php 
                            $initials = strtoupper(substr($assistant['first_name'], 0, 1) . substr($assistant['last_name'], 0, 1));
                            $birth_date = new DateTime($assistant['birth_date']);
                            $age = (new DateTime())->diff($birth_date)->y;
                            $doctor_name = "Unassigned";
                            if($assistant['id_doctor'] && $assistant['doctor_first_name']) {
                                $doctor_name = $assistant['doctor_first_name'] . ' ' . $assistant['doctor_last_name'];
                            }
                        ?>
                        <article class="item-card">
                            <div class="item-avatar">
                                <div class="avatar"><?php echo $initials; ?></div>
                            </div>
                            <div class="item-content">
                                <h3><?php echo htmlspecialchars($assistant['last_name'] . ' ' . $assistant['first_name']); ?></h3>
                                <p class="item-meta">
                                    <span><i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($doctor_name); ?></span>
                                    <span><i class="fas fa-birthday-cake"></i> <?php echo $birth_date->format('d/m/Y') . ' (' . $age . ' years)'; ?></span>
                                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($assistant['phone']); ?></span>
                                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($assistant['email']); ?></span>
                                </p>
                                <p class="item-id">ID: AST-<?php echo sprintf('%03d', $assistant['id_assistant']); ?></p>
                            </div>
                            <div class="item-actions">
                                <a href="doctor_assistants_infos.php?assistant_id=<?php echo $assistant['id_assistant']; ?>" class="btn btn-secondary btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="#" class="btn btn-danger btn-icon" title="Delete" onclick="return confirmDeleteAssistant(<?php echo $assistant['id_assistant']; ?>, '<?php echo addslashes($assistant['first_name'] . ' ' . $assistant['last_name']); ?>');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>No assistants found</p>
                </div>
            <?php endif; ?>
            
            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($current_page > 1): ?>
                        <a href="?page=<?php echo ($current_page - 1); ?>&filter=<?php echo htmlspecialchars($filter); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-page">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <div class="page-numbers">
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if($start_page > 1): ?>
                            <a href="?page=1&filter=<?php echo htmlspecialchars($filter); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-page">1</a>
                            <?php if($start_page > 2): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&filter=<?php echo htmlspecialchars($filter); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                               class="btn-page <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if($end_page < $total_pages): ?>
                            <?php if($end_page < $total_pages - 1): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?>&filter=<?php echo htmlspecialchars($filter); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-page"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($current_page < $total_pages): ?>
                        <a href="?page=<?php echo ($current_page + 1); ?>&filter=<?php echo htmlspecialchars($filter); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-page">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="pagination-info">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $items_per_page, $total_assistants); ?> of <?php echo $total_assistants; ?> assistants
                </div>
            <?php endif; ?>
        </section>
    </main>
    
    <script>
    function confirmDeleteAssistant(assistantId, assistantName) {
        let message = '⚠️ WARNING: This will permanently delete:\n\n';
        message += '• Assistant: ' + assistantName + '\n';
        message += '\nThis action CANNOT be undone!\n\n';
        message += 'Are you sure you want to delete this assistant?';
        
        if(confirm(message)) {
            window.location.href = 'doctor_assistants.php?action=delete&assistant_id=' + assistantId + '&confirm=yes';
            return true;
        }
        return false;
    }
    </script>
</body>
</html>
