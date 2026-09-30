<?php
/**
 * Appointment Reschedule/Edit Helper Functions
 * Include this file in pages that need to edit appointments
 */

/**
 * Reschedule an appointment to a new date/time
 * @param mysqli $conn Database connection
 * @param int $appointment_id Appointment ID
 * @param string $new_date New date (Y-m-d)
 * @param string $new_start_time New start time (H:i:s)
 * @param int $user_id ID of user making the change
 * @param string $user_role Role of user (for authorization)
 * @return array ['success' => bool, 'message' => string]
 */
function reschedule_appointment($conn, $appointment_id, $new_date, $new_start_time, $user_id, $user_role) {
    // Get current appointment details
    $stmt = $conn->prepare("SELECT id_doctor, id_patient, appointment_date, start_time, status 
                           FROM appointment WHERE id_appointment = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        return ['success' => false, 'message' => 'Appointment not found'];
    }
    
    $appointment = $result->fetch_assoc();
    
    // Authorization check
    $authorized = false;
    if ($user_role === 'chief_doctor' || $user_role === 'assistant') {
        $authorized = true;
    } elseif ($user_role === 'doctor' && $appointment['id_doctor'] == $user_id) {
        $authorized = true;
    } elseif ($user_role === 'patient' && $appointment['id_patient'] == $user_id) {
        $authorized = true;
    }
    
    if (!$authorized) {
        return ['success' => false, 'message' => 'Unauthorized to modify this appointment'];
    }
    
    // Check if appointment can be rescheduled
    if ($appointment['status'] === 'completed') {
        return ['success' => false, 'message' => 'Cannot reschedule completed appointments'];
    }
    
    if ($appointment['status'] === 'missed') {
        return ['success' => false, 'message' => 'Cannot reschedule missed appointments'];
    }

    if ($appointment['status'] === 'canceled') {
        return ['success' => false, 'message' => 'Cannot reschedule canceled appointments'];
    }
    
    // Validate minimum 24-hour notice period
    $now = new DateTime('now');
    $appointment_datetime = new DateTime($new_date . ' ' . $new_start_time);
    $interval = $now->diff($appointment_datetime);
    $hours_until_appointment = ($interval->days * 24) + $interval->h;
    
    if ($interval->invert === 1) {
        // Date/time is in the past
        return ['success' => false, 'message' => 'Cannot schedule in the past'];
    }
    
    if ($hours_until_appointment < 24) {
        return ['success' => false, 'message' => 'Appointments must be scheduled at least 24 hours in advance'];
    }
    
    // Check if Friday
    if (date('N', strtotime($new_date)) == 5) {
        return ['success' => false, 'message' => 'Cannot schedule on Friday - Clinic Closed'];
    }
    
    // Check if date is in the past
    $today = date('Y-m-d');
    if ($new_date < $today) {
        return ['success' => false, 'message' => 'Cannot schedule in the past'];
    }
    
    // Check doctor's schedule
    $day_name = strtolower(date('l', strtotime($new_date)));
    $schedule_check = $conn->prepare("SELECT id_schedule FROM schedule 
                                     WHERE id_doctor = ? AND day = ? 
                                     AND start_time <= ? AND end_time > ?");
    $schedule_check->bind_param("isss", $appointment['id_doctor'], $day_name, $new_start_time, $new_start_time);
    $schedule_check->execute();
    
    if ($schedule_check->get_result()->num_rows == 0) {
        return ['success' => false, 'message' => 'Doctor not available at this time'];
    }
    
    // Check for conflicts (excluding current appointment) - check for overlapping times
    $conflict_check = $conn->prepare("SELECT id_appointment FROM appointment 
                                     WHERE id_doctor = ? 
                                     AND appointment_date = ? 
                                     AND status != 'canceled' 
                                     AND id_appointment != ?
                                     AND (
                                         (start_time < ? AND end_time > ?)
                                         OR (start_time < ? AND end_time > ?)
                                         OR (start_time >= ? AND end_time <= ?)
                                     )");
    $conflict_check->bind_param("ississsss", $appointment['id_doctor'], $new_date, $appointment_id, 
                                $new_end_time, $new_start_time, 
                                $new_start_time, $new_end_time,
                                $new_start_time, $new_end_time);
    $conflict_check->execute();
    
    if ($conflict_check->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Time slot already booked'];
    }
    
    // Update appointment
    $conn->begin_transaction();
    
    try {
        $update_stmt = $conn->prepare("UPDATE appointment 
                                       SET appointment_date = ?, 
                                           start_time = ?, 
                                           end_time = ? 
                                       WHERE id_appointment = ?");
        $update_stmt->bind_param("sssi", $new_date, $new_start_time, $new_end_time, $appointment_id);
        $update_stmt->execute();
        
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => 'Appointment rescheduled to ' . date('l, F j, Y', strtotime($new_date)) . ' at ' . date('H:i', strtotime($new_start_time))
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Error rescheduling appointment'];
    }
}
?>
