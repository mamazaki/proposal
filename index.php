<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $sub_group_id = $_POST['sub_group_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
            $pdo->beginTransaction();
            
            $ip_address = $_SERVER['REMOTE_ADDR']; // เก็บ IP
            
            // มี 11 คอลัมน์ ต้องมี ? 11 ตัว
            $stmt = $pdo->prepare("INSERT INTO committee_members 
                (sub_group_id, user_id, wg_position, sort_order, prefix, first_name, last_name, job_position, agency, phone_number, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($_POST['first_name'] as $key => $fname) {
                if (!empty(trim($fname))) {
                    $stmt->execute([
                        $sub_group_id,                 // 1. sub_group_id
                        $_SESSION['user_id'],          // 2. user_id
                        $_POST['wg_position'][$key],   // 3. wg_position
                        $_POST['sort_order'][$key],    // 4. sort_order
                        $_POST['prefix'][$key],        // 5. prefix
                        $fname,                        // 6. first_name
                        $_POST['last_name'][$key],     // 7. last_name
                        $_POST['job_position'][$key],  // 8. job_position
                        $_POST['agency'][$key],        // 9. agency
                        $_POST['phone_number'][$key],  // 10. phone_number
                        $ip_address                    // 11. ip_address
                    ]);
                }
            }
            $pdo->commit();
            echo "<script>alert('บันทึกข้อมูลสำเร็จ'); window.location.href='dashboard.php';</script>";
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Error: " . $e->getMessage();
        }
}

// โหลด Main Topics สำหรับ Dropdown แรก
$stmt_main = $pdo->query("SELECT * FROM main_topics WHERE is_active = 1");
$main_topics = $stmt_main->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เสนอชื่อคณะทำงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .autocomplete-list { max-height: 200px; overflow-y: auto; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>


<div class="container-fluid px-4">
    <?php if(isset($error_msg)): ?><div class="alert alert-danger"><?= $error_msg ?></div><?php endif; ?>
    
    <form method="POST" action="">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">หัวข้อเรื่องหลัก</label>
                <select id="mainTopic" class="form-select" onchange="loadSubGroups()" required>
                    <option value="">-- เลือกหัวข้อเรื่องหลัก --</option>
                    <?php foreach($main_topics as $mt): ?>
                        <option value="<?= $mt['id'] ?>"><?= htmlspecialchars($mt['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">ประเด็นความร่วมมือ / คณะทำงานย่อย</label>
                <select name="sub_group_id" id="subGroup" class="form-select" required disabled>
                    <option value="">-- กรุณาเลือกหัวข้อหลักก่อน --</option>
                </select>
            </div>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">รายชื่อคณะทำงาน</h5>
            <button type="button" class="btn btn-success" onclick="addRow()">+ เพิ่มแถวรายชื่อ</button>
        </div>

        <div id="dynamic-rows">
            <div class="row mb-2 person-row align-items-start g-2">
                <div class="col-md-2">
                    <select class="form-select position-select" onchange="toggleCustomPosition(this)">
                        <option value="ประธานกรรมการ">ประธานกรรมการ</option>
                        <option value="กรรมการ" selected>กรรมการ</option>
                        <option value="กรรมการและเลขานุการ">กรรมการและเลขานุการ</option>
                        <option value="กรรมการและผู้ช่วยเลขานุการ">กรรมการและผู้ช่วยเลขานุการ</option>
                        <option value="ผู้ช่วยเลขานุการ">ผู้ช่วยเลขานุการ</option>
                        <option value="other">อื่น ๆ (ระบุ)</option>
                    </select>
                    <input type="text" class="form-control mt-1 custom-position" style="display:none;" placeholder="ระบุตำแหน่ง...">
                    <input type="hidden" name="wg_position[]" class="real-position" value="กรรมการ">
                    
                    <div class="input-group input-group-sm mt-1">
                        <span class="input-group-text bg-light">ลำดับ</span>
                        <input type="number" name="sort_order[]" class="form-control input-sort" value="50" required title="ค่าน้อยอยู่บน ค่ามากอยู่ล่าง">
                        <span class="input-group-text bg-light">"ค่าน้อยอยู่บน ค่ามากอยู่ล่าง"</span>
                    </div>
                </div>
                <div class="col-md-1">
                    <input type="text" name="prefix[]" class="form-control input-prefix" placeholder="คำนำ" required>
                </div>
                <div class="col-md-2 position-relative">
                    <input type="text" name="first_name[]" class="form-control input-fname" placeholder="ชื่อ (พิมพ์เพื่อค้นหา)" onkeyup="searchPerson(this)" autocomplete="off" required>
                    <div class="autocomplete-list list-group position-absolute w-100 shadow" style="z-index: 1000; display: none;"></div>
                </div>
                <div class="col-md-2">
                    <input type="text" name="last_name[]" class="form-control input-lname" placeholder="สกุล" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="job_position[]" class="form-control input-job" placeholder="ตำแหน่ง/อาชีพ" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="agency[]" class="form-control input-agency mb-1" placeholder="สังกัด/หน่วยงาน" required>
                    <input type="text" name="phone_number[]" class="form-control input-phone" placeholder="หมายเลขโทรศัพท์ (ถ้ามี)">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger w-100" onclick="removeRow(this)">ลบ</button>
                </div>
            </div>
        </div>

        <div class="mt-4 mb-5">
            <button type="submit" name="submit" class="btn btn-primary btn-lg">บันทึกข้อมูลทั้งหมด</button>
            <a href="dashboard.php" class="btn btn-secondary btn-lg ms-2">ยกเลิก/กลับไปหน้า Dashboard</a>
        </div>
    </form>
</div>
<?php include 'footer.php'; ?>
<script>
async function loadSubGroups() {
    const mainId = document.getElementById('mainTopic').value;
    const subSelect = document.getElementById('subGroup');
    subSelect.innerHTML = '<option value="">-- กำลังโหลด... --</option>';
    subSelect.disabled = true;

    if (!mainId) {
        subSelect.innerHTML = '<option value="">-- กรุณาเลือกหัวข้อหลักก่อน --</option>';
        return;
    }

    try {
        const res = await fetch(`api_get_subgroups.php?main_id=${mainId}`);
        const data = await res.json();
        subSelect.innerHTML = '<option value="">-- เลือกประเด็น/คณะทำงาน --</option>';
        data.forEach(item => {
            subSelect.innerHTML += `<option value="${item.id}">${item.title}</option>`;
        });
        subSelect.disabled = false;
    } catch (e) {
        console.error(e);
        subSelect.innerHTML = '<option value="">-- เกิดข้อผิดพลาด --</option>';
    }
}

// อัปเดตฟังก์ชัน toggleCustomPosition เดิม
function toggleCustomPosition(sel) {
    const row = sel.closest('.person-row');
    const customInput = row.querySelector('.custom-position');
    const realInput = row.querySelector('.real-position');
    const sortInput = row.querySelector('.input-sort'); // ดึง element ลำดับมา

    // กำหนดลำดับเริ่มต้นตาม Logic ที่คุณต้องการ (ค่าน้อยอยู่บน)
    let defaultSort = 50; 
    if (sel.value === 'ประธานกรรมการ') defaultSort = 1;
    else if (sel.value === 'กรรมการ') defaultSort = 50;
    else if (sel.value === 'กรรมการและเลขานุการ') defaultSort = 90;
    else if (sel.value === 'กรรมการและผู้ช่วยเลขานุการ') defaultSort = 98;
    else if (sel.value === 'ผู้ช่วยเลขานุการ') defaultSort = 99;

    sortInput.value = defaultSort; // จับยัดค่าลงไปให้ User อัตโนมัติ

    if (sel.value === 'other') {
        customInput.style.display = 'block';
        customInput.required = true;
        realInput.value = customInput.value;
        customInput.oninput = function() { realInput.value = this.value; };
    } else {
        customInput.style.display = 'none';
        customInput.required = false;
        realInput.value = sel.value;
    }
}

let typingTimer;
function searchPerson(inputElem) {
    clearTimeout(typingTimer);
    const query = inputElem.value.trim();
    const resultBox = inputElem.nextElementSibling;
    if (query.length < 2) { resultBox.style.display = 'none'; return; }

    typingTimer = setTimeout(async () => {
        try {
            const res = await fetch(`api_search_person.php?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            resultBox.innerHTML = '';
            if (data.length > 0) {
                data.forEach(person => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'list-group-item list-group-item-action p-2';
                    btn.innerHTML = `<span class="fw-bold">${person.prefix}${person.first_name} ${person.last_name}</span><br><small class="text-muted">${person.agency}</small>`;
                    btn.onclick = () => fillData(inputElem, person);
                    resultBox.appendChild(btn);
                });
                resultBox.style.display = 'block';
            } else {
                resultBox.style.display = 'none';
            }
        } catch (e) { console.error(e); }
    }, 300);
}

function fillData(inputElem, p) {
    const row = inputElem.closest('.person-row');
    row.querySelector('.input-prefix').value = p.prefix || '';
    row.querySelector('.input-fname').value = p.first_name || '';
    row.querySelector('.input-lname').value = p.last_name || '';
    row.querySelector('.input-job').value = p.job_position || '';
    row.querySelector('.input-agency').value = p.agency || '';
    row.querySelector('.input-phone').value = p.phone_number || '';
    inputElem.nextElementSibling.style.display = 'none';
}

document.addEventListener('click', e => {
    if (!e.target.classList.contains('input-fname')) {
        document.querySelectorAll('.autocomplete-list').forEach(b => b.style.display = 'none');
    }
});

function addRow() {
    const row = document.querySelector('.person-row').cloneNode(true);
    row.querySelectorAll('input').forEach(i => { if(i.type !== 'hidden') i.value = ''; });
    row.querySelector('.custom-position').style.display = 'none';
    row.querySelector('.autocomplete-list').style.display = 'none';
    document.getElementById('dynamic-rows').appendChild(row);
}

function removeRow(btn) {
    if(document.querySelectorAll('.person-row').length > 1) {
        btn.closest('.person-row').remove();
    } else {
        alert('ต้องมีอย่างน้อย 1 รายชื่อ');
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>