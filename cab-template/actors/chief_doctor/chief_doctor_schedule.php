<?php
session_start();
require_once '../../DB/connect.php';
require_once '../../DB/secure_page.php';

secure_page('chief_doctor', $conn);

$id_chief_doctor = $_SESSION["id_chief_doctor"];

$stmt = $conn->prepare("SELECT id_doctor, last_name, first_name FROM doctor WHERE id_doctor = ?");
$stmt->bind_param("i", $id_chief_doctor);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    session_destroy();
    header("Location: chief_doctor_auth_login.php");
    exit();
}

$chief_doctor_data = $result->fetch_assoc();
$chief_doctor_name = $chief_doctor_data['first_name'] . ' ' . $chief_doctor_data['last_name'];

function isFriday($date) {
    return date('N', strtotime($date)) == 5;
}

// Get all doctors managed by this chief doctor for filter
$doctors_list = [];
$stmt = $conn->prepare("SELECT id_doctor, first_name, last_name FROM doctor ORDER BY last_name");
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $doctors_list[] = $row;
}

// Get selected doctor for filtering
$selected_doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

// If no doctor selected, default to first doctor
if ($selected_doctor_id === 0 && count($doctors_list) > 0) {
    $selected_doctor_id = $doctors_list[0]['id_doctor'];
}

$current_month = date('m');
$current_year = date('Y');

$month = isset($_GET['month']) ? intval($_GET['month']) : $current_month;
$year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;

$max_future_month = date('m', strtotime('+6 months'));
$max_future_year = date('Y', strtotime('+6 months'));

$current_date = mktime(0, 0, 0, $month, 1, $year);
$now_date = mktime(0, 0, 0, $current_month, 1, $current_year);
$max_date = mktime(0, 0, 0, $max_future_month, 1, $max_future_year);

if ($current_date < $now_date) {
    $month = $current_month;
    $year = $current_year;
} else if ($current_date > $max_date) {
    $month = $max_future_month;
    $year = $max_future_year;
}

$num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day_of_week = date('N', mktime(0, 0, 0, $month, 1, $year));

$prev_month = $month - 1;
$prev_year = $year;
if($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$is_current_month = ($month == $current_month && $year == $current_year);
$is_max_future_month = ($current_date >= $max_date);

$selected_day = isset($_GET['day']) ? intval($_GET['day']) : date('d');
if($selected_day < 1 || $selected_day > $num_days) {
    $selected_day = date('d');
}

$selected_date = sprintf('%04d-%02d-%02d', $year, $month, $selected_day);
$is_friday = isFriday($selected_date);

$month_appointments = [];
$date_with_appointments = [];

$start_date = sprintf('%04d-%02d-01', $year, $month);
$end_date = sprintf('%04d-%02d-%02d', $year, $month, $num_days);

// Build query with optional doctor filter
if($selected_doctor_id > 0) {
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(appointment_date, '%d') as day, COUNT(*) as total_appointments
        FROM appointment 
        WHERE id_doctor = ? AND appointment_date BETWEEN ? AND ? 
        AND status != 'canceled'
        GROUP BY appointment_date
    ");
    $stmt->bind_param("iss", $selected_doctor_id, $start_date, $end_date);
} else {
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(appointment_date, '%d') as day, COUNT(*) as total_appointments
        FROM appointment 
        WHERE appointment_date BETWEEN ? AND ? 
        AND status != 'canceled'
        GROUP BY appointment_date
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
}

$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $month_appointments[$row['day']] = $row['total_appointments'];
    $date_with_appointments[$row['day']] = true;
}
$stmt->close();

$selected_date_appointments = [];
if($selected_doctor_id > 0) {
    $stmt = $conn->prepare("
        SELECT a.id_appointment, a.start_time, a.end_time, a.status, 
               d.id_doctor, d.first_name as doctor_first_name, d.last_name as doctor_last_name,
               p.id_patient, p.first_name, p.last_name, p.phone, p.email
        FROM appointment a
        JOIN doctor d ON a.id_doctor = d.id_doctor
        JOIN patient p ON a.id_patient = p.id_patient
        WHERE a.id_doctor = ? AND a.appointment_date = ? 
        AND a.status != 'canceled'
        ORDER BY a.start_time ASC
    ");
    $stmt->bind_param("is", $selected_doctor_id, $selected_date);
} else {
    $stmt = $conn->prepare("
        SELECT a.id_appointment, a.start_time, a.end_time, a.status, 
               d.id_doctor, d.first_name as doctor_first_name, d.last_name as doctor_last_name,
               p.id_patient, p.first_name, p.last_name, p.phone, p.email
        FROM appointment a
        JOIN doctor d ON a.id_doctor = d.id_doctor
        JOIN patient p ON a.id_patient = p.id_patient
        WHERE a.appointment_date = ? 
        AND a.status != 'canceled'
        ORDER BY a.start_time ASC
    ");
    $stmt->bind_param("s", $selected_date);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $selected_date_appointments[] = $row;
}

// Get list of doctors for filter dropdown
$doctors = [];
$stmt = $conn->prepare("SELECT id_doctor, first_name, last_name, speciality FROM doctor ORDER BY last_name");
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

$fridays = [];
for($day = 1; $day <= $num_days; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    if(isFriday($date)) {
        $fridays[$day] = 'Friday - Clinic Closed';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule | HippoCare</title>
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
        <h1>Schedule Management</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="chief_doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="chief_doctor_doctors.php"><i class="fas fa-user-md"></i> Doctors</a>
                <a href="chief_doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="chief_doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="chief_doctor_app.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="chief_doctor_schedule.php" class="active"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <section class="filter-section">
            <div class="filter-container">
                <h3><i class="fas fa-filter"></i> Filter by Doctor</h3>
                <form method="get" class="filter-form">
                    <div class="form-group">
                        <label for="doctor_id">Select Doctor:</label>
                        <select id="doctor_id" name="doctor_id" onchange="this.form.submit()">
                            <option value="">All Doctors</option>
                            <?php foreach($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['id_doctor']; ?>" <?php echo $selected_doctor_id == $doctor['id_doctor'] ? 'selected' : ''; ?>>
                                    Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?> (<?php echo htmlspecialchars($doctor['speciality']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="month" value="<?php echo $month; ?>">
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="day" value="<?php echo $selected_day; ?>">
                </form>
            </div>
        </section>

        <section class="calendar-section">
            <div class="calendar-container">
                <div class="calendar-navigation">
                    <?php if(!$is_current_month): ?>
                    <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?><?php echo $selected_doctor_id ? '&doctor_id='.$selected_doctor_id : ''; ?>" class="nav-btn">
                        <i class="fas fa-chevron-left"></i> Previous Month
                    </a>
                    <?php else: ?>
                    <span class="nav-btn hidden">
                        <i class="fas fa-chevron-left"></i> Previous Month
                    </span>
                    <?php endif; ?>
                    <div class="calendar-month">
                        <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?>
                    </div>
                    <?php if(!$is_max_future_month): ?>
                    <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?><?php echo $selected_doctor_id ? '&doctor_id='.$selected_doctor_id : ''; ?>" class="nav-btn">
                        Next Month <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php else: ?>
                    <span class="nav-btn hidden">
                        Next Month <i class="fas fa-chevron-right"></i>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="calendar-table-wrapper">
                    <table class="calendar" role="presentation">
                        <thead>
                            <tr>
                                <th>Monday</th>
                                <th>Tuesday</th>
                                <th>Wednesday</th>
                                <th>Thursday</th>
                                <th>Friday</th>
                                <th>Saturday</th>
                                <th>Sunday</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                for($i = 1; $i < $first_day_of_week; $i++) {
                                    echo '<td class="empty-cell"></td>';
                                }
                                
                                $current_day_of_week = $first_day_of_week;
                                
                                for($day = 1; $day <= $num_days; $day++) {
                                    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                    $is_today_cell = ($date == date('Y-m-d'));
                                    $is_selected = ($day == $selected_day && $month == intval(date('m', strtotime($selected_date))) && $year == intval(date('Y', strtotime($selected_date))));
                                    
                                    $day_string = sprintf('%02d', $day);
                                    
                                    $is_friday_cell = isset($fridays[$day]);
                                    $has_appointments = isset($date_with_appointments[$day_string]);
                                    
                                    $class = '';
                                    if($is_today_cell) $class .= ' today';
                                    if($is_selected) $class .= ' selected';
                                    if($is_friday_cell) $class .= ' blocked-date';
                                    if($has_appointments) $class .= ' has-appointments';
                                    
                                    echo '<td class="' . trim($class) . '">';
                                    
                                    if($is_friday_cell) {
                                        echo '<div class="calendar-cell">';
                                        echo '<span class="day-number">' . $day . '</span>';
                                        echo '<div class="blocked-reason">' . htmlspecialchars($fridays[$day]) . '</div>';
                                        echo '</div>';
                                    } else {
                                        echo '<a href="?month=' . $month . '&year=' . $year . '&day=' . $day . ($selected_doctor_id ? '&doctor_id='.$selected_doctor_id : '') . '" class="calendar-cell" title="View schedule for day ' . $day . '">';
                                        echo '<span class="day-number">' . $day . '</span>';
                                        
                                        if(isset($month_appointments[$day_string])) {
                                            echo '<span class="appointments-count">' . $month_appointments[$day_string] . ' appointment' . ($month_appointments[$day_string] > 1 ? 's' : '') . '</span>';
                                        }
                                        
                                        echo '</a>';
                                    }
                                    
                                    echo '</td>';
                                    
                                    $current_day_of_week++;
                                    
                                    if($current_day_of_week > 7) {
                                        echo '</tr>';
                                        
                                        if($day < $num_days) {
                                            echo '<tr>';
                                        }
                                        
                                        $current_day_of_week = 1;
                                    }
                                }
                                
                                for($i = $current_day_of_week; $i <= 7; $i++) {
                                    echo '<td class="empty-cell"></td>';
                                }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        
        <section class="appointments-section">
            <div class="appointments-container">
                <div class="appointments-header">
                    <h3>
                        <i class="fas fa-calendar-day"></i> 
                        Schedule for <?php echo date('l, F j, Y', strtotime($selected_date)); ?>
                    </h3>
                    
                    <?php if ($is_friday): ?>
                        <div class="system-block-controls">
                            <div class="message warning">
                                <i class="fas fa-exclamation-triangle"></i> Friday - Clinic Closed
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$is_friday): ?>
                    <?php if (empty($selected_date_appointments)): ?>
                        <div class="no-appointments">
                            <i class="fas fa-calendar-times"></i>
                            <p>No appointments scheduled for this date.</p>
                        </div>
                    <?php else: ?>
                        <div class="appointments-list">
                            <?php foreach ($selected_date_appointments as $appointment): ?>
                                <article class="appointment-card status-<?php echo strtolower($appointment['status']); ?>">
                                    <div class="appointment-doctor">
                                        <i class="fas fa-user-md"></i>
                                        <span>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></span>
                                    </div>
                                    <div class="appointment-time">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo date('H:i', strtotime($appointment['start_time'])); ?> - <?php echo date('H:i', strtotime($appointment['end_time'])); ?></span>
                                    </div>
                                    <div class="appointment-patient">
                                        <h4><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></h4>
                                        <div class="patient-details">
                                            <p><i class="fas fa-id-card"></i> <span>Patient ID: <?php echo $appointment['id_patient']; ?></span></p>
                                            <p><i class="fas fa-phone"></i> <span><?php echo htmlspecialchars($appointment['phone']); ?></span></p>
                                            <p><i class="fas fa-envelope"></i> <span><?php echo htmlspecialchars($appointment['email']); ?></span></p>
                                        </div>
                                    </div>
                                    <div class="appointment-status">
                                        <span class="status-badge"><?php echo ucfirst($appointment['status']); ?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-appointments">
                        <i class="fas fa-ban"></i>
                        <p>This date is blocked by the system. No appointments can be scheduled on Fridays.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
<script src="js/validation.js"></script>
</body>
</html>

