<?php
function get_dashboard_stats($conn) {
    $stats = [];

    // Total cabinets
    $result = $conn->query("SELECT COUNT(*) as count FROM cabinets");
    $stats['total_cabinets'] = (int)($result->fetch_assoc()['count'] ?? 0);

    // Cabinets by statut (NOT status)
    $result = $conn->query("SELECT COUNT(*) as count FROM cabinets WHERE statut = 'active'");
    $stats['active_cabinets'] = (int)($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("SELECT COUNT(*) as count FROM cabinets WHERE statut = 'pending'");
    $stats['pending_cabinets'] = (int)($result->fetch_assoc()['count'] ?? 0);

    // Subscriptions + invoices use status (OK)
    $result = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
    $stats['active_subscriptions'] = (int)($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'");
    $stats['pending_invoices'] = (int)($result->fetch_assoc()['count'] ?? 0);

    // Monthly revenue: invoices column is date_paiement (not payment_date)
    $result = $conn->query("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM invoices
        WHERE status = 'paid'
          AND date_paiement IS NOT NULL
          AND YEAR(date_paiement) = YEAR(CURDATE())
          AND MONTH(date_paiement) = MONTH(CURDATE())
    ");
    $stats['monthly_revenue'] = (float)($result->fetch_assoc()['total'] ?? 0);

    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'");
    $stats['total_revenue'] = (float)($result->fetch_assoc()['total'] ?? 0);

    // Recent cabinets: use statut + include wilaya used by dashboard.php
    $recent_cabinets = $conn->query("
        SELECT id, nom_cabinet, wilaya, email, created_at, statut
        FROM cabinets
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stats['recent_cabinets'] = $recent_cabinets->fetch_all(MYSQLI_ASSOC);

    // Recent invoices
    $recent_invoices = $conn->query("
        SELECT
            i.id,
            i.numero_facture,
            i.amount,
            i.status,
            i.created_at,
            c.nom_cabinet
        FROM invoices i
        LEFT JOIN cabinets c ON i.cabinet_id = c.id
        ORDER BY i.created_at DESC
        LIMIT 5
    ");
    $stats['recent_invoices'] = $recent_invoices->fetch_all(MYSQLI_ASSOC);

    return $stats;
}

function get_monthly_revenue_chart($conn, $months = 12) {
    $data = [];

    for ($i = $months - 1; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM invoices
            WHERE status = 'paid'
              AND date_paiement IS NOT NULL
              AND DATE_FORMAT(date_paiement, '%Y-%m') = ?
        ");
        $stmt->bind_param("s", $month);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $data[] = [
            'month' => date('M Y', strtotime($month . '-01')),
            'revenue' => (float)($result['total'] ?? 0)
        ];
    }

    return $data;
}

function get_report_data($conn, $start_date = null, $end_date = null) {
    $data = [];

    $date_filter = '';
    if ($start_date && $end_date) {
        $date_filter = " AND created_at BETWEEN '$start_date' AND '$end_date'";
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM cabinets WHERE 1=1 $date_filter");
    $data['total_cabinets'] = (int)($result->fetch_assoc()['count'] ?? 0);

    // cabinets.statut (NOT status)
    $result = $conn->query("SELECT COUNT(*) as count FROM cabinets WHERE statut = 'active' $date_filter");
    $data['active_cabinets'] = (int)($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE 1=1 $date_filter");
    $data['total_subscriptions'] = (int)($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE 1=1 $date_filter");
    $data['total_invoices'] = (int)($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid' $date_filter");
    $data['total_revenue'] = (float)($result->fetch_assoc()['total'] ?? 0);

    $result = $conn->query("SELECT wilaya, COUNT(*) as count FROM cabinets GROUP BY wilaya ORDER BY count DESC LIMIT 10");
    $data['cabinets_by_wilaya'] = $result->fetch_all(MYSQLI_ASSOC);

    return $data;
}

function get_revenue_stats($conn, $start_date = null, $end_date = null) {
    $revenue = [];
    
    $date_filter = '';
    if ($start_date && $end_date) {
        $date_filter = " AND i.created_at BETWEEN '$start_date' AND '$end_date'";
    }
    
    $result = $conn->query("SELECT COALESCE(SUM(i.amount), 0) as total FROM invoices i WHERE 1=1 $date_filter");
    $revenue['total_revenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
    
    $result = $conn->query("SELECT COALESCE(SUM(i.amount), 0) as total FROM invoices i WHERE i.status = 'paid' $date_filter");
    $revenue['paid_revenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
    
    $result = $conn->query("SELECT COUNT(*) as count FROM invoices i WHERE 1=1 $date_filter");
    $revenue['invoice_count'] = (int)($result->fetch_assoc()['count'] ?? 0);
    
    return $revenue;
}

function get_renewal_data($conn) {
    $data = [];
    
    // Subscriptions à renouveler ce mois - utiliser date_fin de subscriptions
    $result = $conn->query("
        SELECT c.nom_cabinet, s.date_fin as renewal_date
        FROM subscriptions s
        LEFT JOIN cabinets c ON s.cabinet_id = c.id
        WHERE MONTH(s.date_fin) = MONTH(CURDATE())
        AND YEAR(s.date_fin) = YEAR(CURDATE())
        ORDER BY s.date_fin ASC
    ");
    $data['renewals'] = $result->fetch_all(MYSQLI_ASSOC) ?? [];
    
    // Renewal rate
    $total_subs = $conn->query("SELECT COUNT(*) as count FROM subscriptions")->fetch_assoc()['count'];
    $active_subs = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'")->fetch_assoc()['count'];
    $data['renewal_rate'] = $total_subs > 0 ? round(($active_subs / $total_subs) * 100) : 0;
    
    // Churn rate
    $cancelled = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'cancelled'")->fetch_assoc()['count'];
    $data['churn_rate'] = $total_subs > 0 ? round(($cancelled / $total_subs) * 100) : 0;
    
    return $data;
}

function get_top_clients($conn, $limit = 5) {
    $result = $conn->query("
        SELECT 
            c.id,
            c.nom_cabinet,
            COALESCE(SUM(i.amount), 0) as total_spent
        FROM cabinets c
        LEFT JOIN invoices i ON c.id = i.cabinet_id AND i.status = 'paid'
        GROUP BY c.id, c.nom_cabinet
        ORDER BY total_spent DESC
        LIMIT $limit
    ");
    return $result->fetch_all(MYSQLI_ASSOC) ?? [];
}
?>
