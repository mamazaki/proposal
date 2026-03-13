<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. ตรวจสอบรหัสผ่านเก่าว่าถูกต้องหรือไม่
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (password_verify($current_pass, $user['password_hash'])) {
        // 2. ตรวจสอบว่ารหัสผ่านใหม่ตรงกันไหม
        if ($new_pass === $confirm_pass) {
            // เช็คความยาวรหัสผ่าน (ควรมีอย่างน้อย 6 ตัวอักษร)
            if (strlen($new_pass) >= 6) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt_update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt_update->execute([$new_hash, $_SESSION['user_id']]);
                
                // บันทึก Log การเปลี่ยนรหัสผ่าน
                systemLog($pdo, 'UPDATE', 'users', $_SESSION['user_id'], ['action' => 'change_password']);
                
                $msg = 'เปลี่ยนรหัสผ่านสำเร็จ!';
                $msg_type = 'success';
            } else {
                $msg = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
                $msg_type = 'danger';
            }
        } else {
            $msg = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
            $msg_type = 'danger';
        }
    } else {
        $msg = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        $msg_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เปลี่ยนรหัสผ่าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white fw-bold">
                        เปลี่ยนรหัสผ่าน (Change Password)
                    </div>
                    <div class="card-body">
                        <?php if($msg): ?>
                            <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">รหัสผ่านปัจจุบัน</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label">รหัสผ่านใหม่</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">บันทึกรหัสผ่านใหม่</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>