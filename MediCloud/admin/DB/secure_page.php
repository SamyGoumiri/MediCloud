<?php
if (!function_exists('secure_admin_page')) {
    function secure_admin_page($conn = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
            session_destroy();
            header("Location: login.php");
            exit();
        }
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            header("Location: login.php?error=session_expired");
            exit();
        }
        
        $_SESSION['last_activity'] = time();
        
        if ($conn !== null) {
            $admin_id = $_SESSION['admin_id'];
            $stmt = $conn->prepare("SELECT id, prenom, nom, email FROM admin_users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                session_destroy();
                header("Location: login.php?error=invalid_session");
                exit();
            }
            
            return $result->fetch_assoc();
        }
        
        return true;
    }
}

if (!function_exists('get_admin_id')) {
    function get_admin_id() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['admin_id'] ?? null;
    }
}

if (!function_exists('get_admin_name')) {
    function get_admin_name() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $prenom = $_SESSION['admin_prenom'] ?? '';
        $nom = $_SESSION['admin_nom'] ?? '';
        return trim("$prenom $nom");
    }
}
?>
