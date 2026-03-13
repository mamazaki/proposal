<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

// ตรวจสอบสิทธิ์
$sql = "SELECT * FROM committee_members WHERE id = ?";
$params = [$id];
if ($_SESSION['role'] !== 'admin') {
    $sql .= " AND user_id = ?";
    $params[] = $_SESSION['user_id'];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$member = $stmt->fetch();

if (!$member) {
    die("ไม่พบข้อมูล หรือคุณไม่มีสิทธิ์แก้ไข");
}

// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     $sql_update = "UPDATE committee_members SET 
//                     wg_position = ?, prefix = ?, first_name = ?, 
//                     last_name = ?, job_position = ?, agency = ?, phone_number = ? 
//                    WHERE id = ?";
//     $stmt_update = $pdo->prepare($sql_update);
//     $stmt_update->execute([
//         $_POST['wg_position'], 
//         $_POST['prefix'],
//         $_POST['first_name'],
//         $_POST['last_name'],
//         $_POST['job_position'],
//         $_POST['agency'],
//         $_POST['phone_number'],
//         $id
//     ]);
//     echo "<script>alert('อัปเดตเรียบร้อย'); window.location.href='dashboard.php';</script>";
// }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. กำหนดชุดข้อมูลเก่า (อ้างอิงจาก $member ที่ Query ออกมาก่อนหน้านี้)
    $old_data = [
        'wg_position'  => $member['wg_position'],
        'sort_order'   => $member['sort_order'],
        'prefix'       => $member['prefix'],
        'first_name'   => $member['first_name'],
        'last_name'    => $member['last_name'],
        'job_position' => $member['job_position'],
        'agency'       => $member['agency'],
        'phone_number' => $member['phone_number']
    ];

    // 2. กำหนดชุดข้อมูลใหม่ (รับมาจากฟอร์ม)
    $new_data = [
        'wg_position'  => trim($_POST['wg_position']),
        'sort_order'   => trim($_POST['sort_order']),
        'prefix'       => trim($_POST['prefix']),
        'first_name'   => trim($_POST['first_name']),
        'last_name'    => trim($_POST['last_name']),
        'job_position' => trim($_POST['job_position']),
        'agency'       => trim($_POST['agency']),
        'phone_number' => trim($_POST['phone_number'])
    ];

    // 3. เทียบหาความต่าง (Diff) ระหว่างข้อมูลเก่าและใหม่
    $changes = ['before' => [], 'after' => []];
    $has_changes = false;

    foreach ($old_data as $key => $old_val) {
        $new_val = $new_data[$key];
        // เทียบแบบ string เพื่อป้องกันปัญหา type mismatch (เช่น int vs string numeric)
        if ((string)$old_val !== (string)$new_val) {
            $changes['before'][$key] = $old_val;
            $changes['after'][$key]  = $new_val;
            $has_changes = true;
        }
    }

    // 4. ถ้ามีการเปลี่ยนแปลงข้อมูล ค่อยทำการ UPDATE และบันทึก Log
    if ($has_changes) {
        try {
            $pdo->beginTransaction();

            $ip_address = $_SERVER['REMOTE_ADDR'];
            
            // อัปเดตข้อมูลลงตารางหลัก
            $sql_update = "UPDATE committee_members SET 
                            wg_position = ?, sort_order = ?, prefix = ?, first_name = ?, 
                            last_name = ?, job_position = ?, agency = ?, phone_number = ?, ip_address = ? 
                        WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                $new_data['wg_position'], 
                $new_data['sort_order'], 
                $new_data['prefix'], 
                $new_data['first_name'], 
                $new_data['last_name'], 
                $new_data['job_position'], 
                $new_data['agency'], 
                $new_data['phone_number'], 
                $ip_address, 
                $id
            ]);

            // บันทึก Log แบบละเอียดโดยใช้ฟังก์ชัน systemLog ที่เราสร้างไว้ใน config.php
            systemLog($pdo, 'UPDATE', 'committee_members', $id, $changes);

            $pdo->commit();
            echo "<script>alert('อัปเดตและบันทึกประวัติการแก้ไขเรียบร้อย'); window.location.href='dashboard.php';</script>";
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            die("เกิดข้อผิดพลาด: " . $e->getMessage());
        }
    } else {
        // กรณีที่ผู้ใช้กด "บันทึก" แต่ไม่ได้แก้ตัวอักษรเลยแม้แต่ตัวเดียว
        echo "<script>alert('ไม่มีการเปลี่ยนแปลงข้อมูล'); window.location.href='dashboard.php';</script>";
        exit;
    }
}

// ตรวจสอบว่าตำแหน่งตรงกับ Dropdown ปกติไหม ถ้าไม่ตรงให้ถือว่าเป็น other
$default_positions = ['ประธานกรรมการ', 'กรรมการ', 'กรรมการและเลขานุการ', 'กรรมการและผู้ช่วยเลขานุการ', 'ผู้ช่วยเลขานุการ'];
$is_other = !in_array($member['wg_position'], $default_positions);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-warning fw-bold">แก้ไขข้อมูลรายชื่อ</div>
        <div class="card-body">
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>ตำแหน่งในคณะทำงาน</label>
                        <select class="form-select" onchange="toggleEditCustomPos(this)">
                            </select>
                        <input type="text" class="form-control mt-2" id="customPosInput" style="display: <?= $is_other ? 'block' : 'none' ?>;" value="<?= $is_other ? htmlspecialchars($member['wg_position']) : '' ?>">
                        <input type="hidden" name="wg_position" id="realPosInput" value="<?= htmlspecialchars($member['wg_position']) ?>">
                        
                        <div class="input-group mt-2">
                            <span class="input-group-text bg-light">ลำดับจัดเรียง</span>
                            <input type="number" name="sort_order" class="form-control" value="<?= $member['sort_order'] ?>" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label>คำนำหน้า</label>
                        <input type="text" name="prefix" class="form-control" value="<?= htmlspecialchars($member['prefix']) ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label>ชื่อ</label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($member['first_name']) ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label>นามสกุล</label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($member['last_name']) ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>ตำแหน่งหน้าที่</label>
                        <input type="text" name="job_position" class="form-control" value="<?= htmlspecialchars($member['job_position']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label>หน่วยงานต้นสังกัด</label>
                        <input type="text" name="agency" class="form-control" value="<?= htmlspecialchars($member['agency']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label>เบอร์โทรศัพท์</label>
                        <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($member['phone_number']) ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-success">บันทึกการแก้ไข</button>
                <a href="dashboard.php" class="btn btn-secondary">ยกเลิก</a>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script>
function toggleEditCustomPos(sel) {
    const custom = document.getElementById('customPosInput');
    const real = document.getElementById('realPosInput');
    
    if(sel.value === 'other') {
        custom.style.display = 'block';
        custom.required = true;
        real.value = custom.value;
        custom.oninput = () => real.value = custom.value;
    } else {
        custom.style.display = 'none';
        custom.required = false;
        real.value = sel.value;
    }
}
// Init event listener if it's already 'other'
if(document.getElementById('customPosInput').style.display === 'block') {
    document.getElementById('customPosInput').oninput = function() {
        document.getElementById('realPosInput').value = this.value;
    };
}
</script>
</body>
</html>