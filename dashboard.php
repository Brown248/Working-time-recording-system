<?php
/**
 * employee/dashboard.php - หน้า Dashboard สำหรับพนักงาน
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$pdo = getDB();
$employeeId = $_SESSION['employee_id'];

// ดึงข้อมูลพนักงาน
try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id");
    $stmt->execute(['id' => $employeeId]);
    $employee = $stmt->fetch();
    
    // Attendance วันนี้
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :id AND date = :date");
    $stmt->execute(['id' => $employeeId, 'date' => $today]);
    $todayAttendance = $stmt->fetch();
    
    // Attendance เดือนนี้
    $firstDay = date('Y-m-01');
    $lastDay = date('Y-m-t');
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as days, SUM(work_hours) as hours 
        FROM attendance 
        WHERE employee_id = :id AND date BETWEEN :start AND :end
    ");
    $stmt->execute(['id' => $employeeId, 'start' => $firstDay, 'end' => $lastDay]);
    $monthStats = $stmt->fetch();
    
    // Leave requests ที่รออนุมัติ
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pending 
        FROM leave_requests 
        WHERE employee_id = :id AND status = 'pending'
    ");
    $stmt->execute(['id' => $employeeId]);
    $pendingLeaves = $stmt->fetch()['pending'];
    
    // Payroll เดือนล่าสุด
    $stmt = $pdo->prepare("
        SELECT * FROM payroll 
        WHERE employee_id = :id 
        ORDER BY year DESC, month DESC 
        LIMIT 1
    ");
    $stmt->execute(['id' => $employeeId]);
    $latestPayroll = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Dashboard - พนักงาน</h2>
            <p class="text-muted">ยินดีต้อนรับ, <?= h($employee['first_name'] . ' ' . $employee['last_name']) ?></p>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>วันทำงานเดือนนี้</h5>
                    <h2><?= $monthStats['days'] ?> วัน</h2>
                    <small><?= number_format($monthStats['hours'], 2) ?> ชั่วโมง</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-<?= $todayAttendance && $todayAttendance['clock_in'] ? 'success' : 'warning' ?> text-white">
                <div class="card-body">
                    <h5>Attendance วันนี้</h5>
                    <?php if ($todayAttendance && $todayAttendance['clock_in']): ?>
                        <p class="mb-1">เข้า: <?= h($todayAttendance['clock_in']) ?></p>
                        <?php if ($todayAttendance['clock_out']): ?>
                            <p class="mb-0">ออก: <?= h($todayAttendance['clock_out']) ?></p>
                        <?php else: ?>
                            <p class="mb-0">ยังไม่ออกงาน</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>ยังไม่ได้ Clock In</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>คำขอลารออนุมัติ</h5>
                    <h2><?= $pendingLeaves ?></h2>
                    <small>รายการ</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>เงินเดือนล่าสุด</h5>
                    <?php if ($latestPayroll): ?>
                        <h2><?= number_format($latestPayroll['total_salary'], 2) ?></h2>
                        <small><?= $latestPayroll['month'] ?>/<?= $latestPayroll['year'] ?></small>
                    <?php else: ?>
                        <p>ยังไม่มีข้อมูล</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">บันทึกเวลาเข้า-ออกงาน</h5>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted">เวลาปัจจุบัน: <strong><?= date('H:i:s') ?></strong></p>
                    
                    <form method="POST" action="../attendance/clockin.php" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-lg btn-success m-2" 
                                <?= ($todayAttendance && $todayAttendance['clock_in']) ? 'disabled' : '' ?>>
                            <i class="bi bi-clock"></i> Clock In
                        </button>
                    </form>
                    
                    <form method="POST" action="../attendance/clockout.php" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-lg btn-danger m-2"
                                <?= (!$todayAttendance || !$todayAttendance['clock_in'] || $todayAttendance['clock_out']) ? 'disabled' : '' ?>>
                            <i class="bi bi-clock"></i> Clock Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">เมนูด่วน</h5>
                </div>
                <div class="card-body">
                    <a href="attendance.php" class="btn btn-outline-primary btn-block mb-2 w-100">
                        <i class="bi bi-calendar-check"></i> ดูประวัติ Attendance
                    </a>
                    <a href="../leave/request.php" class="btn btn-outline-success btn-block mb-2 w-100">
                        <i class="bi bi-envelope-paper"></i> ขอลา
                    </a>
                    <a href="leave.php" class="btn btn-outline-warning btn-block mb-2 w-100">
                        <i class="bi bi-list-check"></i> ดูรายการลา
                    </a>
                    <a href="payroll.php" class="btn btn-outline-info btn-block w-100">
                        <i class="bi bi-cash-stack"></i> ดูเงินเดือน
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>