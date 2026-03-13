<?php
// ตรวจสอบชื่อไฟล์ปัจจุบันเพื่อทำไฮไลต์เมนู
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="dashboard.php">🏫 ระบบเสนอชื่อคณะทำงานฯ</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active fw-bold' : '' ?>" href="dashboard.php">
                        📊 หน้าแรก (Dashboard)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'index.php' ? 'active fw-bold' : '' ?>" href="index.php">
                        📝 เสนอรายชื่อ
                    </a>
                </li>
                
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($current_page, ['admin_dashboard.php', 'admin_users.php', 'admin_logs.php']) ? 'active fw-bold' : '' ?>" href="#" id="adminMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        ⚙️ เมนูผู้ดูแลระบบ
                    </a>
                    <ul class="dropdown-menu shadow" aria-labelledby="adminMenu">
                        <li><a class="dropdown-item <?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>" href="admin_dashboard.php">จัดการหัวข้อ/ประเด็น</a></li>
                        <li><a class="dropdown-item <?= $current_page == 'admin_users.php' ? 'active' : '' ?>" href="admin_users.php">จัดการผู้ใช้งาน</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item <?= $current_page == 'admin_logs.php' ? 'active' : '' ?>" href="admin_logs.php">ตรวจสอบประวัติ (Audit Logs)</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex align-items-center text-white">
                <span class="me-3">
                    👤 คุณ <strong><?= str_replace(' ', '&nbsp;', htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Guest')) ?></strong>
                    <span class="badge bg-light text-primary ms-1"><?= strtoupper($_SESSION['role'] ?? 'USER') ?></span>
                </span>
                
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        บัญชีของฉัน
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userMenu">
                        <li><a class="dropdown-item <?= $current_page == 'change_password.php' ? 'active fw-bold' : '' ?>" href="change_password.php">🔑 เปลี่ยนรหัสผ่าน</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger fw-bold" href="logout.php">🚪 ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->