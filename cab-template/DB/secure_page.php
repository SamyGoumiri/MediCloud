<?php
/**
 * Secure Page Protection for All Actors
 * Include this at the top of every protected page
 * 
 * Usage:
 * require_once '../DB/secure_page.php';
 * secure_page('doctor'); // or 'chief_doctor', 'assistant', 'patient'
 */

if (!function_exists('secure_page')) {
    /**
     * Secure a page with role-based authentication and verification
     * @param string $required_role The role required to access this page
     * @param mysqli|null $conn Optional database connection for additional verification
     */
    function secure_page($required_role, $conn = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Map roles to session variables
        $session_map = [
            'doctor' => 'id_doctor',
            'chief_doctor' => 'id_chief_doctor',
            'assistant' => 'id_assistant',
            'patient' => 'id_patient'
        ];
        
        // Map roles to login pages
        $login_map = [
            'doctor' => 'doctor_auth_login.php',
            'chief_doctor' => 'chief_doctor_auth_login.php',
            'assistant' => 'assistant_auth_login.php',
            'patient' => 'patient_auth_login.php'
        ];
        
        if (!isset($session_map[$required_role]) || !isset($login_map[$required_role])) {
            die('Invalid role specified');
        }
        
        $session_key = $session_map[$required_role];
        $login_page = $login_map[$required_role];
        
        // Check if user is logged in with correct role
        if (!isset($_SESSION[$session_key]) || 
            !isset($_SESSION['user_role']) || 
            $_SESSION['user_role'] !== $required_role) {
            session_destroy();
            header("Location: " . $login_page);
            exit();
        }
        
        // Additional database verification if connection provided
        if ($conn !== null) {
            $user_id = $_SESSION[$session_key];
            
            if ($required_role === 'doctor') {
                $stmt = $conn->prepare("SELECT id_doctor FROM doctor WHERE id_doctor = ? AND role = 'doctor' LIMIT 1");
                $stmt->bind_param("i", $user_id);
            } elseif ($required_role === 'chief_doctor') {
                $stmt = $conn->prepare("SELECT id_doctor FROM doctor WHERE id_doctor = ? AND role = 'chief_doctor' LIMIT 1");
                $stmt->bind_param("i", $user_id);
            } elseif ($required_role === 'assistant') {
                $stmt = $conn->prepare("SELECT id_assistant FROM assistant WHERE id_assistant = ? LIMIT 1");
                $stmt->bind_param("i", $user_id);
            } elseif ($required_role === 'patient') {
                $stmt = $conn->prepare("SELECT id_patient FROM patient WHERE id_patient = ? LIMIT 1");
                $stmt->bind_param("i", $user_id);
            }
            
            if (isset($stmt)) {
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    session_destroy();
                    header("Location: " . $login_page . "?error=invalid_session");
                    exit();
                }
            }
        }
        
        return true;
    }
}

/**
 * Verify user owns a resource
 * @param mysqli $conn Database connection
 * @param string $resource_type Type of resource (appointment, patient, etc.)
 * @param int $resource_id ID of the resource
 * @param string|null $user_role Override role (uses session if not provided)
 * @return bool True if authorized
 */
function verify_ownership($conn, $resource_type, $resource_id, $user_role = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $role = $user_role ?? $_SESSION['user_role'] ?? null;
    
    if (!$role) {
        return false;
    }
    
    // Chief doctors and assistants have global access
    if ($role === 'chief_doctor' || $role === 'assistant') {
        return true;
    }
    
    switch ($resource_type) {
        case 'appointment':
            return verify_appointment_ownership($conn, $resource_id, $role);
        case 'patient':
            return verify_patient_ownership($conn, $resource_id, $role);
        case 'consultation':
            return verify_consultation_ownership($conn, $resource_id, $role);
        case 'doctor':
            return verify_doctor_ownership($conn, $resource_id, $role);
        default:
            return false;
    }
}

function verify_appointment_ownership($conn, $appointment_id, $role) {
    $session_key = ($role === 'doctor') ? 'id_doctor' : 'id_patient';
    $user_id = $_SESSION[$session_key] ?? 0;
    
    if ($role === 'doctor') {
        $stmt = $conn->prepare("SELECT id_appointment FROM appointment WHERE id_appointment = ? AND id_doctor = ?");
    } else {
        $stmt = $conn->prepare("SELECT id_appointment FROM appointment WHERE id_appointment = ? AND id_patient = ?");
    }
    
    $stmt->bind_param("ii", $appointment_id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function verify_patient_ownership($conn, $patient_id, $role) {
    if ($role === 'patient') {
        return $patient_id == ($_SESSION['id_patient'] ?? 0);
    }
    
    if ($role === 'doctor') {
        // Doctors can only access patients they have appointments with
        $doctor_id = $_SESSION['id_doctor'] ?? 0;
        $stmt = $conn->prepare("SELECT DISTINCT id_patient FROM appointment WHERE id_patient = ? AND id_doctor = ?");
        $stmt->bind_param("ii", $patient_id, $doctor_id);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    return false;
}

function verify_consultation_ownership($conn, $consultation_id, $role) {
    if ($role === 'doctor') {
        $doctor_id = $_SESSION['id_doctor'] ?? 0;
        $stmt = $conn->prepare("SELECT id_consult FROM consultation WHERE id_consult = ? AND id_doctor = ?");
        $stmt->bind_param("ii", $consultation_id, $doctor_id);
    } else if ($role === 'patient') {
        $patient_id = $_SESSION['id_patient'] ?? 0;
        $stmt = $conn->prepare("SELECT c.id_consult FROM consultation c 
                               JOIN appointment a ON c.id_appointment = a.id_appointment 
                               WHERE c.id_consult = ? AND a.id_patient = ?");
        $stmt->bind_param("ii", $consultation_id, $patient_id);
    } else {
        return false;
    }
    
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function verify_doctor_ownership($conn, $doctor_id, $role) {
    if ($role === 'chief_doctor') {
        return true;
    }
    
    if ($role === 'doctor') {
        return $doctor_id == ($_SESSION['id_doctor'] ?? 0);
    }
    
    return false;
}

/**
 * Check if doctor is available on specific day/time
 * @param mysqli $conn Database connection
 * @param int $doctor_id Doctor ID
 * @param string $appointment_date Date in Y-m-d format
 * @param string $start_time Time in H:i:s format
 * @return bool True if available
 */
function is_doctor_available($conn, $doctor_id, $appointment_date, $start_time) {
    // Check if it's Friday (clinic closed)
    if (date('N', strtotime($appointment_date)) == 5) {
        return false;
    }
    
    // Check doctor's schedule
    $day_name = strtolower(date('l', strtotime($appointment_date)));
    
    $stmt = $conn->prepare("SELECT id_schedule FROM schedule 
                           WHERE id_doctor = ? 
                           AND day = ? 
                           AND start_time <= ? 
                           AND end_time > ?");
    $stmt->bind_param("isss", $doctor_id, $day_name, $start_time, $start_time);
    $stmt->execute();
    
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Generate a CSRF token and store it in the session
 * @return string The CSRF token
 */
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}

/**
 * Verify a CSRF token from a form submission
 * @param string $token The token to verify
 * @return bool True if valid, false otherwise
 */
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Output a CSRF token hidden field for forms
 * @return void
 */
if (!function_exists('output_csrf_field')) {
    function output_csrf_field() {
        $token = generate_csrf_token();
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
?>
