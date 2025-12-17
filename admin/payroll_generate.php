<?php
/**
 * admin/payroll_generate.php - สร้าง Payroll ประจำเดือน
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $month = (int)($_POST['month'] ?? 0);
        $year = (int)($_POST['year'] ?? 0);
        $overtimeRate = (float)($_POST['overtime_rate'] ?? 175); // บาทต่อชั่วโมง
        
        // Validation
        if ($month < 1 || $month > 12) $errors[] = 'กรุณาเลือกเดือน';
        if ($year < 2000 || $year > 2100) $errors[] = 'กรุณากรอกปีที่ถูกต้อง';
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // ดึงพนักงานทั้งหมดที่ active
                $stmt = $pdo->query("SELECT * FROM employees WHERE status = 'active'");
                $employees = $stmt->fetchAll();
                
                $generated = 0;
                $skipped = 0;
                
                foreach ($employees as $emp) {
                    $employeeId = $emp['id'];
                    $basicSalary = $emp['salary'];
                    
                    // ตรวจสอบว่ามี payroll เดือนนี้แล้วหรือยัง
                    $stmt = $pdo->prepare("
                        SELECT id FROM payroll 
                        WHERE employee_id = :emp_id AND month = :month AND year = :year
                    ");
                    $stmt->execute(['emp_id' => $employeeId, 'month' => $month, 'year' => $year]);
                    
                    if ($stmt->fetch()) {
                        $skipped++;
                        continue; // มีแล้ว ข้าม
                    }
                    
                    // คำนวณ overtime hours (จากชั่วโมงที่ทำงานเกิน 8 ชม./วัน)
                    $firstDay = sprintf('%04d-%02d-01', $year, $month);
                    $lastDay = date('Y-m-t', strtotime($firstDay));
                    
                    $stmt = $pdo->prepare("
                        SELECT SUM(CASE WHEN work_hours > 8 THEN work_hours - 8 ELSE 0 END) as overtime
                        FROM attendance 
                        WHERE employee_id = :emp_id 
                        AND date BETWEEN :start AND :end
                    ");
                    $stmt->execute([
                        'emp_id' => $employeeId,
                        'start' => $firstDay,
                        'end' => $lastDay
                    ]);
                    $overtimeHours = (float)($stmt->fetch()['overtime'] ?? 0);
                    $overtimePay = $overtimeHours * $overtimeRate;
                    
                    // คำนวณเงินเดือนรวม (ในตัวอย่างนี้ไม่มี allowances/deductions)
                    $allowances = 0;
                    $deductions = 0;
                    $totalSalary = $basicSalary + $overtimePay + $allowances - $deductions;
                    
                    // Insert payroll
                    $stmt = $pdo->prepare("
                        INSERT INTO payroll 
                        (employee_id, month, year, basic_salary, allowances, deductions, 
                         overtime_hours, overtime_pay, total_salary, payment_status) 
                        VALUES (:emp_id, :month, :year, :basic, :allow, :deduct, 
                                :ot_hours, :ot_pay, :total, 'pending')
                    ");
                    $stmt->execute([
                        'emp_id' => $employeeId,
                        'month' => $month,
                        'year' => $year,
                        'basic' => $basicSalary,
                        'allow' => $allowances,
                        'deduct' => $deductions,
                        'ot_hours' => $overtimeHours,
                        'ot_pay' => $overtimePay,
                        'total' => $totalSalary
                    ]);
                    
                    $generated++;
                }
                
                $pdo->commit();
                $success = true;
                $message = "สร้าง Payroll สำเร็จ: {$generated} รายการ";
                if ($skipped > 0) {
                    $message .= " (ข้าม {$skipped} รายการที่มีอยู่แล้ว)";
                }
                setFlashMessage('success', $message);
                redirect('payroll_list.php?month=' . $month . '&year=' . $year);
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'เกิดข้อผิดพลาดในการสร้าง Payroll';
                error_log($e->getMessage());
            }
        }
    }
}

// ค่า default สำหรับฟอร์ม
$currentMonth = (int)date('n');
$currentYear = (int)date('Y');

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>สร้าง Payroll ประจำเดือน</h2>
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
            <div class="alert alert-info">
                <strong>คำแนะนำ:</strong> 
                <ul class="mb-0">
                    <li>ระบบจะคำนวณเงินเดือนสำหรับพนักงานทุกคนที่มีสถานะ Active</li>
                    <li>OT จะคำนวณจากชั่วโมงทำงานที่เกิน 8 ชั่วโมง/วัน</li>
                    <li>หาก Payroll ของเดือนนี้มีอยู่แล้ว ระบบจะไม่สร้างซ้ำ</li>
                </ul>
            </div>
            
            <form method="POST" action="payroll_generate.php">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">เดือน *</label>
                        <select class="form-select" name="month" required>
                            <?php
                            $months = [
                                1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                                5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                                9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                            ];
                            foreach ($months as $num => $name):
                            ?>
                                <option value="<?= $num ?>" <?= $num === $currentMonth ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ปี (พ.ศ.) *</label>
                        <input type="number" class="form-control" name="year" 
                               value="<?= $currentYear + 543 ?>" required>
                        <small class="text-muted">ระบบจะแปลงเป็น ค.ศ. อัตโนมัติ</small>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">อัตรา OT (บาท/ชม.)</label>
                        <input type="number" class="form-control" name="overtime_rate" 
                               value="175" step="0.01" min="0">
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-calculator"></i> สร้าง Payroll
                    </button>
                    <a href="payroll_list.php" class="btn btn-secondary">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// แปลง พ.ศ. เป็น ค.ศ. ก่อน submit
document.querySelector('form').addEventListener('submit', function(e) {
    const yearInput = document.querySelector('input[name="year"]');
    let year = parseInt(yearInput.value);
    
    // ถ้าเป็น พ.ศ. (มากกว่า 2500) ให้ลบ 543
    if (year > 2500) {
        yearInput.value = year - 543;
    }
});
</script>

<?php include '../includes/footer.php'; ?>