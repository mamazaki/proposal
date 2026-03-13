<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ------------------------------------------------------------------
// 1. ดึงหัวข้อเรื่องหลักทั้งหมดมาทำ Dropdown
// ------------------------------------------------------------------
$stmt_mt = $pdo->query("SELECT id, title FROM main_topics WHERE is_active = 1 ORDER BY id ASC");
$main_topics = $stmt_mt->fetchAll();

// กำหนดหัวข้อหลักที่ถูกเลือก
$selected_main_topic_id = $_GET['main_topic_id'] ?? ($main_topics[0]['id'] ?? 0);

// ------------------------------------------------------------------
// 2. ระบบประมวลผลการลบข้อมูล (Soft Delete)
// ------------------------------------------------------------------
if (isset($_GET['del_id'])) {
    $del_id = $_GET['del_id'];
    $stmt_check = $pdo->prepare("SELECT user_id, first_name, last_name FROM committee_members WHERE id = ?");
    $stmt_check->execute([$del_id]);
    $record = $stmt_check->fetch();
    
    if ($record && ($_SESSION['role'] === 'admin' || $_SESSION['user_id'] == $record['user_id'])) {
        try {
            $pdo->beginTransaction();
            $stmt_del = $pdo->prepare("UPDATE committee_members SET is_active = 0 WHERE id = ?");
            $stmt_del->execute([$del_id]);
            systemLog($pdo, 'DELETE (Soft)', 'committee_members', $del_id, ['deleted_name' => $record['first_name'].' '.$record['last_name']]);
            $pdo->commit();
            echo "<script>alert('ลบรายชื่อเรียบร้อยแล้ว'); window.location.href='dashboard.php?main_topic_id={$selected_main_topic_id}';</script>";
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Error: " . $e->getMessage());
        }
    }
}

// ------------------------------------------------------------------
// 3. อัปเดต Query สรุปจำนวน และ รายชื่อทั้งหมด (กรองตาม Main Topic)
// ------------------------------------------------------------------

// สรุปจำนวนคณะทำงานแต่ละประเด็นย่อย
$sql_summary = "SELECT sg.title, COUNT(cm.id) as total 
                FROM sub_groups sg 
                LEFT JOIN committee_members cm ON sg.id = cm.sub_group_id AND cm.is_active = 1
                WHERE sg.main_topic_id = ?
                GROUP BY sg.id ORDER BY sg.sort_order";
$stmt_summary = $pdo->prepare($sql_summary);
$stmt_summary->execute([$selected_main_topic_id]);
$summary_data = $stmt_summary->fetchAll();

// ดึงข้อมูลรายชื่อทั้งหมด
$sql_all = "SELECT cm.*, sg.title as subgroup_title, mt.title as main_topic_title, u.full_name as author_name 
            FROM committee_members cm
            JOIN sub_groups sg ON cm.sub_group_id = sg.id
            JOIN main_topics mt ON sg.main_topic_id = mt.id
            LEFT JOIN users u ON cm.user_id = u.id
            WHERE cm.is_active = 1 AND mt.id = ?
            ORDER BY mt.id ASC, sg.sort_order ASC, cm.sort_order ASC, cm.id ASC";
$stmt_all = $pdo->prepare($sql_all);
$stmt_all->execute([$selected_main_topic_id]);
$all_members = $stmt_all->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    
    <?php include 'navbar.php'; ?>

    <div class="container-fluid px-4 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Dashboard สรุปการเสนอชื่อ</h3>
            <div>
                <button type="button" class="btn btn-success me-2" onclick="exportToExcel()">
                    📥 ดาวน์โหลด Excel (.xlsx)
                </button>
                <a href="index.php" class="btn btn-primary">+ เสนอชื่อเพิ่ม</a>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0 bg-white">
            <div class="card-body py-3">
                <form method="GET" action="dashboard.php" id="filterForm">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <label class="form-label fw-bold mb-0 text-primary">📂 เลือกหัวข้อเรื่องหลัก:</label>
                        </div>
                        <div class="col-md-10">
                            <select name="main_topic_id" class="form-select fw-bold" onchange="document.getElementById('filterForm').submit()">
                                <?php foreach($main_topics as $mt): ?>
                                    <option value="<?= $mt['id'] ?>" <?= ($mt['id'] == $selected_main_topic_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mt['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="row">
            <?php foreach($summary_data as $row): ?>
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <p class="card-title text-muted" style="font-size: 0.9rem;"><?= htmlspecialchars($row['title']) ?></p>
                        <h3 class="text-success"><?= $row['total'] ?> <small class="text-muted fs-6">คน</small></h3>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <hr>
        <h4>รายชื่อทั้งหมด</h4>
        <div class="table-responsive bg-white shadow-sm mb-4">
            <table class="table table-bordered table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>คณะทำงาน/ประเด็น</th>
                        <th>ตำแหน่งในคณะ</th>
                        <th>ชื่อ - นามสกุล</th>
                        <th>ตำแหน่ง/สังกัด</th>
                        <th>เบอร์โทร</th>
                        <th>ผู้เพิ่มข้อมูล</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_members as $row): ?>
                    <tr>
                        <td><small><?= htmlspecialchars($row['subgroup_title']) ?></small></td>
                        <td>
                            <?= htmlspecialchars($row['wg_position']) ?>
                            <br><small class="text-muted">(ลำดับ: <?= $row['sort_order'] ?>)</small>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['prefix']) ?>&nbsp;<?= htmlspecialchars($row['first_name']) ?>&nbsp;&nbsp;<?= htmlspecialchars($row['last_name']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['job_position']) ?>
                            <br><small class="text-muted"><?= htmlspecialchars($row['agency']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['phone_number']) ?></td>
                        <td>
                            <span class="badge bg-secondary">
                                <?= str_replace(' ', '&nbsp;', htmlspecialchars($row['author_name'] ?? $row['username'] ?? 'System')) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if($_SESSION['role'] === 'admin' || $_SESSION['user_id'] == $row['user_id']): ?>
                                <div class="btn-group" role="group">
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                                    <a href="?del_id=<?= $row['id'] ?>&main_topic_id=<?= $selected_main_topic_id ?>" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายชื่อ: <?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?> ?');">ลบ</a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    <script>
    function exportToExcel() {
        let data = [
            ["ลำดับ", "คณะทำงาน/ประเด็น", "คำนำหน้า", "ชื่อ", "นามสกุล", "ตำแหน่งหน้าที่", "สังกัด", "ตำแหน่งในคณะ", "เบอร์โทร", "ผู้เพิ่มข้อมูล"]
        ];

        <?php
        $i = 1;
        foreach($all_members as $row) {
            $subgroup = json_encode($row['subgroup_title'], JSON_UNESCAPED_UNICODE);
            $wg_pos   = json_encode($row['wg_position'], JSON_UNESCAPED_UNICODE);
            $prefix   = json_encode($row['prefix'], JSON_UNESCAPED_UNICODE);
            $fname    = json_encode($row['first_name'], JSON_UNESCAPED_UNICODE);
            $lname    = json_encode($row['last_name'], JSON_UNESCAPED_UNICODE);
            $job      = json_encode($row['job_position'], JSON_UNESCAPED_UNICODE);
            $agency   = json_encode($row['agency'], JSON_UNESCAPED_UNICODE);
            $phone    = json_encode($row['phone_number'], JSON_UNESCAPED_UNICODE);
            $author   = json_encode($row['author_name'] ?? $row['username'] ?? 'System', JSON_UNESCAPED_UNICODE);

            echo "data.push([$i, $subgroup, $prefix, $fname, $lname, $job, $agency, $wg_pos, $phone, $author]);\n";
            $i++;
        }
        ?>

        let ws = XLSX.utils.aoa_to_sheet(data);
        ws['!cols'] = [
            {wch: 6}, {wch: 45}, {wch: 10}, {wch: 20}, {wch: 20}, 
            {wch: 30}, {wch: 35}, {wch: 25}, {wch: 15}, {wch: 25}
        ];

        let wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "รายชื่อคณะทำงาน");

        let dateStr = new Date().toISOString().slice(0, 10);
        XLSX.writeFile(wb, "รายชื่อคณะทำงาน_อุดรธานี_" + dateStr + ".xlsx");
    }
    </script>
</body>
</html>