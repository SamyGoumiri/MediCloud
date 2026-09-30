<?php
/**
 * Sync Feedbacks Management Page
 * Location: MediCloud/admin/sync-feedbacks.php
 * Allows manual synchronization of cabinet feedbacks to platform
 */

include('DB/secure_page.php');
include('DB/cabinet_sync_helper.php');

$sync_all_results = null;
$error_message = '';
$success_message = '';

// Handle sync actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'sync_all':
                $sync_all_results = sync_all_cabinets($conn);
                if ($sync_all_results['success']) {
                    $success_message = $sync_all_results['message'];
                } else {
                    $error_message = $sync_all_results['message'];
                }
                break;
                
            case 'sync_cabinet':
                if (isset($_POST['cabinet_id']) && !empty($_POST['cabinet_id'])) {
                    $cabinet_id = intval($_POST['cabinet_id']);
                    
                    // Get cabinet database name
                    $stmt = $conn->prepare("SELECT database_name FROM cabinets WHERE id = ?");
                    $stmt->bind_param('i', $cabinet_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $cabinet = $result->fetch_assoc();
                    
                    if ($cabinet) {
                        $database_name = $cabinet['database_name'] ?? 'hippocare_' . $cabinet_id;
                        $sync_all_results = ['results' => []];
                        
                        $single_result = sync_cabinet_feedback($cabinet_id, $database_name);
                        $sync_all_results['results'][] = $single_result;
                        
                        if ($single_result['success']) {
                            $success_message = "Cabinet synced: " . $single_result['message'];
                        } else {
                            $error_message = "Sync failed: " . $single_result['message'];
                        }
                    } else {
                        $error_message = "Cabinet not found";
                    }
                }
                break;
        }
    }
}

// Get all cabinets
$cabinets_result = get_all_cabinets_for_sync($conn);
$cabinets = $cabinets_result['success'] ? $cabinets_result['cabinets'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Feedbacks - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .sync-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .sync-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        
        .sync-header h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
        }
        
        .sync-info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #1565c0;
        }
        
        .sync-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        
        .action-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .action-card h3 {
            margin-top: 0;
            color: #333;
            font-size: 18px;
        }
        
        .action-card p {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-sync-all {
            background: #4CAF50;
            color: white;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-sync-all:hover {
            background: #45a049;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .btn-sync-cabinet {
            background: #2196F3;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
        }
        
        .btn-sync-cabinet:hover {
            background: #0b7dda;
        }
        
        .cabinet-list {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .cabinet-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            background: #fafafa;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .cabinet-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .cabinet-info {
            flex: 1;
        }
        
        .cabinet-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        
        .cabinet-meta {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .cabinet-ratings {
            background: #fff3e0;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            color: #e65100;
            font-weight: 600;
            margin: 0 20px;
        }
        
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .sync-results {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .sync-result-item {
            padding: 12px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .result-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .result-error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .result-count {
            font-weight: 600;
            color: #333;
        }
        
        .no-cabinets {
            padding: 40px;
            text-align: center;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="sync-container">
        <div class="sync-header">
            <h1>Feedback Synchronization</h1>
            <a href="dashboard.php" class="btn" style="background: #6c757d; color: white; text-decoration: none;">Back to Dashboard</a>
        </div>
        
        <div class="sync-info">
            <strong>ℹ️ About Sync:</strong> Synchronization copies patient feedback from individual cabinet databases 
            to the platform database for aggregation and analysis. This enables cross-cabinet ratings and analytics.
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="sync-actions">
            <div class="action-card">
                <h3>🔄 Sync All Cabinets</h3>
                <p>Synchronize feedback from all active cabinets to the platform database at once.</p>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="sync_all">
                    <button type="submit" class="btn btn-sync-all" onclick="return confirm('Sync all cabinets? This may take a moment.');">
                        Sync All Cabinets
                    </button>
                </form>
            </div>
            
            <div class="action-card">
                <h3>🏥 Sync Single Cabinet</h3>
                <p>Synchronize feedback from a specific cabinet to the platform database.</p>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="sync_cabinet">
                    <select name="cabinet_id" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select a cabinet...</option>
                        <?php foreach ($cabinets as $cabinet): ?>
                            <option value="<?php echo $cabinet['id']; ?>">
                                <?php echo htmlspecialchars($cabinet['name']); ?> 
                                (<?php echo $cabinet['total_ratings']; ?> ratings)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sync-all" style="background: #2196F3;" onclick="return confirm('Sync this cabinet? This may take a moment.');">
                        Sync Cabinet
                    </button>
                </form>
            </div>
        </div>
        
        <div class="cabinet-list">
            <h3>📋 Cabinet Status</h3>
            <?php if (empty($cabinets)): ?>
                <div class="no-cabinets">No active cabinets found</div>
            <?php else: ?>
                <?php foreach ($cabinets as $cabinet): ?>
                    <div class="cabinet-item">
                        <div class="cabinet-info">
                            <div class="cabinet-name"><?php echo htmlspecialchars($cabinet['name']); ?></div>
                            <div class="cabinet-meta">
                                Email: <?php echo htmlspecialchars($cabinet['email']); ?> | 
                                Location: <?php echo htmlspecialchars($cabinet['wilaya']); ?> | 
                                Since: <?php echo date('M d, Y', strtotime($cabinet['created_at'])); ?>
                            </div>
                        </div>
                        <div class="cabinet-ratings">
                            <strong><?php echo $cabinet['total_ratings']; ?> Ratings</strong> on Platform
                        </div>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="sync_cabinet">
                            <input type="hidden" name="cabinet_id" value="<?php echo $cabinet['id']; ?>">
                            <button type="submit" class="btn btn-sync-cabinet" onclick="return confirm('Sync this cabinet?');">
                                Sync Now
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($sync_all_results): ?>
            <div class="sync-results">
                <h3>✅ Synchronization Results</h3>
                
                <?php foreach ($sync_all_results['results'] as $result): ?>
                    <div class="sync-result-item <?php echo $result['success'] ? 'result-success' : 'result-error'; ?>">
                        <strong><?php echo htmlspecialchars($result['cabinet_name'] ?? 'Cabinet #' . $result['cabinet_id']); ?></strong><br>
                        <span class="result-count">
                            <?php 
                            if ($result['success']) {
                                echo "✓ Synced " . $result['synced_count'] . " feedback(s)";
                            } else {
                                echo "✗ " . htmlspecialchars($result['message']);
                            }
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                
                <?php if (!empty($sync_all_results['errors'])): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-top: 15px; color: #856404;">
                        <strong>⚠️ Errors Encountered:</strong><br>
                        <?php foreach ($sync_all_results['errors'] as $error): ?>
                            • <?php echo htmlspecialchars($error); ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
