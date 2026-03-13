<?php
session_start();
require 'config.php';

// บังคับว่าต้องเป็น Admin เท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

// ---------------------------------------------------------
// 1. จัดการ Action: เพิ่มผู้ใช้ใหม่ (ADD)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']); // เพิ่มรับค่าชื่อจริง
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $agency = trim($_POST['agency_name']);
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, password_hash, agency_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $full_name, $password, $agency, $role]);
        $new_id = $pdo->lastInsertId();
        
        systemLog($pdo, 'INSERT', 'users', $new_id, ['username' => $username, 'full_name' => $full_name, 'role' => $role, 'agency' => $agency]);
        echo "<script>alert('เพิ่มผู้ใช้สำเร็จ'); window.location.href='admin_users.php';</script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('Error: ชื่อผู้ใช้นี้อาจมีซ้ำในระบบ หรือเกิดข้อผิดพลาดฐานข้อมูล');</script>";
    }
}

// ---------------------------------------------------------
// 2. จัดการ Action: แก้ไขผู้ใช้ (EDIT SAVE)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $edit_target_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']); // เพิ่มรับค่าชื่อจริง
    $agency = trim($_POST['agency_name']);
    $role = $_POST['role'];
    $new_password = $_POST['password'];

    try {
        // ดึงข้อมูลเก่ามาเทียบเพื่อทำ Log
        $stmt_old = $pdo->prepare("SELECT username, full_name, agency_name, role FROM users WHERE id = ?");
        $stmt_old->execute([$edit_target_id]);
        $old_data = $stmt_old->fetch();

        // ตรวจสอบว่ามีการพิมพ์รหัสผ่านใหม่มาหรือไม่
        if (!empty($new_password)) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, password_hash = ?, agency_name = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $full_name, $hash, $agency, $role, $edit_target_id]);
            $pass_changed = true;
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, agency_name = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $full_name, $agency, $role, $edit_target_id]);
            $pass_changed = false;
        }

        // จัดเตรียมข้อมูลสำหรับบันทึก Log
        $changes = [
            'before' => $old_data,
            'after' => ['username' => $username, 'full_name' => $full_name, 'agency_name' => $agency, 'role' => $role],
            'password_changed' => $pass_changed
        ];
        systemLog($pdo, 'UPDATE', 'users', $edit_target_id, $changes);
        
        echo "<script>alert('แก้ไขข้อมูลผู้ใช้สำเร็จ'); window.location.href='admin_users.php';</script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('Error: ไม่สามารถแก้ไขข้อมูลได้ อาจมีชื่อผู้ใช้ซ้ำ');</script>";
    }
}

// ---------------------------------------------------------
// 3. จัดการ Action: ลบผู้ใช้ (DELETE)
// ---------------------------------------------------------
if (isset($_GET['del_id'])) {
    $del_id = $_GET['del_id'];
    if ($del_id != $_SESSION['user_id']) { // กันไม่ให้ Admin ลบตัวเอง
        $stmt_get = $pdo->prepare("SELECT username, full_name FROM users WHERE id = ?");
        $stmt_get->execute([$del_id]);
        $del_user = $stmt_get->fetch();

        if ($del_user) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$del_id]);
            systemLog($pdo, 'DELETE', 'users', $del_id, ['deleted_username' => $del_user['username'], 'deleted_name' => $del_user['full_name'], 'deleted_by' => $_SESSION['username']]);
            echo "<script>alert('ลบผู้ใช้สำเร็จ'); window.location.href='admin_users.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('ไม่อนุญาตให้ลบบัญชีของคุณเองในขณะที่กำลังล็อกอินอยู่'); window.location.href='admin_users.php';</script>";
        exit;
    }
}

// ---------------------------------------------------------
// 4. โหลดข้อมูลสำหรับโหมดแก้ไข
// ---------------------------------------------------------
$edit_mode = false;
$edit_data = [];
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $stmt_edit = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_edit->execute([$_GET['edit_id']]);
    $edit_data = $stmt_edit->fetch();
    
    if (!$edit_data) {
        echo "<script>alert('ไม่พบผู้ใช้ที่ต้องการแก้ไข'); window.location.href='admin_users.php';</script>";
        exit;
    }
}

// ดึงรายชื่อ User ทั้งหมดมาแสดง
$users = $pdo->query("SELECT id, username, full_name, agency_name, role FROM users ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    
    <?php include 'navbar.php'; ?>

    <div class="container-fluid px-4">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-<?= $edit_mode ? 'warning' : 'primary' ?>">
                    <div class="card-header bg-<?= $edit_mode ? 'warning text-dark' : 'primary text-white' ?> fw-bold">
                        <?= $edit_mode ? '✏️ แก้ไขข้อมูลผู้ใช้งาน (ID: '.$edit_data['id'].')' : '➕ เพิ่มผู้ใช้งานใหม่' ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="admin_users.php">
                            <input type="hidden" name="action" value="<?= $edit_mode ? 'edit' : 'add' ?>">
                            <?php if($edit_mode): ?>
                                <input type="hidden" name="user_id" value="<?= $edit_data['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Username (ใช้ตอน Login)</label>
                                <input type="text" name="username" class="form-control" value="<?= $edit_mode ? htmlspecialchars($edit_data['username']) : '' ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">ชื่อ-นามสกุล (ผู้ใช้งาน)</label>
                                <input type="text" name="full_name" class="form-control" value="<?= $edit_mode ? htmlspecialchars($edit_data['full_name']) : '' ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" <?= $edit_mode ? '' : 'required' ?> placeholder="<?= $edit_mode ? 'ปล่อยว่างไว้หากไม่เปลี่ยนรหัสผ่าน' : 'ตั้งรหัสผ่านใหม่' ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">หน่วยงานต้นสังกัด</label>
                                <input type="text" name="agency_name" class="form-control" value="<?= $edit_mode ? htmlspecialchars($edit_data['agency_name']) : '' ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">สิทธิ์การใช้งาน (Role)</label>
                                <select name="role" class="form-select" required>
                                    <option value="user" <?= ($edit_mode && $edit_data['role'] == 'user') ? 'selected' : '' ?>>User (จัดการได้เฉพาะข้อมูลตัวเอง)</option>
                                    <option value="admin" <?= ($edit_mode && $edit_data['role'] == 'admin') ? 'selected' : '' ?>>Admin (จัดการได้ทั้งหมด)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-<?= $edit_mode ? 'warning' : 'success' ?> w-100 fw-bold">
                                <?= $edit_mode ? 'บันทึกการแก้ไข' : 'บันทึกผู้ใช้ใหม่' ?>
                            </button>
                            
                            <?php if($edit_mode): ?>
                                <a href="admin_users.php" class="btn btn-secondary w-100 mt-2">ยกเลิก / กลับไปหน้าเพิ่มผู้ใช้</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white fw-bold">
                        👥 รายชื่อผู้ใช้งานในระบบทั้งหมด
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>หน่วยงาน</th>
                                        <th>สิทธิ์</th>
                                        <th class="text-center">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $u): ?>
                                    <tr class="<?= ($edit_mode && $edit_data['id'] == $u['id']) ? 'table-warning' : '' ?>">
                                        <td><?= $u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><?= str_replace(' ', '&nbsp;', htmlspecialchars($u['full_name'])) ?></td>
                                        <td><?= htmlspecialchars($u['agency_name']) ?></td>
                                        <td>
                                            <span class="badge <?= $u['role']=='admin' ? 'bg-danger' : 'bg-primary' ?>">
                                                <?= strtoupper($u['role']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="?edit_id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning text-dark">✏️ แก้ไข</a>
                                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                                    <a href="?del_id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันการลบผู้ใช้งาน: <?= htmlspecialchars($u['full_name']) ?> ?');">🗑️ ลบ</a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-secondary" disabled>ตัวเอง</button>
                                                <?php endif; ?>
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
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>