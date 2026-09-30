<?php
/**
 * Cabinet Sync Helper Functions
 * Location: MediCloud/admin/DB/cabinet_sync_helper.php
 * Manages synchronization of feedback from all cabinets to platform
 * Follows the modular pattern used in other helpers
 */

if (!function_exists('get_all_cabinets_for_sync')) {
    function get_all_cabinets_for_sync($conn) {
        if (!$conn) {
            return ['success' => false, 'message' => 'No database connection'];
        }
        
        try {
            $stmt = $conn->prepare("
                SELECT 
                    id, 
                    name, 
                    email, 
                    phone,
                    wilaya,
                    database_name,
                    created_at,
                    (SELECT COUNT(*) FROM feedbacks WHERE cabinet_id = cabinets.id) as total_ratings
                FROM cabinets
                WHERE status = 'active'
                ORDER BY name ASC
            ");
            
            if (!$stmt) {
                return ['success' => false, 'message' => 'Query prepare error: ' . $conn->error];
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $cabinets = $result->fetch_all(MYSQLI_ASSOC);
            
            return ['success' => true, 'cabinets' => $cabinets];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('sync_cabinet_feedback')) {
    function sync_cabinet_feedback($cabinet_id, $database_name) {
        // Connect to cabinet database
        $cabinet_conn = mysqli_connect('localhost', 'root', '', $database_name);
        
        if (!$cabinet_conn) {
            return [
                'success' => false,
                'message' => 'Cannot connect to cabinet database: ' . mysqli_connect_error(),
                'cabinet_id' => $cabinet_id,
                'synced_count' => 0
            ];
        }
        
        mysqli_set_charset($cabinet_conn, 'utf8mb4');
        
        // Connect to platform database
        $platform_conn = mysqli_connect('localhost', 'root', '', 'medicloud');
        
        if (!$platform_conn) {
            mysqli_close($cabinet_conn);
            return [
                'success' => false,
                'message' => 'Cannot connect to platform database: ' . mysqli_connect_error(),
                'cabinet_id' => $cabinet_id,
                'synced_count' => 0
            ];
        }
        
        mysqli_set_charset($platform_conn, 'utf8mb4');
        
        // Load sync helper functions
        require_once(__DIR__ . '/../../assets/backend/config/sync_helper.php');
        
        // Perform sync
        $result = sync_consultation_feedbacks($cabinet_conn, $platform_conn, $cabinet_id);
        
        // Close connections
        mysqli_close($cabinet_conn);
        mysqli_close($platform_conn);
        
        return $result;
    }
}

if (!function_exists('sync_all_cabinets')) {
    function sync_all_cabinets($conn) {
        if (!$conn) {
            return ['success' => false, 'message' => 'No database connection'];
        }
        
        try {
            // Get all active cabinets
            $cabinets_result = get_all_cabinets_for_sync($conn);
            
            if (!$cabinets_result['success']) {
                return $cabinets_result;
            }
            
            $cabinets = $cabinets_result['cabinets'];
            $total_synced = 0;
            $sync_results = [];
            $errors = [];
            
            foreach ($cabinets as $cabinet) {
                // Use default database name pattern if not specified
                $database_name = $cabinet['database_name'] ?? 'hippocare_' . $cabinet['id'];
                
                $result = sync_cabinet_feedback($cabinet['id'], $database_name);
                
                if ($result['success']) {
                    $total_synced += $result['synced_count'];
                } else {
                    $errors[] = 'Cabinet ' . $cabinet['name'] . ': ' . $result['message'];
                }
                
                $sync_results[] = [
                    'cabinet_id' => $cabinet['id'],
                    'cabinet_name' => $cabinet['name'],
                    'synced_count' => $result['synced_count'] ?? 0,
                    'success' => $result['success'],
                    'message' => $result['message']
                ];
            }
            
            return [
                'success' => count($errors) === 0,
                'message' => "Synced $total_synced feedbacks from " . count($cabinets) . " cabinets",
                'total_synced' => $total_synced,
                'cabinets_processed' => count($cabinets),
                'results' => $sync_results,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('get_cabinet_sync_stats')) {
    function get_cabinet_sync_stats($conn, $cabinet_id) {
        if (!$conn) {
            return null;
        }
        
        try {
            $stmt = $conn->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM feedbacks WHERE cabinet_id = ?) as total_synced,
                    (SELECT COUNT(*) FROM feedbacks WHERE cabinet_id = ? AND source_database IS NOT NULL) as platform_feedbacks,
                    (SELECT MAX(synced_at) FROM feedbacks WHERE cabinet_id = ?) as last_sync_time
            ");
            
            if (!$stmt) return null;
            
            $stmt->bind_param('iii', $cabinet_id, $cabinet_id, $cabinet_id);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
            
        } catch (Exception $e) {
            return null;
        }
    }
}

?>
