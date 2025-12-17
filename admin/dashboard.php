<?php
/**
 * admin/dashboard.php - หน้า Dashboard สำหรับ Admin
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

// ดึงสถิติต่างๆ
try {
    // จำนวนพนักงานทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
    $totalEmployees = $stmt->fetch()['total'];
    
    // จำนวนพนักงานที่มาวันนี้
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) as total FROM attendance WHERE date = CURDATE()");
    $stmt->execute();
    $todayAttendance = $stmt->fetch()['total'];
    
    // คำขอลาที่รอ approve
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM leave_requests WHERE status = 'pending'");
    $pendingLeaves = $stmt->fetch()['total'];
    
    // Payroll ที่ยังไม่ได้จ่าย
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM payroll WHERE payment_status = 'pending'");
    $pendingPayroll = $stmt->fetch()['total'];
    
    // รายการ attendance วันนี้
    $stmt = $pdo->prepare("
        SELECT a.*, e.employee_code, e.first_name, e.last_name, e.department
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id
        WHERE a.date = CURDATE()
        ORDER BY a.clock_in DESC
        LIMIT 10
    ");
    $stmt->execute();
    $todayAttendances = $stmt->fetchAll();
    
    // รายการลาที่รออนุมัติ
    $stmt = $pdo->prepare("
        SELECT lr.*, e.employee_code, e.first_name, e.last_name
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        WHERE lr.status = 'pending'
        ORDER BY lr.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $pendingLeaveRequests = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Dashboard - Admin</h2>
            <p class="text-muted">ยินดีต้อนรับ, <?= h($_SESSION['full_name'] ?: $_SESSION['username']) ?></p>
        </div>
    </div>
    
    <!-- สถิติแบบ Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">พนักงานทั้งหมด</h5>
                    <h2><?= $totalEmployees ?></h2>
                    <p class="card-text">Active Employees</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">มาทำงานวันนี้</h5>
                    <h2><?= $todayAttendance ?></h2>
                    <p class="card-text">Today's Attendance</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">คำขอลารออนุมัติ</h5>
                    <h2><?= $pendingLeaves ?></h2>
                    <p class="card-text">Pending Leave Requests</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Payroll รอจ่าย</h5>
                    <h2><?= $pendingPayroll ?></h2>
                    <p class="card-text">Pending Payments</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Attendance วันนี้ -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5>Attendance วันนี้</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>ชื่อ-สกุล</th>
                                <th>แผนก</th>
                                <th>เวลาเข้า</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($todayAttendances)): ?>
                                <tr><td colspan="5" class="text-center">ยังไม่มีข้อมูล</td></tr>
                            <?php else: ?>
                                <?php foreach ($todayAttendances as $att): ?>
                                    <tr>
                                        <td><?= h($att['employee_code']) ?></td>
                                        <td><?= h($att['first_name'] . ' ' . $att['last_name']) ?></td>
                                        <td><?= h($att['department']) ?></td>
                                        <td><?= h($att['clock_in'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $att['status'] === 'present' ? 'success' : 'warning' ?>">
                                                <?= h($att['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <a href="attendance_list.php" class="btn btn-sm btn-primary">ดูทั้งหมด</a>
                </div>
            </div>
        </div>
        
        <!-- Leave Requests รออนุมัติ -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5>คำขอลารออนุมัติ</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingLeaveRequests)): ?>
                        <p class="text-center text-muted">ไม่มีคำขอลารออนุมัติ</p>
                    <?php else: ?>
                        <?php foreach ($pendingLeaveRequests as $leave): ?>
                            <div class="mb-3 p-3 border rounded">
                                <strong><?= h($leave['first_name'] . ' ' . $leave['last_name']) ?></strong>
                                <small class="text-muted">(<?= h($leave['employee_code']) ?>)</small>
                                <br>
                                <small>ประเภท: <?= h($leave['leave_type']) ?></small><br>
                                <small>วันที่: <?= h($leave['start_date']) ?> ถึง <?= h($leave['end_date']) ?></small><br>
                                <small>จำนวน: <?= h($leave['days']) ?> วัน</small>
                                <div class="mt-2">
                                    <a href="leave_approve.php?id=<?= $leave['id'] ?>" class="btn btn-sm btn-success">อนุมัติ</a>
                                    <a href="leave_reject.php?id=<?= $leave['id'] ?>" class="btn btn-sm btn-danger">ปฏิเสธ</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <a href="leave_requests.php" class="btn btn-sm btn-primary">ดูทั้งหมด</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>