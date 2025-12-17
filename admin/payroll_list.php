<?php
/**
 * admin/payroll_list.php - รายการ Payroll ทั้งหมด
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

// รับพารามิเตอร์เดือนและปี (default = เดือนปัจจุบัน)
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));

// ดึงข้อมูล payroll
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            e.employee_code,
            e.first_name,
            e.last_name,
            e.department
        FROM payroll p
        JOIN employees e ON p.employee_id = e.id
        WHERE p.month = :month AND p.year = :year
        ORDER BY e.employee_code
    ");
    $stmt->execute(['month' => $month, 'year' => $year]);
    $payrolls = $stmt->fetchAll();
    
    // คำนวณยอดรวม
    $totalSalary = array_sum(array_column($payrolls, 'total_salary'));
    $totalPaid = 0;
    $totalPending = 0;
    
    foreach ($payrolls as $p) {
        if ($p['payment_status'] === 'paid') {
            $totalPaid += $p['total_salary'];
        } else {
            $totalPending += $p['total_salary'];
        }
    }
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $payrolls = [];
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Payroll ประจำเดือน</h2>
        </div>
        <div class="col text-end">
            <a href="payroll_generate.php" class="btn btn-primary">
                <i class="bi bi-calculator"></i> สร้าง Payroll
            </a>
            <?php if (!empty($payrolls)): ?>
                <a href="payroll_export_csv.php?month=<?= $month ?>&year=<?= $year ?>" 
                   class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Export CSV
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="payroll_list.php" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">เดือน</label>
                    <select class="form-select" name="month">
                        <?php
                        $months = [
                            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                        ];
                        foreach ($months as $num => $name):
                        ?>
                            <option value="<?= $num ?>" <?= $num === $month ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">ปี</label>
                    <select class="form-select" name="year">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">ค้นหา</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- สรุปยอด -->
    <?php if (!empty($payrolls)): ?>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6>ยอดรวมทั้งหมด</h6>
                        <h3><?= number_format($totalSalary, 2) ?> ฿</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6>จ่ายแล้ว</h6>
                        <h3><?= number_format($totalPaid, 2) ?> ฿</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6>ยังไม่จ่าย</h6>
                        <h3><?= number_format($totalPending, 2) ?> ฿</h3>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- ตาราง Payroll -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อ-สกุล</th>
                            <th>แผนก</th>
                            <th>เงินเดือน</th>
                            <th>เบี้ยเลี้ยง</th>
                            <th>หักเงิน</th>
                            <th>OT</th>
                            <th>รวม</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payrolls)): ?>
                            <tr><td colspan="10" class="text-center">ไม่พบข้อมูล Payroll</td></tr>
                        <?php else: ?>
                            <?php foreach ($payrolls as $p): ?>
                                <tr>
                                    <td><?= h($p['employee_code']) ?></td>
                                    <td><?= h($p['first_name'] . ' ' . $p['last_name']) ?></td>
                                    <td><?= h($p['department']) ?></td>
                                    <td><?= number_format($p['basic_salary'], 2) ?></td>
                                    <td><?= number_format($p['allowances'], 2) ?></td>
                                    <td><?= number_format($p['deductions'], 2) ?></td>
                                    <td>
                                        <?= number_format($p['overtime_hours'], 2) ?> ชม.<br>
                                        <small>(<?= number_format($p['overtime_pay'], 2) ?>฿)</small>
                                    </td>
                                    <td><strong><?= number_format($p['total_salary'], 2) ?> ฿</strong></td>
                                    <td>
                                        <?php if ($p['payment_status'] === 'paid'): ?>
                                            <span class="badge bg-success">จ่ายแล้ว</span><br>
                                            <small><?= h($p['payment_date']) ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-warning">ยังไม่จ่าย</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['payment_status'] === 'pending'): ?>
                                            <a href="payroll_mark_paid.php?id=<?= $p['id'] ?>" 
                                               class="btn btn-sm btn-success"
                                               onclick="return confirm('ยืนยันว่าจ่ายเงินแล้ว?')">
                                                ทำเครื่องหมายจ่ายแล้ว
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>