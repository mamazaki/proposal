<?php
session_start();
require 'config.php';

// บังคับว่าต้องเป็น Admin เท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

// =========================================================
// 1. จัดการ หัวข้อเรื่องหลัก (Main Topics)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_main') {
    $title = trim($_POST['title']);
    $stmt = $pdo->prepare("INSERT INTO main_topics (title, is_active) VALUES (?, 1)");
    $stmt->execute([$title]);
    systemLog($pdo, 'INSERT', 'main_topics', $pdo->lastInsertId(), ['title' => $title]);
    echo "<script>alert('เพิ่มหัวข้อเรื่องหลักสำเร็จ'); window.location.href='admin_dashboard.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_main') {
    $id = $_POST['main_id'];
    $title = trim($_POST['title']);
    $stmt = $pdo->prepare("UPDATE main_topics SET title = ? WHERE id = ?");
    $stmt->execute([$title, $id]);
    systemLog($pdo, 'UPDATE', 'main_topics', $id, ['title' => $title]);
    echo "<script>alert('แก้ไขหัวข้อเรื่องหลักสำเร็จ'); window.location.href='admin_dashboard.php';</script>";
    exit;
}

// ปิดการใช้งานหัวข้อหลัก (Soft Delete)
if (isset($_GET['toggle_main_id'])) {
    $id = $_GET['toggle_main_id'];
    $status = $_GET['status'] == 1 ? 0 : 1; // สลับสถานะ
    $stmt = $pdo->prepare("UPDATE main_topics SET is_active = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    systemLog($pdo, 'UPDATE (Status)', 'main_topics', $id, ['is_active' => $status]);
    echo "<script>window.location.href='admin_dashboard.php';</script>";
    exit;
}


// =========================================================
// 2. จัดการ คณะทำงานย่อย (Sub Groups)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_sub') {
    $main_topic_id = $_POST['main_topic_id'];
    $title = trim($_POST['title']);
    $sort_order = $_POST['sort_order'] ?? 99;
    
    $stmt = $pdo->prepare("INSERT INTO sub_groups (main_topic_id, title, sort_order) VALUES (?, ?, ?)");
    $stmt->execute([$main_topic_id, $title, $sort_order]);
    systemLog($pdo, 'INSERT', 'sub_groups', $pdo->lastInsertId(), ['title' => $title, 'main_topic_id' => $main_topic_id]);
    echo "<script>alert('เพิ่มคณะทำงานย่อยสำเร็จ'); window.location.href='admin_dashboard.php';</script>";
    exit;
}

if (isset($_GET['del_sub_id'])) {
    $id = $_GET['del_sub_id'];
    // การลบ Sub Group เราจะใช้ Hard Delete เพราะถ้าลบไปแล้วคือทิ้งเลย แต่ต้องลบคนข้างในก่อน (Cascade)
    $stmt = $pdo->prepare("DELETE FROM sub_groups WHERE id = ?");
    $stmt->execute([$id]);
    systemLog($pdo, 'DELETE', 'sub_groups', $id, ['deleted_id' => $id]);
    echo "<script>alert('ลบคณะทำงานย่อยสำเร็จ'); window.location.href='admin_dashboard.php';</script>";
    exit;
}

// =========================================================
// ดึงข้อมูลมาแสดงผล
// =========================================================
// ดึงหัวข้อหลักทั้งหมด (รวมอันที่ปิดการใช้งานด้วย เพื่อให้ Admin เปิด/ปิดได้)
$main_topics_all = $pdo->query("SELECT * FROM main_topics ORDER BY id ASC")->fetchAll();

// ดึงหัวข้อย่อยทั้งหมด
$sub_groups = $pdo->query("SELECT sg.*, mt.title as main_title FROM sub_groups sg JOIN main_topics mt ON sg.main_topic_id = mt.id ORDER BY mt.id, sg.sort_order")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการหัวข้อและประเด็น - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    
    <?php include 'navbar.php'; ?>

    <div class="container-fluid px-4 flex-grow-1">
        
        <div class="row mb-5">
            <div class="col-12">
                <h4 class="text-primary fw-bold border-bottom pb-2">📂 1. จัดการหัวข้อเรื่องหลัก (Main Topics)</h4>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white fw-bold">➕ เพิ่มหัวข้อเรื่องหลักใหม่</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_main">
                            <div class="mb-3">
                                <label>ชื่อหัวข้อเรื่องหลัก (เช่น โครงการ..., คณะกรรมการชุดที่...)</label>
                                <textarea name="title" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100">บันทึกหัวข้อหลัก</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">ID</th>
                                    <th>ชื่อหัวข้อเรื่องหลัก</th>
                                    <th width="15%" class="text-center">สถานะ</th>
                                    <th width="15%" class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($main_topics_all as $mt): ?>
                                <tr class="<?= $mt['is_active'] == 0 ? 'table-secondary text-muted' : '' ?>">
                                    <td><?= $mt['id'] ?></td>
                                    <td>
                                        <form method="POST" class="d-flex align-items-center d-none" id="editMainForm_<?= $mt['id'] ?>">
                                            <input type="hidden" name="action" value="edit_main">
                                            <input type="hidden" name="main_id" value="<?= $mt['id'] ?>">
                                            <input type="text" name="title" class="form-control form-control-sm me-2" value="<?= htmlspecialchars($mt['title']) ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Save</button>
                                            <button type="button" class="btn btn-sm btn-secondary ms-1" onclick="toggleEditMain(<?= $mt['id'] ?>)">Cancel</button>
                                        </form>
                                        <span id="mainText_<?= $mt['id'] ?>"><?= htmlspecialchars($mt['title']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if($mt['is_active'] == 1): ?>
                                            <span class="badge bg-success">เปิดใช้งาน</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ปิดใช้งาน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-warning text-dark" onclick="toggleEditMain(<?= $mt['id'] ?>)">✏️</button>
                                            <a href="?toggle_main_id=<?= $mt['id'] ?>&status=<?= $mt['is_active'] ?>" class="btn btn-sm <?= $mt['is_active'] == 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" onclick="return confirm('ยืนยันการเปลี่ยนสถานะหัวข้อนี้?');">
                                                <?= $mt['is_active'] == 1 ? 'ปิด' : 'เปิด' ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-12">
                <h4 class="text-success fw-bold border-bottom pb-2">📑 2. จัดการประเด็น / คณะทำงานย่อย (Sub Groups)</h4>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm border-success">
                    <div class="card-header bg-success text-white fw-bold">➕ เพิ่มคณะทำงานย่อย</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_sub">
                            <div class="mb-3">
                                <label>ภายใต้หัวข้อเรื่องหลัก</label>
                                <select name="main_topic_id" class="form-select" required>
                                    <option value="">-- เลือกหัวข้อเรื่องหลัก --</option>
                                    <?php foreach($main_topics_all as $mt): 
                                        if($mt['is_active'] == 1): // โชว์เฉพาะอันที่เปิดใช้งานให้เลือก
                                    ?>
                                        <option value="<?= $mt['id'] ?>"><?= htmlspecialchars($mt['title']) ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>ชื่อประเด็น/คณะทำงานย่อย</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>ลำดับที่จัดเรียง (ค่าน้อยอยู่บน)</label>
                                <input type="number" name="sort_order" class="form-control" value="99">
                            </div>
                            <button type="submit" class="btn btn-success w-100">บันทึกคณะทำงานย่อย</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%">ลำดับจัดเรียง</th>
                                    <th>ภายใต้หัวข้อเรื่องหลัก</th>
                                    <th>ชื่อประเด็น / คณะทำงานย่อย</th>
                                    <th width="10%" class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($sub_groups as $sg): ?>
                                <tr>
                                    <td class="text-center"><?= $sg['sort_order'] ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($sg['main_title']) ?></small></td>
                                    <td class="fw-bold"><?= htmlspecialchars($sg['title']) ?></td>
                                    <td class="text-center">
                                        <a href="?del_sub_id=<?= $sg['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ระวัง!! การลบหัวข้อย่อยจะทำให้รายชื่อบุคคลภายใต้หัวข้อนี้หายไปจากหน้าจอทั้งหมด ยืนยันการลบหรือไม่?');">🗑️ ลบ</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ฟังก์ชันสำหรับเปิด/ปิด ช่องแก้ไขชื่อหัวข้อหลักแบบ In-line
        function toggleEditMain(id) {
            const form = document.getElementById('editMainForm_' + id);
            const text = document.getElementById('mainText_' + id);
            
            if (form.classList.contains('d-none')) {
                form.classList.remove('d-none');
                text.classList.add('d-none');
            } else {
                form.classList.add('d-none');
                text.classList.remove('d-none');
            }
        }
    </script>
</body>
</html>