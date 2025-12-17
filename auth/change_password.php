<?php
/**
 * auth/change_password.php - เปลี่ยนรหัสผ่าน
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$pdo = getDB();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword)) {
            $errors[] = 'กรุณากรอกรหัสผ่านปัจจุบัน';
        }
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย ' . PASSWORD_MIN_LENGTH . ' ตัวอักษร';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'รหัสผ่านใหม่ไม่ตรงกัน';
        }
        
        // ตรวจสอบรหัสผ่านเดิม
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
                }
            } catch (PDOException $e) {
                $errors[] = 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูล';
                error_log($e->getMessage());
            }
        }
        
        // อัปเดตรหัสผ่าน
        if (empty($errors)) {
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute([
                    'password' => $hashedPassword,
                    'id' => $_SESSION['user_id']
                ]);
                
                $success = true;
                setFlashMessage('success', 'เปลี่ยนรหัสผ่านสำเร็จ');
                
            } catch (PDOException $e) {
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
            <h2>เปลี่ยนรหัสผ่าน</h2>
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
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            เปลี่ยนรหัสผ่านสำเร็จ!
        </div>
    <?php endif; ?>
    
    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="change_password.php">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-3">
                    <label class="form-label">รหัสผ่านปัจจุบัน *</label>
                    <input type="password" class="form-control" name="current_password" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">รหัสผ่านใหม่ *</label>
                    <input type="password" class="form-control" name="new_password" required>
                    <small class="text-muted">ต้องมีอย่างน้อย <?= PASSWORD_MIN_LENGTH ?> ตัวอักษร</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">ยืนยันรหัสผ่านใหม่ *</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">เปลี่ยนรหัสผ่าน</button>
                    <a href="<?= isAdmin() ? '../admin/dashboard.php' : '../employee/dashboard.php' ?>" 
                       class="btn btn-secondary">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>