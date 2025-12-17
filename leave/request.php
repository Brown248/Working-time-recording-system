<?php
/**
 * leave/request.php - ฟอร์มขอลา
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$pdo = getDB();
$errors = [];

// ต้องมี employee_id
if (empty($_SESSION['employee_id'])) {
    setFlashMessage('danger', 'ไม่พบข้อมูลพนักงาน');
    redirect('../employee/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $employeeId = $_SESSION['employee_id'];
        $leaveType = $_POST['leave_type'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        
        // Validation
        if (empty($leaveType)) $errors[] = 'กรุณาเลือกประเภทการลา';
        if (empty($startDate)) $errors[] = 'กรุณาเลือกวันที่เริ่มลา';
        if (empty($endDate)) $errors[] = 'กรุณาเลือกวันที่สิ้นสุดการลา';
        if (empty($reason)) $errors[] = 'กรุณากรอกเหตุผล';
        
        // ตรวจสอบวันที่
        if (!empty($startDate) && !empty($endDate)) {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            
            if ($start > $end) {
                $errors[] = 'วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด';
            }
            
            // คำนวณจำนวนวันลา (ไม่นับเสาร์-อาทิตย์)
            $days = 0;
            $current = clone $start;
            while ($current <= $end) {
                $dayOfWeek = $current->format('N'); // 1 (Monday) ถึง 7 (Sunday)
                if ($dayOfWeek < 6) { // จันทร์-ศุกร์
                    $days++;
                }
                $current->modify('+1 day');
            }
            
            if ($days <= 0) {
                $errors[] = 'จำนวนวันลาต้องมากกว่า 0';
            }
        }
        
        // บันทึกข้อมูล
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO leave_requests 
                    (employee_id, leave_type, start_date, end_date, days, reason, status) 
                    VALUES (:emp_id, :type, :start, :end, :days, :reason, 'pending')
                ");
                $stmt->execute([
                    'emp_id' => $employeeId,
                    'type' => $leaveType,
                    'start' => $startDate,
                    'end' => $endDate,
                    'days' => $days,
                    'reason' => $reason
                ]);
                
                setFlashMessage('success', 'ส่งคำขอลาสำเร็จ รอการอนุมัติ');
                redirect('../employee/leave.php');
                
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
            <h2>ขอลา</h2>
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
            <form method="POST" action="request.php">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-3">
                    <label class="form-label">ประเภทการลา *</label>
                    <select class="form-select" name="leave_type" required>
                        <option value="">-- เลือกประเภท --</option>
                        <option value="sick" <?= ($_POST['leave_type'] ?? '') === 'sick' ? 'selected' : '' ?>>ลาป่วย</option>
                        <option value="personal" <?= ($_POST['leave_type'] ?? '') === 'personal' ? 'selected' : '' ?>>ลากิจ</option>
                        <option value="vacation" <?= ($_POST['leave_type'] ?? '') === 'vacation' ? 'selected' : '' ?>>ลาพักร้อน</option>
                        <option value="other" <?= ($_POST['leave_type'] ?? '') === 'other' ? 'selected' : '' ?>>อื่นๆ</option>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">วันที่เริ่มลา *</label>
                        <input type="date" class="form-control" name="start_date" 
                               value="<?= h($_POST['start_date'] ?? '') ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">วันที่สิ้นสุด *</label>
                        <input type="date" class="form-control" name="end_date" 
                               value="<?= h($_POST['end_date'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">เหตุผล *</label>
                    <textarea class="form-control" name="reason" rows="4" required><?= h($_POST['reason'] ?? '') ?></textarea>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="bi bi-info-circle"></i> 
                        หมายเหตุ: ระบบจะคำนวณจำนวนวันลาโดยไม่นับเสาร์-อาทิตย์
                    </small>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">ส่งคำขอลา</button>
                    <a href="../employee/leave.php" class="btn btn-secondary">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>