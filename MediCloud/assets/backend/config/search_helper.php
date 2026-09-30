<?php
/**
 * Search and cabinet helper functions
 * Provides modular functions for cabinet searches and feedback queries
 */

if (!function_exists('get_all_cabinets')) {
    function get_all_cabinets($conn, $search = '', $wilaya = '') {
        $query = 'SELECT * FROM cabinets WHERE 1=1';
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $query .= ' AND (nom_cabinet LIKE ? OR email LIKE ?)';
            $search_term = '%' . $search . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= 'ss';
        }
        
        if (!empty($wilaya)) {
            $query .= ' AND wilaya = ?';
            $params[] = $wilaya;
            $types .= 's';
        }
        
        $query .= ' ORDER BY nom_cabinet ASC';
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log('Prepare failed: ' . $conn->error);
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log('Execute failed: ' . $stmt->error);
            return [];
        }
        
        $result = $stmt->get_result();
        $cabinets = [];
        
        while ($row = $result->fetch_assoc()) {
            $cabinets[] = format_cabinet($row);
        }
        
        $stmt->close();
        return $cabinets;
    }
}

if (!function_exists('get_cabinet_by_id')) {
    function get_cabinet_by_id($conn, $id) {
        $stmt = $conn->prepare('SELECT * FROM cabinets WHERE id = ?');
        if (!$stmt) return null;
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cabinet = $result->fetch_assoc();
        $stmt->close();
        
        return $cabinet ? format_cabinet($cabinet) : null;
    }
}

if (!function_exists('get_doctors_by_cabinet')) {
    function get_doctors_by_cabinet($conn, $cabinet_id) {
        $stmt = $conn->prepare('SELECT * FROM doctors WHERE cabinet_id = ? ORDER BY nom ASC');
        if (!$stmt) return [];
        
        $stmt->bind_param('i', $cabinet_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $doctors = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['name'] = trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? ''));
            $doctors[] = $row;
        }
        
        $stmt->close();
        return $doctors;
    }
}

if (!function_exists('get_cabinet_feedback')) {
    function get_cabinet_feedback($conn, $cabinet_id, $specialty = '') {
        $query = '
            SELECT 
                cabinet_id,
                AVG(rating_security) as security,
                AVG(rating_equipment) as equipment,
                AVG(rating_hygiene) as hygiene,
                AVG(rating_availability) as availability,
                AVG(rating_skills) as skills,
                AVG(rating_experience) as experience,
                AVG(rating_location) as location,
                AVG(rating_price) as price,
                AVG(rating_reception) as reception,
                AVG(rating_punctuality) as punctuality,
                COUNT(*) as feedback_count
            FROM feedbacks
            WHERE cabinet_id = ?'
        ;
        
        if (!empty($specialty)) {
            $query .= ' AND doctor_id IN (SELECT id FROM doctors WHERE specialty = ?)';
        }
        
        $query .= ' GROUP BY cabinet_id';
        
        $stmt = $conn->prepare($query);
        if (!$stmt) return null;
        
        if (!empty($specialty)) {
            $stmt->bind_param('is', $cabinet_id, $specialty);
        } else {
            $stmt->bind_param('i', $cabinet_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $feedback = $result->fetch_assoc();
        $stmt->close();
        
        return $feedback;
    }
}

if (!function_exists('format_cabinet')) {
    function format_cabinet($row) {
        return [
            'id' => (int)$row['id'],
            'name' => $row['nom_cabinet'] ?? '',
            'city' => $row['wilaya'] ?? '',
            'commune' => $row['commune'] ?? '',
            'address' => $row['adresse'] ?? '',
            'phone' => $row['telephone'] ?? '',
            'email' => $row['email'] ?? '',
            'price' => (float)($row['tarif'] ?? 0),
            'status' => $row['statut'] ?? 'active',
            'created_at' => $row['created_at'] ?? null,
            'doctors' => [],
            'ratings' => [
                'security' => 0,
                'equipment' => 0,
                'hygiene' => 0,
                'availability' => 0,
                'skills' => 0,
                'experience' => 0,
                'location' => 0,
                'price' => 0,
                'reception' => 0,
                'punctuality' => 0,
            ],
            'feedback_count' => 0,
            'score' => 0,
            'method' => 'wsm'
        ];
    }
}

?>
