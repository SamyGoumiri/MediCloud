<?php
require_once 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get consultation ID and appointment ID
$id_consultation = isset($_POST['id_consultation']) ? intval($_POST['id_consultation']) : 0;
$id_appointment = isset($_POST['id_appointment']) ? intval($_POST['id_appointment']) : 0;

if ($id_consultation <= 0 || $id_appointment <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid consultation or appointment ID']);
    exit;
}

// Get rating values
$ratings = [
    'accueil' => isset($_POST['accueil']) ? intval($_POST['accueil']) : 0,
    'ponctualite' => isset($_POST['ponctualite']) ? intval($_POST['ponctualite']) : 0,
    'disponibilite' => isset($_POST['disponibilite']) ? intval($_POST['disponibilite']) : 0,
    'competence' => isset($_POST['competence']) ? intval($_POST['competence']) : 0,
    'experience' => isset($_POST['experience']) ? intval($_POST['experience']) : 0,
    'equipement' => isset($_POST['equipement']) ? intval($_POST['equipement']) : 0,
    'hygiene' => isset($_POST['hygiene']) ? intval($_POST['hygiene']) : 0,
    'securite' => isset($_POST['securite']) ? intval($_POST['securite']) : 0,
    'parking' => isset($_POST['parking']) ? intval($_POST['parking']) : 0,
    'cout' => isset($_POST['cout']) ? intval($_POST['cout']) : 0
];

// Validate all ratings are between 1-5
foreach ($ratings as $key => $value) {
    if ($value < 1 || $value > 5) {
        echo json_encode(['success' => false, 'message' => "Invalid rating for $key"]);
        exit;
    }
}

// Get comments (optional)
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

// Check if this is an edit operation
$is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] == 1;
$feedback_id = isset($_POST['feedback_id']) ? intval($_POST['feedback_id']) : 0;

// Verify consultation belongs to patient and is completed
session_start();
$id_patient = $_SESSION['id_patient'];

$verify_stmt = $conn->prepare("
    SELECT c.id_consult, a.id_doctor
    FROM consultation c
    JOIN appointment a ON c.id_appointment = a.id_appointment
    WHERE c.id_consult = ? 
    AND a.id_appointment = ?
    AND a.id_patient = ?
    AND a.status = 'completed'
");
$verify_stmt->bind_param("iii", $id_consultation, $id_appointment, $id_patient);
$verify_stmt->execute();
$result = $verify_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid consultation']);
    exit;
}

$consultation_data = $result->fetch_assoc();
$id_doctor = $consultation_data['id_doctor'];

// Check if feedback already exists
$check_stmt = $conn->prepare("SELECT id FROM consultation_feedback WHERE id_consultation = ?");
$check_stmt->bind_param("i", $id_consultation);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$existing_feedback = $check_result->fetch_assoc();

if ($is_edit) {
    // Update existing feedback
    if (!$existing_feedback || $existing_feedback['id'] != $feedback_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid feedback ID']);
        exit;
    }
    
    $stmt = $conn->prepare("
        UPDATE consultation_feedback SET
            accueil = ?,
            ponctualite = ?,
            disponibilite = ?,
            competence = ?,
            experience = ?,
            equipement = ?,
            hygiene = ?,
            securite = ?,
            parking = ?,
            cout = ?,
            comments = ?
        WHERE id = ? AND id_consultation = ?
    ");
    
    $stmt->bind_param(
        "iiiiiiiiiisii",
        $ratings['accueil'],
        $ratings['ponctualite'],
        $ratings['disponibilite'],
        $ratings['competence'],
        $ratings['experience'],
        $ratings['equipement'],
        $ratings['hygiene'],
        $ratings['securite'],
        $ratings['parking'],
        $ratings['cout'],
        $comments,
        $feedback_id,
        $id_consultation
    );
    
    if ($stmt->execute()) {
        updateMediCloudFeedback($conn, $id_consultation, $ratings, $comments);
        
        echo json_encode(['success' => true, 'message' => 'Feedback updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
} else {
    // Insert new feedback
    if ($existing_feedback) {
        echo json_encode(['success' => false, 'message' => 'Feedback already submitted']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO consultation_feedback (
            id_consultation,
            id_patient,
            id_doctor,
            accueil,
            ponctualite,
            disponibilite,
            competence,
            experience,
            equipement,
            hygiene,
            securite,
            parking,
            cout,
            comments
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iiiiiiiiiiiiis",
        $id_consultation,
        $id_patient,
        $id_doctor,
        $ratings['accueil'],
        $ratings['ponctualite'],
        $ratings['disponibilite'],
        $ratings['competence'],
        $ratings['experience'],
        $ratings['equipement'],
        $ratings['hygiene'],
        $ratings['securite'],
        $ratings['parking'],
        $ratings['cout'],
        $comments
    );

    if ($stmt->execute()) {
        $feedback_id_inserted = $conn->insert_id;
        
        // Sync to MediCloud platform
        syncToMediCloud($conn, $id_consultation, $id_doctor, $feedback_id_inserted, $ratings, $comments);
        
        echo json_encode(['success' => true, 'message' => 'Feedback saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
}

/**
 * Update feedback in MediCloud platform
 */
function updateMediCloudFeedback($conn, $medicloud_feedback_id, $ratings, $comments) {
    $medicloud_conn = mysqli_connect('localhost', 'root', '', 'medicloud');
    if (!$medicloud_conn) {
        error_log('Failed to connect to MediCloud: ' . mysqli_connect_error());
        return;
    }
    
    $update_sql = "UPDATE feedbacks SET
        rating_security = ?, rating_equipment = ?, rating_hygiene = ?, rating_availability = ?,
        rating_skills = ?, rating_experience = ?, rating_location = ?, rating_price = ?,
        rating_reception = ?, rating_punctuality = ?, comment = ?
        WHERE consultation_id = ?";
    
    $update_stmt = $medicloud_conn->prepare($update_sql);
    if (!$update_stmt) {
        error_log('Failed to prepare MediCloud update: ' . $medicloud_conn->error);
        mysqli_close($medicloud_conn);
        return;
    }
    
    $update_stmt->bind_param(
        'iiiiiiiiiiis',
        $ratings['securite'],
        $ratings['equipement'],
        $ratings['hygiene'],
        $ratings['disponibilite'],
        $ratings['competence'],
        $ratings['experience'],
        $ratings['parking'],
        $ratings['cout'],
        $ratings['accueil'],
        $ratings['ponctualite'],
        $comments,
        $medicloud_feedback_id
    );
    
    if ($update_stmt->execute()) {
        error_log("Successfully updated MediCloud feedback ID $medicloud_feedback_id");
    } else {
        error_log("Failed to update MediCloud feedback: " . $update_stmt->error);
    }
    
    $update_stmt->close();
    mysqli_close($medicloud_conn);
}

/**
 * Sync feedback to MediCloud platform
 * Assumes all HippoCare doctors belong to the first cabinet in MediCloud
 */
function syncToMediCloud($conn, $id_consultation, $id_doctor, $feedback_id, $ratings, $comments) {
    // Use HippoCare database connection
    // Get MediCloud connection
    $medicloud_conn = mysqli_connect('localhost', 'root', '', 'medicloud');
    if (!$medicloud_conn) {
        error_log('Failed to connect to MediCloud: ' . mysqli_connect_error());
        return;
    }
    
    // Map HippoCare ratings to MediCloud column names
    // HippoCare: accueil, ponctualite, disponibilite, competence, experience, equipement, hygiene, securite, parking, cout
    // MediCloud: rating_reception, rating_punctuality, rating_availability, rating_skills, rating_experience, rating_equipment, rating_hygiene, rating_security, rating_location, rating_price
    
    $mapping = [
        'accueil' => 'rating_reception',
        'ponctualite' => 'rating_punctuality',
        'disponibilite' => 'rating_availability',
        'competence' => 'rating_skills',
        'experience' => 'rating_experience',
        'equipement' => 'rating_equipment',
        'hygiene' => 'rating_hygiene',
        'securite' => 'rating_security',
        'parking' => 'rating_location',  // Parking -> Location (cabinet location rating)
        'cout' => 'rating_price'
    ];
    
    // Get cabinet ID - assume first active cabinet (change if needed for multi-cabinet setup)
    $cabinet_result = $medicloud_conn->query("SELECT id FROM cabinets WHERE statut = 'active' LIMIT 1");
    $cabinet_row = $cabinet_result->fetch_assoc();
    $cabinet_id = $cabinet_row ? $cabinet_row['id'] : 1;
    
    // Get doctor ID in MediCloud by mapping specialty
    $doctor_result = $medicloud_conn->prepare(
        "SELECT d.id FROM doctors d 
         WHERE d.cabinet_id = ? AND d.specialty = (SELECT speciality FROM doctor WHERE id_doctor = ?)"
    );
    $doctor_result->bind_param('ii', $cabinet_id, $id_doctor);
    $doctor_result->execute();
    $doctor_row = $doctor_result->get_result()->fetch_assoc();
    $medicloud_doctor_id = $doctor_row ? $doctor_row['id'] : NULL;
    
    // Insert into MediCloud feedbacks table
    $insert_sql = "INSERT INTO feedbacks (
        cabinet_id, doctor_id, consultation_id,
        rating_security, rating_equipment, rating_hygiene, rating_availability,
        rating_skills, rating_experience, rating_location, rating_price,
        rating_reception, rating_punctuality,
        comment, source_database
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'hippocare')";
    
    $insert_stmt = $medicloud_conn->prepare($insert_sql);
    if (!$insert_stmt) {
        error_log('Failed to prepare MediCloud insert: ' . $medicloud_conn->error);
        mysqli_close($medicloud_conn);
        return;
    }
    
    $insert_stmt->bind_param(
        'iiiiiiiiiiis',
        $cabinet_id,
        $medicloud_doctor_id,
        $id_consultation,
        $ratings['securite'],
        $ratings['equipement'],
        $ratings['hygiene'],
        $ratings['disponibilite'],
        $ratings['competence'],
        $ratings['experience'],
        $ratings['parking'],
        $ratings['cout'],
        $ratings['accueil'],
        $ratings['ponctualite'],
        $comments
    );
    
    if ($insert_stmt->execute()) {
        $medicloud_feedback_id = $medicloud_conn->insert_id;
        
        // Update HippoCare feedback record to mark as synced
        $update_stmt = $conn->prepare(
            "UPDATE consultation_feedback SET synced_to_platform = 1, platform_feedback_id = ?, synced_at = NOW() WHERE id = ?"
        );
        $update_stmt->bind_param('ii', $medicloud_feedback_id, $feedback_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        error_log("Successfully synced feedback ID $feedback_id to MediCloud feedback ID $medicloud_feedback_id");
    } else {
        error_log("Failed to sync feedback to MediCloud: " . $insert_stmt->error);
    }
    
    $insert_stmt->close();
    $doctor_result->close();
    mysqli_close($medicloud_conn);
}

$stmt->close();
$conn->close();
?>
