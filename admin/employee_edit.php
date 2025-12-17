<?php
/**
 * admin/employee_edit.php - แก้ไขข้อมูลพนักงาน
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();
$errors = [];

// รับ employee ID
$employeeId = (int)($_GET['id'] ?? 0);

if ($employeeId <= 0) {
    setFlashMessage('danger', 'ไม่พบข้อมูลพนักงาน');
    redirect('employees.php');
}

// ดึงข้อมูลพนักงาน
try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id");
    $stmt->execute(['id' => $employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        setFlashMessage('danger', 'ไม่พบข้อมูลพนักงาน');
        redirect('employees.php');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    setFlashMessage('danger', 'เกิดข้อผิดพลาดในการดึงข้อมูล');
    redirect('employees.php');
}

// ประมวลผลฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        // รับข้อมูลจากฟอร์ม
        $employeeCode = trim($_POST['employee_code'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $hireDate = $_POST['hire_date'] ?? '';
        $salary = $_POST['salary'] ?? 0;
        $status = $_POST['status'] ?? 'active';
        
        // Validation
        if (empty($employeeCode)) $errors[] = 'กรุณากรอกรหัสพนักงาน';
        if (empty($firstName)) $errors[] = 'กรุณากรอกชื่อ';
        if (empty($lastName)) $errors[] = 'กรุณากรอกนามสกุล';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'กรุณากรอกอีเมลที่ถูกต้อง';
        }
        if (empty($hireDate)) $errors[] = 'กรุณาเลือกวันที่เริ่มงาน';
        
        // ตรวจสอบว่ารหัสพนักงาน/อีเมลซ้ำหรือไม่ (ยกเว้นตัวเอง)
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT id FROM employees 
                    WHERE (employee_code = :code OR email = :email) 
                    AND id != :id
                ");
                $stmt->execute([
                    'code' => $employeeCode, 
                    'email' => $email, 
                    'id' => $employeeId
                ]);
                if ($stmt->fetch()) {
                    $errors[] = 'รหัสพนักงานหรืออีเมลนี้มีอยู่ในระบบแล้ว';
                }
            } catch (PDOException $e) {
                $errors[] = 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูล';
                error_log($e->getMessage());
            }
        }
        
        // อัปเดตข้อมูล
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE employees SET
                        employee_code = :code,
                        first_name = :fname,
                        last_name = :lname,
                        email = :email,
                        phone = :phone,
                        department = :dept,
                        position = :pos,
                        hire_date = :hire,
                        salary = :salary,
                        status = :status,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    'code' => $employeeCode,
                    'fname' => $firstName,
                    'lname' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'dept' => $department,
                    'pos' => $position,
                    'hire' => $hireDate,
                    'salary' => $salary,
                    'status' => $status,
                    'id' => $employeeId
                ]);
                
                setFlashMessage('success', 'อัปเดตข้อมูลพนักงานสำเร็จ');
                redirect('employees.php');
                
            } catch (PDOException $e) {
                $errors[] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
                error_log($e->getMessage());
            }
        }
    }
    
    // ใช้ข้อมูลจากฟอร์มถ้ามี error
    if (!empty($errors)) {
        $employee = array_merge($employee, $_POST);
    }
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>แก้ไขข้อมูลพนักงาน</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="employees.php">จัดการพนักงาน</a></li>
                    <li class="breadcrumb-item active">แก้ไข</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="employee_edit.php?id=<?= $employeeId ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">รหัสพนักงาน *</label>
                        <input type="text" class="form-control" name="employee_code" 
                               value="<?= h($employee['employee_code']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">อีเมล *</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?= h($employee['email']) ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ชื่อ *</label>
                        <input type="text" class="form-control" name="first_name" 
                               value="<?= h($employee['first_name']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">นามสกุล *</label>
                        <input type="text" class="form-control" name="last_name" 
                               value="<?= h($employee['last_name']) ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" name="phone" 
                               value="<?= h($employee['phone']) ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">แผนก</label>
                        <input type="text" class="form-control" name="department" 
                               value="<?= h($employee['department']) ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ตำแหน่ง</label>
                        <input type="text" class="form-control" name="position" 
                               value="<?= h($employee['position']) ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">วันที่เริ่มงาน *</label>
                        <input type="date" class="form-control" name="hire_date" 
                               value="<?= h($employee['hire_date']) ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">เงินเดือน</label>
                        <input type="number" class="form-control" name="salary" step="0.01" min="0"
                               value="<?= h($employee['salary']) ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">สถานะ</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= $employee['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $employee['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <strong>หมายเหตุ:</strong> การเปลี่ยนแปลงข้อมูลพนักงานจะไม่กระทบต่อ User Account ที่เชื่อมโยงอยู่
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                    <a href="employees.php" class="btn btn-secondary">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- แสดงข้อมูล User Account ที่เชื่อมโยง (ถ้ามี) -->
    <?php
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_id = :id");
        $stmt->execute(['id' => $employeeId]);
        $userAccount = $stmt->fetch();
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
    ?>
    
    <?php if ($userAccount): ?>
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">User Account ที่เชื่อมโยง</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th width="200">Username:</th>
                        <td><?= h($userAccount['username']) ?></td>
                    </tr>
                    <tr>
                        <th>Role:</th>
                        <td>
                            <span class="badge bg-<?= $userAccount['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                <?= h($userAccount['role']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>สร้างเมื่อ:</th>
                        <td><?= h($userAccount['created_at']) ?></td>
                    </tr>
                </table>
                
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i> 
                    หากต้องการเปลี่ยนแปลงข้อมูล User Account กรุณาติดต่อผู้ดูแลระบบ
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card mt-4">
            <div class="card-body text-center">
                <p class="text-muted mb-3">พนักงานคนนี้ยังไม่มี User Account</p>
                <a href="user_create.php?employee_id=<?= $employeeId ?>" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> สร้าง User Account
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>