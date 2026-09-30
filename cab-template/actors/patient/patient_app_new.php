<?php
session_start();
if(!isset($_SESSION["id_patient"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== 'patient') {
    session_destroy();
    header("Location: patient_auth_login.php");
    exit();
}

require_once '../../DB/connect.php';

$id_patient = $_SESSION["id_patient"];

// Get patient info
$stmt = $conn->prepare("SELECT last_name, first_name FROM patient WHERE id_patient = ?");
$stmt->bind_param("i", $id_patient);
$stmt->execute();
$patient_data = $stmt->get_result()->fetch_assoc();
$patient_name = $patient_data['first_name'] . ' ' . $patient_data['last_name'];

// Get all doctors for selection
$doctors = [];
$stmt = $conn->prepare("SELECT id_doctor, first_name, last_name, speciality FROM doctor ORDER BY last_name");
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

$selected_doctor = isset($_GET['doctor_id']) && is_numeric($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$doctor_name_selected = '';

// Handle reschedule - check if old_appointment_id is provided
$old_appointment_id = 0;
$is_rescheduling = false;

if (isset($_GET['old_appointment_id']) && is_numeric($_GET['old_appointment_id'])) {
    $old_appointment_id = intval($_GET['old_appointment_id']);
    $is_rescheduling = true;
    
    // Store in session for persistence
    $_SESSION['old_appointment_id'] = $old_appointment_id;
    
    // Load old appointment details to prefill
    $stmt = $conn->prepare("SELECT a.id_doctor, d.first_name as doc_first, d.last_name as doc_last 
                            FROM appointment a
                            JOIN doctor d ON a.id_doctor = d.id_doctor
                            WHERE a.id_appointment = ? AND a.id_patient = ?");
    $stmt->bind_param("ii", $old_appointment_id, $id_patient);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $old_appointment = $result->fetch_assoc();
        
        // Prefill doctor selection
        $_SESSION['reschedule_doctor_id'] = $old_appointment['id_doctor'];
        
        $selected_doctor = $old_appointment['id_doctor'];
        $doctor_name_selected = $old_appointment['doc_first'] . ' ' . $old_appointment['doc_last'];
    }
} else if (isset($_SESSION['old_appointment_id'])) {
    // Load from session if page is reloaded
    $old_appointment_id = $_SESSION['old_appointment_id'];
    $is_rescheduling = true;
    if (isset($_SESSION['reschedule_doctor_id'])) {
        $selected_doctor = $_SESSION['reschedule_doctor_id'];
    }
}

if($selected_doctor > 0 && empty($doctor_name_selected)) {
    foreach($doctors as $doc) {
        if($doc['id_doctor'] == $selected_doctor) {
            $doctor_name_selected = $doc['first_name'] . ' ' . $doc['last_name'];
            break;
        }
    }
}

$error_message = '';
$success_message = '';

// Handle appointment booking
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    if($selected_doctor > 0) {
        $id_doctor = $selected_doctor;
        $appointment_date = trim($_POST['appointment_date']);
        $start_time = trim($_POST['start_time']);
        
        // Calculate end time (1 hour appointment)
        $end_time = date('H:i:s', strtotime($start_time) + 3600);
        
        // Verify date is not in the past
        if($appointment_date < date('Y-m-d')) {
            $error_message = "Cannot book appointments in the past";
        } else {
            // Check if slot is available
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointment WHERE id_doctor = ? AND appointment_date = ? AND start_time = ? AND status != 'canceled'");
            $stmt->bind_param("iss", $id_doctor, $appointment_date, $start_time);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            // Check for overlapping appointments
            $overlap_check = $conn->prepare("SELECT COUNT(*) as count FROM appointment WHERE id_doctor = ? AND appointment_date = ? AND status != 'canceled' AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))");
            $overlap_check->bind_param("isssss", $id_doctor, $appointment_date, $end_time, $start_time, $start_time, $end_time);
            $overlap_check->execute();
            $overlap_result = $overlap_check->get_result();
            $overlap_row = $overlap_result->fetch_assoc();
            
            if($row['count'] > 0 || $overlap_row['count'] > 0) {
                $error_message = "This time slot is already booked or overlaps with another appointment";
            } else {
                // If rescheduling, delete old appointment first
                if (isset($_SESSION['old_appointment_id']) && $_SESSION['old_appointment_id'] > 0) {
                    $old_id = $_SESSION['old_appointment_id'];
                    $delete_stmt = $conn->prepare("DELETE FROM appointment WHERE id_appointment = ? AND id_patient = ?");
                    $delete_stmt->bind_param("ii", $old_id, $id_patient);
                    $delete_stmt->execute();
                }
                
                $stmt = $conn->prepare("INSERT INTO appointment (id_doctor, id_patient, appointment_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iisss", $id_doctor, $id_patient, $appointment_date, $start_time, $end_time);
                
                if($stmt->execute()) {
                    $doctor_stmt = $conn->prepare("SELECT first_name, last_name FROM doctor WHERE id_doctor = ?");
                    $doctor_stmt->bind_param("i", $id_doctor);
                    $doctor_stmt->execute();
                    $doctor_data = $doctor_stmt->get_result()->fetch_assoc();
                    
                    $doctor_full_name = $doctor_data['first_name'] . ' ' . $doctor_data['last_name'];
                    $success_message = "Appointment with Dr. " . $doctor_full_name . " scheduled on " . date('l, F j, Y', strtotime($appointment_date)) . " at " . date('H:i', strtotime($start_time)) . ".";
                    
                    // Clear reschedule session data
                    unset($_SESSION['old_appointment_id']);
                    unset($_SESSION['reschedule_doctor_id']);
                } else {
                    $error_message = "Error booking appointment";
                }
            }
        }
    } else {
        $error_message = "Please select a doctor";
    }
}

$current_month = intval(date('m'));
$current_year = intval(date('Y'));

$month = isset($_GET['month']) ? intval($_GET['month']) : $current_month;
$year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;

$max_future_timestamp = strtotime('+6 months');
$max_future_month = intval(date('m', $max_future_timestamp));
$max_future_year = intval(date('Y', $max_future_timestamp));

$current_timestamp = mktime(0, 0, 0, $month, 1, $year);
$now_timestamp = mktime(0, 0, 0, $current_month, 1, $current_year);
$max_timestamp = mktime(0, 0, 0, $max_future_month, 1, $max_future_year);

if ($current_timestamp < $now_timestamp) {
    $month = $current_month;
    $year = $current_year;
} else if ($current_timestamp > $max_timestamp) {
    $month = $max_future_month;
    $year = $max_future_year;
}

$num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day_timestamp = mktime(0, 0, 0, $month, 1, $year);
$first_day_of_week = date('N', $first_day_timestamp);

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
$is_max_future_month = ($current_timestamp >= $max_timestamp);

$selected_day = isset($_GET['day']) ? intval($_GET['day']) : intval(date('d'));
if($selected_day < 1 || $selected_day > $num_days) {
    $selected_day = date('d');
}

$selected_date = sprintf('%04d-%02d-%02d', $year, $month, $selected_day);
$current_date = date('Y-m-d');
$is_past_date = ($selected_date < $current_date);
$is_today = ($selected_date == $current_date);
$is_friday = (date('N', strtotime($selected_date)) == 5);

$time_slots = [
    'morning' => [
        '08:00:00' => '09:00:00',
        '09:00:00' => '10:00:00',
        '10:00:00' => '11:00:00',
        '11:00:00' => '12:00:00'
    ],
    'afternoon' => [
        '13:00:00' => '14:00:00',
        '14:00:00' => '15:00:00',
        '15:00:00' => '16:00:00',
        '16:00:00' => '17:00:00'
    ]
];

$total_slots_per_day = count($time_slots['morning']) + count($time_slots['afternoon']);

$booked_slots = [];
if($selected_doctor > 0) {
    $stmt = $conn->prepare("SELECT a.id_appointment, a.start_time, a.end_time, a.id_patient, a.status 
                           FROM appointment a 
                           WHERE a.id_doctor = ? AND a.appointment_date = ? AND a.status != 'canceled'");
    $stmt->bind_param("is", $selected_doctor, $selected_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $booked_slots[$row['start_time']] = $row;
    }
}

$month_bookings = [];
$fully_booked_days = [];
$start_date = sprintf('%04d-%02d-01', $year, $month);
$end_date = sprintf('%04d-%02d-%02d', $year, $month, $num_days);

if($selected_doctor > 0) {
    $stmt = $conn->prepare("SELECT DATE_FORMAT(appointment_date, '%d') as day, COUNT(*) as total_bookings 
                           FROM appointment 
                           WHERE id_doctor = ? AND appointment_date BETWEEN ? AND ? AND status != 'canceled' 
                           GROUP BY appointment_date");
    $stmt->bind_param("iss", $selected_doctor, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $month_bookings[$row['day']] = $row['total_bookings'];
        if($row['total_bookings'] >= $total_slots_per_day) {
            $fully_booked_days[$row['day']] = true;
        }
    }
}

$fridays = [];
for($day = 1; $day <= $num_days; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    if(date('N', strtotime($date)) == 5) {
        $fridays[$day] = true;
    }
}

function renderTimeSlot($start, $end, $is_booked, $is_past_date, $is_friday, $selected_doctor, $selected_date) {
    $is_past_time = ($selected_date == date('Y-m-d') && $start <= date('H:i:s'));
    
    $class = $is_booked ? 'booked' : 'available';
    if($is_past_date || $is_friday || $is_past_time) {
        $class .= ' past-date';
    }
    
    $display_start = date('H:i', strtotime($start));
    $display_end = date('H:i', strtotime($end));
    
    if($selected_doctor > 0 && !$is_past_date && !$is_friday && !$is_booked && !$is_past_time): ?>
        <form method="post" action="" class="time-slot <?php echo $class; ?>">
            <div class="time-range"><?php echo $display_start . ' - ' . $display_end; ?></div>
            <div class="booking-status"><i class="fas fa-clock"></i> Available</div>
            <input type="hidden" name="doctor_id" value="<?php echo $selected_doctor; ?>">
            <input type="hidden" name="appointment_date" value="<?php echo $selected_date; ?>">
            <input type="hidden" name="start_time" value="<?php echo $start; ?>">
            <div class="booking-actions">
                <button type="submit" name="book_appointment" class="btn">Book Appointment</button>
            </div>
        </form>
    <?php else: ?>
        <div class="time-slot <?php echo $class; ?>">
            <div class="time-range"><?php echo $display_start . ' - ' . $display_end; ?></div>
            <div class="booking-status">
                <?php if($selected_doctor <= 0): ?>
                    <i class="fas fa-exclamation-circle"></i> Select a doctor first
                <?php elseif($is_booked): ?>
                    <i class="fas fa-times-circle"></i> Already Booked
                <?php elseif($is_past_time): ?>
                    <i class="fas fa-ban"></i> Time has passed
                <?php elseif($is_past_date): ?>
                    <i class="fas fa-ban"></i> Past Date
                <?php elseif($is_friday): ?>
                    <i class="fas fa-ban"></i> Friday - Clinic Closed
                <?php endif; ?>
            </div>
        </div>
    <?php endif;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule New Appointment | HippoCare</title>
    <link rel="stylesheet" href="css/patient_style.css">
    <link rel="stylesheet" href="css/patient_forms.css">
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
        <h1>Schedule New Appointment</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="patient_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="patient_app.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="patient_consult.php"><i class="fas fa-notes-medical"></i> Consultations</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <div class="breadcrumb">
            <a href="patient_app.php">Appointments</a> &gt; Schedule New Appointment
        </div>
        
        <?php if(isset($success_message) && !empty($success_message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error_message) && !empty($error_message)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="selection-container">
            <div class="doctor-selection">
                <h3><i class="fas fa-user-md"></i> Select Doctor</h3>
                
                <form method="get" id="doctor-form" class="doctor-search-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="doctor_id">Doctor:</label>
                            <select id="doctor_id" name="doctor_id" required onchange="document.getElementById('doctor-form').submit();">
                                <option value="">Select a doctor</option>
                                <?php foreach($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['id_doctor']; ?>" <?php echo $selected_doctor == $doctor['id_doctor'] ? 'selected' : ''; ?>>
                                        Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?> - <?php echo htmlspecialchars($doctor['speciality']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <!-- Preserve date/month when changing doctor -->
                    <?php if(isset($_GET['month']) && isset($_GET['year'])): ?>
                        <input type="hidden" name="month" value="<?php echo $month; ?>">
                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <?php endif; ?>
                    <?php if(isset($_GET['day'])): ?>
                        <input type="hidden" name="day" value="<?php echo $selected_day; ?>">
                    <?php endif; ?>
                </form>
                
                <?php if($selected_doctor > 0): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i> Doctor selected: Dr. <?php echo htmlspecialchars($doctor_name_selected); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="calendar-container">
            <div class="calendar-navigation">
                <?php if(!$is_current_month): ?>
                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?><?php echo $selected_doctor ? '&doctor_id='.$selected_doctor : ''; ?>" class="nav-btn">
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
                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?><?php echo $selected_doctor ? '&doctor_id='.$selected_doctor : ''; ?>" class="nav-btn">
                    Next Month <i class="fas fa-chevron-right"></i>
                </a>
                <?php else: ?>
                <span class="nav-btn hidden">
                    Next Month <i class="fas fa-chevron-right"></i>
                </span>
                <?php endif; ?>
            </div>
            
            <table class="calendar">
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
                            $is_selected = ($day == $selected_day);
                            $is_past = ($date < date('Y-m-d'));
                            $is_friday_cell = isset($fridays[$day]);
                            
                            $day_string = sprintf('%02d', $day);
                                    
                            $class = '';
                            if($is_today_cell) $class .= ' today';
                            if($is_selected) $class .= ' selected';
                            if($is_past) $class .= ' past-date';
                            if($is_friday_cell) $class .= ' blocked-date';
                            
                            echo '<td class="' . trim($class) . '">';
                            
                            if($is_past || $is_friday_cell) {
                                echo '<div class="calendar-cell">';
                                echo '<span class="day-number past-date">' . $day . '</span>';
                                
                                if($is_friday_cell) {
                                    echo '<div class="blocked-reason">Friday - Clinic Closed</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '<a href="?month=' . $month . '&year=' . $year . '&day=' . $day . ($selected_doctor ? '&doctor_id='.$selected_doctor : '') . '" class="calendar-cell">';
                                echo '<span class="day-number">' . $day . '</span>';
                                
                                if(isset($month_bookings[$day_string])) {
                                    echo '<span class="appointments-count">' . $month_bookings[$day_string] . ' booked</span>';
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

        <div class="schedule-container">
            <div class="schedule-header">
                <h3>
                    <i class="fas fa-calendar-day"></i> 
                    Available Appointments for <?php echo date('l, F j, Y', strtotime($selected_date)); ?>
                </h3>
                <div>
                    <?php if($selected_doctor <= 0): ?>
                        <div class="message error">
                            <i class="fas fa-exclamation-triangle"></i> Please select a doctor before booking an appointment.
                        </div>
                    <?php elseif($is_past_date): ?>
                        <div class="message error">
                            <i class="fas fa-exclamation-triangle"></i> This date has already passed. Booking is disabled.
                        </div>
                    <?php elseif($is_friday): ?>
                        <div class="message error">
                            <i class="fas fa-ban"></i> Friday - Clinic Closed. Booking is disabled.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h3><i class="fas fa-sun"></i> Morning</h3>
            <div class="time-slots">
                <?php 
                foreach($time_slots['morning'] as $start => $end) {
                    $is_booked = isset($booked_slots[$start]);
                    renderTimeSlot($start, $end, $is_booked, $is_past_date, $is_friday, 
                                  $selected_doctor, $selected_date);
                }
                ?>
            </div>
            
            <h3><i class="fas fa-moon"></i> Afternoon</h3>
            <div class="time-slots">
                <?php 
                foreach($time_slots['afternoon'] as $start => $end) {
                    $is_booked = isset($booked_slots[$start]);
                    renderTimeSlot($start, $end, $is_booked, $is_past_date, $is_friday, 
                                  $selected_doctor, $selected_date);
                }
                ?>
            </div>
        </div>
    </main>
    
    <script src="js/validation.js"></script>
</body>
</html>
