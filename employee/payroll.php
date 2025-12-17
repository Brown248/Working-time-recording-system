<?php
/**
 * employee/payroll.php - แสดงประวัติเงินเดือนของพนักงาน
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$pdo = getDB();
$employeeId = $_SESSION['employee_id'];

// ดึงข้อมูล payroll ทั้งหมดของพนักงาน
$stmt = $pdo->prepare("
    SELECT *
    FROM payroll
    WHERE employee_id = :id
    ORDER BY year DESC, month DESC
");
$stmt->execute(['id' => $employeeId]);
$payrolls = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">ประวัติเงินเดือนของฉัน</h2>

    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">ข้อมูลเงินเดือน</h5>
        </div>

        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>เดือน</th>
                        <th>เงินเดือนพื้นฐาน</th>
                        <th>ค่าอื่นๆ</th>
                        <th>หักเงิน</th>
                        <th>OT (ชั่วโมง)</th>
                        <th>ค่าล่วงเวลา</th>
                        <th>รวมสุทธิ</th>
                        <th>สถานะ</th>
                        <th>วันที่จ่าย</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$payrolls): ?>
                        <tr>
                            <td colspan="9" class="text-center py-3 text-muted">
                                ยังไม่มีประวัติเงินเดือน
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payrolls as $p): ?>
                            <tr>
                                <td><?= h($p['month']) ?>/<?= h($p['year']) ?></td>
                                <td><?= number_format($p['basic_salary'], 2) ?></td>
                                <td><?= number_format($p['allowances'], 2) ?></td>
                                <td><?= number_format($p['deductions'], 2) ?></td>
                                <td><?= number_format($p['overtime_hours'], 2) ?></td>
                                <td><?= number_format($p['overtime_pay'], 2) ?></td>
                                <td><strong><?= number_format($p['total_salary'], 2) ?></strong></td>

                                <?php 
                                    $status = $p['payment_status'];
                                    $badge = [
                                        'pending' => 'warning',
                                        'paid' => 'success'
                                    ][$status];
                                ?>
                                <td>
                                    <span class="badge bg-<?= $badge ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>

                                <td><?= h($p['payment_date'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
