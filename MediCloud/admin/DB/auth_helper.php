<?php
function admin_login($conn, $email, $password) {
    $stmt = $conn->prepare("SELECT id, prenom, nom, email, password FROM admin_users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    $admin = $result->fetch_assoc();
    
    if (!password_verify($password, $admin['password'])) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    session_regenerate_id(true);
    
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_prenom'] = $admin['prenom'];
    $_SESSION['admin_nom'] = $admin['nom'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['last_activity'] = time();
    
    return ['success' => true, 'message' => 'Login successful'];
}

function admin_logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    session_unset();
    session_destroy();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    header("Location: login.php?message=logged_out");
    exit();
}

function update_admin_password($conn, $admin_id, $current_password, $new_password) {
    $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Admin not found'];
    }
    
    $admin = $result->fetch_assoc();
    
    if (!password_verify($current_password, $admin['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $update_stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $admin_id);
    
    if ($update_stmt->execute()) {
        return ['success' => true, 'message' => 'Password updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to update password'];
}

function update_admin_profile($conn, $admin_id, $prenom, $nom, $email) {
    $check_stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ? LIMIT 1");
    $check_stmt->bind_param("si", $email, $admin_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already in use'];
    }
    
    $update_stmt = $conn->prepare("UPDATE admin_users SET prenom = ?, nom = ?, email = ? WHERE id = ?");
    $update_stmt->bind_param("sssi", $prenom, $nom, $email, $admin_id);
    
    if ($update_stmt->execute()) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['admin_prenom'] = $prenom;
        $_SESSION['admin_nom'] = $nom;
        $_SESSION['admin_email'] = $email;
        
        return ['success' => true, 'message' => 'Profile updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to update profile'];
}
?>
