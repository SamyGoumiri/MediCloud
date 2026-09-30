<?php
function get_all_cabinets($conn, $search = '', $status_filter = 'all', $limit = null, $offset = 0) {
    $query = "SELECT c.*, 
              s.type_abonnement,
              s.date_debut,
              s.date_fin,
              (SELECT COUNT(*) FROM subscriptions WHERE cabinet_id = c.id AND status = 'active') as active_subscriptions,
              (SELECT SUM(amount) FROM invoices WHERE cabinet_id = c.id AND status = 'paid') as total_revenue
              FROM cabinets c 
              LEFT JOIN subscriptions s ON c.id = s.cabinet_id AND s.status = 'active'
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $query .= " AND (c.nom_cabinet LIKE ? OR c.email LIKE ? OR c.telephone LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'sss';
    }
    
    if ($status_filter !== 'all') {
        $query .= " AND c.statut = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    
    $query .= " ORDER BY c.created_at DESC";
    
    if ($limit !== null) {
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
    }
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_cabinet_by_id($conn, $cabinet_id) {
    $stmt = $conn->prepare("SELECT * FROM cabinets WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $cabinet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

function create_cabinet($conn, $data) {
    $required_fields = ['nom_cabinet', 'prenom', 'nom', 'email', 'telephone', 'wilaya', 'commune'];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Field $field is required"];
        }
    }
    
    $check_stmt = $conn->prepare("SELECT id FROM cabinets WHERE email = ? LIMIT 1");
    $check_stmt->bind_param("s", $data['email']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    $stmt = $conn->prepare("INSERT INTO cabinets (nom_cabinet, prenom, nom, email, telephone, 
                           wilaya, commune, adresse, plus_code, name, address, phone, specialty, description, statut) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $status = $data['statut'] ?? 'pending';
    
    $stmt->bind_param("sssssssssssssss",
        $data['nom_cabinet'],
        $data['prenom'],
        $data['nom'],
        $data['email'],
        $data['telephone'],
        $data['wilaya'],
        $data['commune'],
        $data['adresse'] ?? null,
        $data['plus_code'] ?? null,
        $data['name'] ?? $data['nom_cabinet'],
        $data['address'] ?? null,
        $data['phone'] ?? $data['telephone'],
        $data['specialty'] ?? null,
        $data['description'] ?? null,
        $status
    );
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Cabinet created successfully', 'cabinet_id' => $conn->insert_id];
    }
    
    return ['success' => false, 'message' => 'Failed to create cabinet'];
}

function update_cabinet($conn, $cabinet_id, $data) {
    $cabinet = get_cabinet_by_id($conn, $cabinet_id);
    if (!$cabinet) {
        return ['success' => false, 'message' => 'Cabinet not found'];
    }
    
    if (!empty($data['email']) && $data['email'] !== $cabinet['email']) {
        $check_stmt = $conn->prepare("SELECT id FROM cabinets WHERE email = ? AND id != ? LIMIT 1");
        $check_stmt->bind_param("si", $data['email'], $cabinet_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already in use'];
        }
    }
    
    $stmt = $conn->prepare("UPDATE cabinets SET nom_cabinet = ?, prenom = ?, nom = ?, 
                           email = ?, telephone = ?, wilaya = ?, commune = ?, adresse = ?, plus_code = ?, 
                           name = ?, address = ?, phone = ?, specialty = ?, description = ?, statut = ? 
                           WHERE id = ?");
    
    $stmt->bind_param("sssssssssssssssi",
        $data['nom_cabinet'],
        $data['prenom'],
        $data['nom'],
        $data['email'],
        $data['telephone'],
        $data['wilaya'],
        $data['commune'],
        $data['adresse'] ?? null,
        $data['plus_code'] ?? null,
        $data['name'] ?? $data['nom_cabinet'],
        $data['address'] ?? null,
        $data['phone'] ?? $data['telephone'],
        $data['specialty'] ?? null,
        $data['description'] ?? null,
        $data['statut'] ?? 'active',
        $cabinet_id
    );
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Cabinet updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to update cabinet'];
}

function delete_cabinet($conn, $cabinet_id) {
    $cabinet = get_cabinet_by_id($conn, $cabinet_id);
    if (!$cabinet) {
        return ['success' => false, 'message' => 'Cabinet not found'];
    }
    
    $conn->begin_transaction();
    
    try {
        $conn->query("DELETE FROM invoices WHERE cabinet_id = $cabinet_id");
        $conn->query("DELETE FROM subscriptions WHERE cabinet_id = $cabinet_id");
        
        $stmt = $conn->prepare("DELETE FROM cabinets WHERE id = ?");
        $stmt->bind_param("i", $cabinet_id);
        $stmt->execute();
        
        $conn->commit();
        return ['success' => true, 'message' => 'Cabinet deleted successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Failed to delete cabinet: ' . $e->getMessage()];
    }
}

function get_cabinet_stats($conn, $cabinet_id) {
    $stats = [];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE cabinet_id = ? AND status = 'active'");
    $stmt->bind_param("i", $cabinet_id);
    $stmt->execute();
    $stats['active_subscriptions'] = $stmt->get_result()->fetch_assoc()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM invoices WHERE cabinet_id = ?");
    $stmt->bind_param("i", $cabinet_id);
    $stmt->execute();
    $stats['total_invoices'] = $stmt->get_result()->fetch_assoc()['count'];
    
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM invoices WHERE cabinet_id = ? AND status = 'paid'");
    $stmt->bind_param("i", $cabinet_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['total_revenue'] = $result['total'] ?? 0;
    
    $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE cabinet_id = ? ORDER BY date_debut DESC LIMIT 1");
    $stmt->bind_param("i", $cabinet_id);
    $stmt->execute();
    $stats['current_subscription'] = $stmt->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT * FROM invoices WHERE cabinet_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $cabinet_id);
    $stmt->execute();
    $stats['recent_invoices'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $stats;
}
?>
