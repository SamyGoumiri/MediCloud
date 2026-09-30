<?php
function get_all_invoices($conn, $search = '', $status_filter = 'all', $limit = null, $offset = 0) {
    $query = "SELECT i.*, c.nom_cabinet, c.email as cabinet_email 
              FROM invoices i 
              LEFT JOIN cabinets c ON i.cabinet_id = c.id 
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $query .= " AND (i.numero_facture LIKE ? OR c.nom_cabinet LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }
    
    if ($status_filter !== 'all') {
        $query .= " AND i.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    
    $query .= " ORDER BY i.created_at DESC";
    
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

function get_invoice_by_id($conn, $invoice_id) {
    // Check if $invoice_id is numeric (ID) or string (numero_facture)
    if (is_numeric($invoice_id)) {
        $stmt = $conn->prepare("SELECT i.*, c.nom_cabinet, c.prenom, c.nom, 
                               c.email as cabinet_email, c.telephone, c.adresse, c.wilaya, c.commune,
                               s.type_abonnement, s.date_debut, s.date_fin 
                               FROM invoices i 
                               LEFT JOIN cabinets c ON i.cabinet_id = c.id 
                               LEFT JOIN subscriptions s ON i.subscription_id = s.id 
                               WHERE i.id = ? LIMIT 1");
        $stmt->bind_param("i", $invoice_id);
    } else {
        $stmt = $conn->prepare("SELECT i.*, c.nom_cabinet, c.prenom, c.nom, 
                               c.email as cabinet_email, c.telephone, c.adresse, c.wilaya, c.commune,
                               s.type_abonnement, s.date_debut, s.date_fin 
                               FROM invoices i 
                               LEFT JOIN cabinets c ON i.cabinet_id = c.id 
                               LEFT JOIN subscriptions s ON i.subscription_id = s.id 
                               WHERE i.numero_facture = ? LIMIT 1");
        $stmt->bind_param("s", $invoice_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

function create_invoice($conn, $data) {
    // Validate required fields against existing schema
    $required_fields = ['cabinet_id', 'amount', 'date_creation'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            return ['success' => false, 'message' => "Field $field is required"];
        }
    }

    $cabinet_id = (int)$data['cabinet_id'];
    $subscription_id = isset($data['subscription_id']) && $data['subscription_id'] !== '' ? (int)$data['subscription_id'] : null;
    $amount = (float)$data['amount'];
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Le montant doit être supérieur à 0'];
    }

    $status = $data['status'] ?? 'pending';
    $allowed_status = ['pending', 'paid', 'cancelled'];
    if (!in_array($status, $allowed_status, true)) {
        $status = 'pending';
    }

    $methode_paiement = $data['methode_paiement'] ?? null;
    $reference = $data['reference'] ?? null;
    $date_creation = !empty($data['date_creation']) ? $data['date_creation'] : date('Y-m-d');

    $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("INSERT INTO invoices (cabinet_id, subscription_id, numero_facture, amount, montant,
                           date_creation, status, methode_paiement, reference) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Types: i (cabinet), i (subscription), s (numero), d (amount), d (montant), s (date), s (status), s (method), s (reference)
    $stmt->bind_param("iisddssss",
        $cabinet_id,
        $subscription_id,
        $invoice_number,
        $amount,
        $amount,
        $date_creation,
        $status,
        $methode_paiement,
        $reference
    );
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Invoice created successfully', 'invoice_id' => $conn->insert_id, 'invoice_number' => $invoice_number];
    }
    
    return ['success' => false, 'message' => 'Failed to create invoice'];
}

function update_invoice_status($conn, $invoice_id, $status, $payment_method = null, $payment_date = null) {
    $invoice = get_invoice_by_id($conn, $invoice_id);
    if (!$invoice) {
        return ['success' => false, 'message' => 'Invoice not found'];
    }
    
    // Use the actual ID from the fetched invoice to ensure we update the right record
    $actual_id = $invoice['id'];
    
    $stmt = $conn->prepare("UPDATE invoices SET status = ?, methode_paiement = ?, date_paiement = ? WHERE id = ?");
    $stmt->bind_param("sssi", $status, $payment_method, $payment_date, $actual_id);
    
    if ($stmt->execute()) {
        // Verify the update worked
        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Invoice status updated successfully'];
        } else {
            return ['success' => false, 'message' => 'No rows affected - invoice may already have this status'];
        }
    }
    
    return ['success' => false, 'message' => 'Failed to update invoice status: ' . $stmt->error];
}

function get_invoice_stats($conn) {
    $stats = [];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'");
    $stats['pending_count'] = (int)($result->fetch_assoc()['count'] ?? 0);
    
    $result = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'paid'");
    $stats['paid_count'] = (int)($result->fetch_assoc()['count'] ?? 0);
    
    $result = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'cancelled'");
    $stats['overdue_count'] = (int)($result->fetch_assoc()['count'] ?? 0);
    
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'");
    $stats['paid_revenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
    
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'pending'");
    $stats['pending_revenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
    
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'cancelled'");
    $stats['overdue_revenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
    
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid' AND MONTH(date_paiement) = MONTH(CURDATE())");
    $stats['monthly_revenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
    
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'");
    $stats['total_revenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
    
    return $stats;
}
?>
