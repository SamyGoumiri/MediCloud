<?php
session_start();
require_once("../../DB/connect.php");
require_once("../../DB/secure_page.php");

secure_page('chief_doctor', $conn);

$id_doctor = $_SESSION["id_doctor"];

// Get doctor info
$stmt = $conn->prepare("SELECT first_name, last_name FROM doctor WHERE id_doctor = ?");
$stmt->bind_param("i", $id_doctor);
$stmt->execute();
$result = $stmt->get_result();
$doctor_data = $result->fetch_assoc();
$doctor_name = $doctor_data['first_name'] . ' ' . $doctor_data['last_name'];

// Get appointment ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: chief_doctor_app.php?error=missing_id");
    exit();
}

$appointment_id = intval($_GET['id']);

// Get appointment (chief doctor can reschedule any appointment)
$stmt = $conn->prepare("SELECT a.*, d.id_doctor, p.id_patient, p.first_name as patient_first, p.last_name as patient_last 
                       FROM appointment a 
                       JOIN doctor d ON a.id_doctor = d.id_doctor
                       JOIN patient p ON a.id_patient = p.id_patient 
                       WHERE a.id_appointment = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header("Location: chief_doctor_app.php?error=unauthorized");
    exit();
}

// Check if appointment can be rescheduled
if (in_array($appointment['status'], ['completed', 'canceled', 'missed'])) {
    header("Location: chief_doctor_app.php?error=cannot_reschedule_" . $appointment['status']);
    exit();
}

$patient_id = $appointment['id_patient'];
$appointment_doctor_id = $appointment['id_doctor'];
$patient_name = $appointment['patient_first'] . ' ' . $appointment['patient_last'];
$error_message = '';
$success_message = '';

// Process reschedule request
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule_appointment'])) {
    $new_date = $_POST['appointment_date'];
    $new_start_time = $_POST['start_time'];
    $new_end_time = date('H:i:s', strtotime($new_start_time . ' + 1 hour'));
    
    // Validate
    $now = new DateTime('now');
    $appointment_datetime = new DateTime($new_date . ' ' . $new_start_time);
    $interval = $now->diff($appointment_datetime);
    
    if ($interval->invert === 1) {
        $error_message = "Cannot reschedule to the past";
    } else if (date('N', strtotime($new_date)) == 5) {
        $error_message = "Cannot reschedule to Friday - Clinic Closed";
    } else {
        // Check for conflicts (excluding current appointment)
        $conflict_check = $conn->prepare("SELECT COUNT(*) as count FROM appointment 
                                         WHERE id_doctor = ? AND appointment_date = ? 
                                         AND id_appointment != ? AND status != 'canceled' 
                                         AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))");
        $conflict_check->bind_param("isisssssss", $appointment_doctor_id, $new_date, $appointment_id, 
                                    $new_end_time, $new_start_time, 
                                    $new_start_time, $new_end_time,
                                    $new_start_time, $new_end_time);
        $conflict_check->execute();
        $result = $conflict_check->get_result();
        $row = $result->fetch_assoc();
        
        if($row['count'] > 0) {
            $error_message = "This time slot is already booked or overlaps with another appointment";
        } else {
            $stmt = $conn->prepare("UPDATE appointment SET appointment_date = ?, start_time = ?, end_time = ? WHERE id_appointment = ?");
            $stmt->bind_param("sssi", $new_date, $new_start_time, $new_end_time, $appointment_id);
            
            if($stmt->execute()) {
                header("Location: chief_doctor_app.php?success=rescheduled");
                exit();
            } else {
                $error_message = "Error rescheduling appointment";
            }
        }
    }
}

// Calendar setup
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
$is_friday = (date('N', strtotime($selected_date)) == 5);

// Time slots
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

// Get booked appointments for the appointment's doctor
$booked_slots = [];
if (!$is_past_date && !$is_friday) {
    $stmt = $conn->prepare("SELECT start_time FROM appointment WHERE id_doctor = ? AND appointment_date = ? AND status != 'canceled' AND id_appointment != ?");
    $stmt->bind_param("isi", $appointment_doctor_id, $selected_date, $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $booked_slots[] = $row['start_time'];
    }
}

// Get doctor schedule for selected day (for the appointment's doctor)
$day_name = strtolower(date('l', strtotime($selected_date)));
$stmt = $conn->prepare("SELECT start_time, end_time FROM schedule WHERE id_doctor = ? AND day = ?");
$stmt->bind_param("is", $appointment_doctor_id, $day_name);
$stmt->execute();
$schedule_result = $stmt->get_result();
$doctor_schedule = $schedule_result->fetch_assoc();
$doctor_works_this_day = ($doctor_schedule !== null);

// Check Fridays
$fridays = [];
for($day = 1; $day <= $num_days; $day++) {
    $check_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    if(date('N', strtotime($check_date)) == 5) {
        $fridays[$day] = true;
    }
}

function renderTimeSlot($start, $end, $is_booked, $is_past_date, $is_friday, $selected_date, $appointment_id, $patient_id, $doctor_works) {
    $is_past_time = ($selected_date == date('Y-m-d') && $start <= date('H:i:s'));
    
    $class = $is_booked ? 'booked' : 'available';
    if($is_past_date || $is_friday || $is_past_time || !$doctor_works) {
        $class .= ' past-date';
    }
    
    $display_start = date('H:i', strtotime($start));
    $display_end = date('H:i', strtotime($end));
    
    if(!$is_past_date && !$is_friday && !$is_booked && !$is_past_time && $doctor_works): ?>
        <form method="post" action="" class="time-slot <?php echo $class; ?>">
            <div class="time-range"><?php echo $display_start . ' - ' . $display_end; ?></div>
            <div class="booking-status"><i class="fas fa-clock"></i> Available</div>
            <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
            <input type="hidden" name="appointment_date" value="<?php echo $selected_date; ?>">
            <input type="hidden" name="start_time" value="<?php echo $start; ?>">
            <div class="booking-actions">
                <button type="submit" name="reschedule_appointment" class="btn">Reschedule Here</button>
            </div>
        </form>
    <?php else: ?>
        <div class="time-slot <?php echo $class; ?>">
            <div class="time-range"><?php echo $display_start . ' - ' . $display_end; ?></div>
            <div class="booking-status">
                <?php if($is_booked): ?>
                    <i class="fas fa-times-circle"></i> Already Booked
                <?php elseif($is_past_time): ?>
                    <i class="fas fa-ban"></i> Time has passed
                <?php elseif($is_past_date): ?>
                    <i class="fas fa-ban"></i> Past Date
                <?php elseif($is_friday): ?>
                    <i class="fas fa-ban"></i> Friday - Clinic Closed
                <?php elseif(!$doctor_works): ?>
                    <i class="fas fa-ban"></i> Doctor Not Available
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
    <title>Reschedule Appointment | HippoCare</title>
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
                    Dr. <?php echo htmlspecialchars($doctor_name); ?>
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
        <h1>Reschedule Appointment</h1>
    </header>

    <nav>
        <fieldset>
            <legend>Menu</legend>
            <div class="menu-links">
                <a href="chief_doctor_home.php"><i class="fas fa-home"></i> Home</a>
                <a href="chief_doctor_assistants.php"><i class="fas fa-user-nurse"></i> Assistants</a>
                <a href="chief_doctor_doctors.php"><i class="fas fa-user-md"></i> Doctors</a>
                <a href="chief_doctor_patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                <a href="chief_doctor_app.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="chief_doctor_schedule.php"><i class="fas fa-calendar"></i> Schedule</a>
            </div>
        </fieldset>
    </nav>

    <main>
        <?php if($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="appointment-info-banner">
            <h2><i class="fas fa-info-circle"></i> Current Appointment</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Patient:</strong> <?php echo htmlspecialchars($patient_name); ?>
                </div>
                <div class="info-item">
                    <strong>Current Date:</strong> <?php echo date('l, F j, Y', strtotime($appointment['appointment_date'])); ?>
                </div>
                <div class="info-item">
                    <strong>Current Time:</strong> <?php echo date('H:i', strtotime($appointment['start_time'])); ?> - <?php echo date('H:i', strtotime($appointment['end_time'])); ?>
                </div>
            </div>
        </div>

        <div class="calendar-container">
            <div class="calendar-navigation">
                <?php if(!$is_current_month): ?>
                <a href="?id=<?php echo $appointment_id; ?>&month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="nav-btn">
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
                <a href="?id=<?php echo $appointment_id; ?>&month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="nav-btn">
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
                            
                            $cell_class = 'calendar-day';
                            if($is_today_cell) $cell_class .= ' today';
                            if($is_selected) $cell_class .= ' selected';
                            if($is_past) $cell_class .= ' past';
                            if($is_friday_cell) $cell_class .= ' friday';
                            
                            echo '<td class="' . $cell_class . '">';
                            if(!$is_past) {
                                echo '<a href="?id=' . $appointment_id . '&day=' . $day . '&month=' . $month . '&year=' . $year . '" class="day-link">' . $day . '</a>';
                            } else {
                                echo '<span class="day-number">' . $day . '</span>';
                            }
                            echo '</td>';
                            
                            $current_day_of_week++;
                            if($current_day_of_week > 7) {
                                echo '</tr><tr>';
                                $current_day_of_week = 1;
                            }
                        }
                        
                        while($current_day_of_week <= 7 && $current_day_of_week > 1) {
                            echo '<td class="empty-cell"></td>';
                            $current_day_of_week++;
                        }
                        ?>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="time-slots-container">
            <h2>Available Time Slots for <?php echo date('l, F j, Y', strtotime($selected_date)); ?></h2>
            
            <?php if($is_past_date): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    This date is in the past. Please select a future date.
                </div>
            <?php elseif($is_friday): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Clinic is closed on Fridays. Please select another day.
                </div>
            <?php elseif(!$doctor_works_this_day): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    You don't work on this day according to your schedule. Please select another day or update your schedule.
                </div>
            <?php else: ?>
                <div class="time-period">
                    <h3><i class="fas fa-sun"></i> Morning Slots</h3>
                    <div class="time-slots-grid">
                        <?php
                        foreach($time_slots['morning'] as $start => $end) {
                            $is_booked = in_array($start, $booked_slots);
                            renderTimeSlot($start, $end, $is_booked, $is_past_date, $is_friday, $selected_date, $appointment_id, $patient_id, $doctor_works_this_day);
                        }
                        ?>
                    </div>
                </div>

                <div class="time-period">
                    <h3><i class="fas fa-cloud-sun"></i> Afternoon Slots</h3>
                    <div class="time-slots-grid">
                        <?php
                        foreach($time_slots['afternoon'] as $start => $end) {
                            $is_booked = in_array($start, $booked_slots);
                            renderTimeSlot($start, $end, $is_booked, $is_past_date, $is_friday, $selected_date, $appointment_id, $patient_id, $doctor_works_this_day);
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions" style="margin-top: 20px;">
            <a href="chief_doctor_app.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Appointments
            </a>
        </div>
    </main>

    <footer>
        <p>&copy; 2023-2026 HippoCare. All rights reserved.</p>
    </footer>
</body>
</html>
