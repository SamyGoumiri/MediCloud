<?php
/**
 * Sync Helper - Platform Sync Functions
 * Location: MediCloud/assets/backend/config/sync_helper.php
 * Contains platform sync functions for cross-database synchronization
 */

if (!function_exists('get_platform_connection')) {
    function get_platform_connection($host = 'localhost', $user = 'root', $pass = '', $db = 'medicloud') {
        $conn = mysqli_connect($host, $user, $pass, $db);
        
        if (!$conn) {
            error_log('Platform DB Connection Error: ' . mysqli_connect_error());
            return null;
        }
        
        mysqli_set_charset($conn, 'utf8mb4');
        return $conn;
    }
}

if (!function_exists('sync_consultation_feedbacks')) {
    function sync_consultation_feedbacks($cabinet_conn, $platform_conn, $cabinet_id) {
        if (!$cabinet_conn || !$platform_conn) {
            return ['success' => false, 'message' => 'Database connection error'];
        }
        
        try {
            $cabinet_conn->begin_transaction();
            $platform_conn->begin_transaction();
            
            // Get unsynced feedbacks from cabinet database
            $stmt = $cabinet_conn->prepare("
                SELECT cf.id, cf.id_patient, cf.id_doctor, cf.id_consultation, 
                       cf.accueil, cf.ponctualite, cf.disponibilite, cf.competence,
                       cf.experience, cf.equipement, cf.hygiene, cf.securite,
                       cf.parking, cf.cout, cf.comments, cf.created_at
                FROM consultation_feedback cf
                WHERE cf.synced_to_platform = FALSE
                ORDER BY cf.created_at ASC
            ");
            
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $cabinet_conn->error);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $synced_count = 0;
            
            while ($feedback = $result->fetch_assoc()) {
                // Insert into platform feedbacks table
                $insert_stmt = $platform_conn->prepare("
                    INSERT INTO feedbacks (
                        cabinet_id, doctor_id, consultation_id,
                        rating_reception, rating_punctuality, rating_availability, 
                        rating_skills, rating_experience, rating_equipment,
                        rating_hygiene, rating_security, rating_parking, rating_price,
                        comment, source_database, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                if (!$insert_stmt) {
                    throw new Exception('Insert prepare failed: ' . $platform_conn->error);
                }
                
                $source_db = 'hippocare_' . $cabinet_id;
                
                $insert_stmt->bind_param(
                    'iiiiiiiiiiiiisss',
                    $cabinet_id,
                    $feedback['id_doctor'],
                    $feedback['id_consultation'],
                    $feedback['accueil'],
                    $feedback['ponctualite'],
                    $feedback['disponibilite'],
                    $feedback['competence'],
                    $feedback['experience'],
                    $feedback['equipement'],
                    $feedback['hygiene'],
                    $feedback['securite'],
                    $feedback['parking'],
                    $feedback['cout'],
                    $feedback['comments'],
                    $source_db,
                    $feedback['created_at']
                );
                
                if (!$insert_stmt->execute()) {
                    throw new Exception('Insert failed: ' . $insert_stmt->error);
                }
                
                $platform_feedback_id = $platform_conn->insert_id;
                
                // Update cabinet feedback to mark as synced
                $update_stmt = $cabinet_conn->prepare("
                    UPDATE consultation_feedback 
                    SET synced_to_platform = TRUE, 
                        platform_feedback_id = ?,
                        synced_at = NOW()
                    WHERE id = ?
                ");
                
                if (!$update_stmt) {
                    throw new Exception('Update prepare failed: ' . $cabinet_conn->error);
                }
                
                $update_stmt->bind_param('ii', $platform_feedback_id, $feedback['id']);
                
                if (!$update_stmt->execute()) {
                    throw new Exception('Update failed: ' . $update_stmt->error);
                }
                
                $synced_count++;
            }
            
            $cabinet_conn->commit();
            $platform_conn->commit();
            
            return [
                'success' => true,
                'message' => "Synced $synced_count feedbacks to platform",
                'synced_count' => $synced_count,
                'cabinet_id' => $cabinet_id
            ];
            
        } catch (Exception $e) {
            $cabinet_conn->rollback();
            $platform_conn->rollback();
            
            error_log('Sync error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'cabinet_id' => $cabinet_id
            ];
        }
    }
}

if (!function_exists('get_cabinet_average_ratings')) {
    function get_cabinet_average_ratings($platform_conn, $cabinet_id) {
        if (!$platform_conn) {
            return null;
        }
        
        $stmt = $platform_conn->prepare("
            SELECT 
                cabinet_id,
                ROUND(AVG(rating_reception), 2) as avg_reception,
                ROUND(AVG(rating_punctuality), 2) as avg_punctuality,
                ROUND(AVG(rating_availability), 2) as avg_availability,
                ROUND(AVG(rating_skills), 2) as avg_skills,
                ROUND(AVG(rating_experience), 2) as avg_experience,
                ROUND(AVG(rating_equipment), 2) as avg_equipment,
                ROUND(AVG(rating_hygiene), 2) as avg_hygiene,
                ROUND(AVG(rating_security), 2) as avg_security,
                ROUND(AVG(rating_parking), 2) as avg_parking,
                ROUND(AVG(rating_price), 2) as avg_price,
                ROUND((
                    AVG(rating_reception) + AVG(rating_punctuality) + AVG(rating_availability) +
                    AVG(rating_skills) + AVG(rating_experience) + AVG(rating_equipment) +
                    AVG(rating_hygiene) + AVG(rating_security) + AVG(rating_parking) + AVG(rating_price)
                ) / 10, 2) as overall_rating,
                COUNT(*) as total_ratings
            FROM feedbacks
            WHERE cabinet_id = ?
            GROUP BY cabinet_id
        ");
        
        if (!$stmt) {
            error_log('Get ratings prepare failed: ' . $platform_conn->error);
            return null;
        }
        
        $stmt->bind_param('i', $cabinet_id);
        
        if (!$stmt->execute()) {
            error_log('Get ratings execute failed: ' . $stmt->error);
            return null;
        }
        
        return $stmt->get_result()->fetch_assoc();
    }
}

if (!function_exists('get_doctor_average_ratings')) {
    function get_doctor_average_ratings($platform_conn, $doctor_id) {
        if (!$platform_conn) {
            return null;
        }
        
        $stmt = $platform_conn->prepare("
            SELECT 
                doctor_id,
                ROUND(AVG(rating_reception), 2) as avg_reception,
                ROUND(AVG(rating_punctuality), 2) as avg_punctuality,
                ROUND(AVG(rating_availability), 2) as avg_availability,
                ROUND(AVG(rating_skills), 2) as avg_skills,
                ROUND(AVG(rating_experience), 2) as avg_experience,
                ROUND(AVG(rating_equipment), 2) as avg_equipment,
                ROUND(AVG(rating_hygiene), 2) as avg_hygiene,
                ROUND(AVG(rating_security), 2) as avg_security,
                ROUND(AVG(rating_parking), 2) as avg_parking,
                ROUND(AVG(rating_price), 2) as avg_price,
                ROUND((
                    AVG(rating_reception) + AVG(rating_punctuality) + AVG(rating_availability) +
                    AVG(rating_skills) + AVG(rating_experience) + AVG(rating_equipment) +
                    AVG(rating_hygiene) + AVG(rating_security) + AVG(rating_parking) + AVG(rating_price)
                ) / 10, 2) as overall_rating,
                COUNT(*) as total_ratings
            FROM feedbacks
            WHERE doctor_id = ?
            GROUP BY doctor_id
        ");
        
        if (!$stmt) return null;
        
        $stmt->bind_param('i', $doctor_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
}

?>
