<?php
session_start();
require 'config.php';

// บังคับว่าต้องเป็น Admin เท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

// ---------------------------------------------------------
// ประมวลผลการค้นหา (Search)
// ---------------------------------------------------------
$search_query = trim($_GET['q'] ?? '');

$sql = "SELECT l.*, u.username, u.full_name 
        FROM audit_logs l 
        LEFT JOIN users u ON l.user_id = u.id ";
$params = [];

// ถ้ามีการพิมพ์ค้นหา ให้เพิ่มเงื่อนไข WHERE
if ($search_query !== '') {
    $sql .= " WHERE u.username LIKE ? 
                 OR u.full_name LIKE ?
                 OR l.action_type LIKE ? 
                 OR l.table_name LIKE ? 
                 OR l.detail LIKE ? 
                 OR l.ip_address LIKE ? ";
    
    $like_term = "%{$search_query}%";
    // ใส่ Parameter 6 ตัวตามจำนวนเครื่องหมาย ? ด้านบน
    $params = [$like_term, $like_term, $like_term, $like_term, $like_term, $like_term];
}

// เรียงลำดับจากล่าสุดลงมา และจำกัดแค่ 500 รายการเพื่อไม่ให้ Server โหลดหนักเกินไป
$sql .= " ORDER BY l.id DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>System Audit Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .json-detail { 
            font-size: 0.8rem; 
            background: #f8f9fa; 
            padding: 8px; 
            border-radius: 4px; 
            word-break: break-all;
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    
    <?php include 'navbar.php'; ?>

    <div class="container-fluid px-4 flex-grow-1">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold">🕵️‍♂️ ตรวจสอบประวัติการใช้งานระบบ (Audit Logs)</h4>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body bg-white">
                <form method="GET" action="admin_logs.php">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white">🔍 ค้นหา</span>
                                <input type="text" name="q" class="form-control" placeholder="พิมพ์ชื่อผู้ใช้, IP, ชื่อตาราง, หรือคำที่อยู่ในรายละเอียด (เช่น id หรือ ชื่อคนถูกลบ)..." value="<?= htmlspecialchars($search_query) ?>">
                                <button class="btn btn-primary" type="submit">ค้นหาเลย</button>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($search_query !== ''): ?>
                                <a href="admin_logs.php" class="btn btn-outline-secondary">❌ ล้างการค้นหา</a>
                                <span class="ms-2 text-muted"><small>พบ <?= count($logs) ?> รายการ</small></span>
                            <?php else: ?>
                                <span class="text-muted"><small>แสดง 500 รายการล่าสุด</small></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-hover table-sm align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="12%">วัน-เวลา</th>
                            <th width="15%">ผู้กระทำ (IP)</th>
                            <th width="10%">Action</th>
                            <th width="12%">ตารางที่แก้</th>
                            <th width="8%">Record ID</th>
                            <th width="43%">รายละเอียด (JSON Diff)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($logs) > 0): ?>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td><small><?= $log['created_at'] ?></small></td>
                                <td>
                                    <strong><?= htmlspecialchars($log['full_name'] ?? $log['username'] ?? 'System/Unknown') ?></strong><br>
                                    <span class="badge bg-light text-dark border border-secondary"><?= $log['ip_address'] ?></span>
                                </td>
                                <td>
                                    <span class="badge w-100 py-2
                                        <?php 
                                            if($log['action_type']=='INSERT') echo 'bg-success';
                                            elseif($log['action_type']=='UPDATE') echo 'bg-warning text-dark';
                                            elseif(strpos($log['action_type'], 'DELETE') !== false) echo 'bg-danger';
                                            elseif($log['action_type']=='LOGIN') echo 'bg-info text-dark';
                                            else echo 'bg-secondary';
                                        ?>">
                                        <?= $log['action_type'] ?>
                                    </span>
                                </td>
                                <td><code><?= $log['table_name'] ?></code></td>
                                <td class="text-center fw-bold"><?= $log['record_id'] ?: '-' ?></td>
                                <td>
                                    <?php if($log['detail']): ?>
                                        <div class="json-detail text-wrap"><?= htmlspecialchars($log['detail']) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted"><small>ไม่มีรายละเอียดเพิ่มเติม</small></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-danger fw-bold">ไม่พบข้อมูล Log ที่ค้นหา</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>