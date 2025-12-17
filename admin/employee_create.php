<?php
/**
 * admin/employee_create.php - สร้างพนักงานใหม่
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$errors = [];
$pdo = getDB();

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
        
        // สร้าง user account
        $createUser = isset($_POST['create_user']) ? true : false;
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($employeeCode)) $errors[] = 'กรุณากรอกรหัสพนักงาน';
        if (empty($firstName)) $errors[] = 'กรุณากรอกชื่อ';
        if (empty($lastName)) $errors[] = 'กรุณากรอกนามสกุล';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'กรุณากรอกอีเมลที่ถูกต้อง';
        }
        if (empty($hireDate)) $errors[] = 'กรุณาเลือกวันที่เริ่มงาน';
        
        if ($createUser) {
            if (empty($username)) $errors[] = 'กรุณากรอก Username';
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                $errors[] = 'Password ต้องมีอย่างน้อย ' . PASSWORD_MIN_LENGTH . ' ตัวอักษร';
            }
        }
        
        // ตรวจสอบว่ารหัสพนักงาน/อีเมลซ้ำหรือไม่
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM employees WHERE employee_code = :code OR email = :email");
                $stmt->execute(['code' => $employeeCode, 'email' => $email]);
                if ($stmt->fetch()) {
                    $errors[] = 'รหัสพนักงานหรืออีเมลนี้มีอยู่ในระบบแล้ว';
                }
                
                if ($createUser) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
                    $stmt->execute(['username' => $username]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Username นี้มีอยู่ในระบบแล้ว';
                    }
                }
            } catch (PDOException $e) {
                $errors[] = 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูล';
                error_log($e->getMessage());
            }
        }
        
        // บันทึกข้อมูล
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Insert employee
                $stmt = $pdo->prepare("
                    INSERT INTO employees 
                    (employee_code, first_name, last_name, email, phone, department, position, hire_date, salary, status) 
                    VALUES (:code, :fname, :lname, :email, :phone, :dept, :pos, :hire, :salary, :status)
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
                    'status' => $status
                ]);
                
                $employeeId = $pdo->lastInsertId();
                
                // สร้าง user account ถ้าเลือก
                if ($createUser) {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, password, role, employee_id) 
                        VALUES (:username, :password, 'user', :emp_id)
                    ");
                    $stmt->execute([
                        'username' => $username,
                        'password' => $hashedPassword,
                        'emp_id' => $employeeId
                    ]);
                }
                
                $pdo->commit();
                
                setFlashMessage('success', 'เพิ่มพนักงานสำเร็จ');
                redirect('employees.php');
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
                error_log($e->getMessage());
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>เพิ่มพนักงานใหม่</h2>
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
            <form method="POST" action="employee_create.php">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">รหัสพนักงาน *</label>
                        <input type="text" class="form-control" name="employee_code" 
                               value="<?= h($_POST['employee_code'] ?? '') ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">อีเมล *</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?= h($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ชื่อ *</label>
                        <input type="text" class="form-control" name="first_name" 
                               value="<?= h($_POST['first_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">นามสกุล *</label>
                        <input type="text" class="form-control" name="last_name" 
                               value="<?= h($_POST['last_name'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" name="phone" 
                               value="<?= h($_POST['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">แผนก</label>
                        <input type="text" class="form-control" name="department" 
                               value="<?= h($_POST['department'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ตำแหน่ง</label>
                        <input type="text" class="form-control" name="position" 
                               value="<?= h($_POST['position'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">วันที่เริ่มงาน *</label>
                        <input type="date" class="form-control" name="hire_date" 
                               value="<?= h($_POST['hire_date'] ?? '') ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">เงินเดือน</label>
                        <input type="number" class="form-control" name="salary" step="0.01" min="0"
                               value="<?= h($_POST['salary'] ?? '0') ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">สถานะ</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <hr>
                <h5>สร้าง User Account (ไม่บังคับ)</h5>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="create_user" id="create_user" 
                           <?= isset($_POST['create_user']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="create_user">
                        สร้าง User Account สำหรับพนักงานคนนี้
                    </label>
                </div>
                
                <div id="user-fields" style="display: <?= isset($_POST['create_user']) ? 'block' : 'none' ?>;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" 
                                   value="<?= h($_POST['username'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password">
                            <small class="text-muted">ต้องมีอย่างน้อย <?= PASSWORD_MIN_LENGTH ?> ตัวอักษร</small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                    <a href="employees.php" class="btn btn-secondary">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle user account fields
document.getElementById('create_user').addEventListener('change', function() {
    document.getElementById('user-fields').style.display = this.checked ? 'block' : 'none';
});
</script>

<?php include '../includes/footer.php'; ?>