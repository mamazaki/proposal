<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // เก็บข้อมูลลง Session (เพิ่ม full_name เข้ามา)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name']; // ดึงชื่อจริงมาเก็บ
        $_SESSION['agency_name'] = $user['agency_name'];
        $_SESSION['role'] = $user['role'];
        
        // บันทึก Log การเข้าสู่ระบบ
        systemLog($pdo, 'LOGIN', 'users', $user['id'], ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);
        
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบเสนอชื่อคณะทำงาน - สำนักงานศึกษาธิการจังหวัดอุดรธานี</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <center><img src="https://pmss.udonpeo.go.th/images/login/130x130.png" alt="Logo" class="mb-4" width="130px" height="130px"></center>
                    <h4 class="text-center mb-4">เข้าสู่ระบบเสนอชื่อคณะทำงาน</h4>
                    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label>ชื่อผู้ใช้</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>รหัสผ่าน</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <center><a href="manual.pdf" target="_blank" class="btn btn-link">คู่มือการใช้งานระบบ</a></center>
    <?php include 'footer.php'; ?>
</div>

</body>
</html>