<?php
/**
 * employee/attendance_history.php
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$pdo = getDB();
$employeeId = $_SESSION['employee_id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = :id
    ORDER BY date DESC
");
$stmt->execute(['id' => $employeeId]);
$records = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">ประวัติการเข้างาน</h2>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">ตารางประวัติการเข้า–ออกงาน</h5>
        </div>

        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>วันที่</th>
                        <th>เวลาเข้า</th>
                        <th>เวลาออก</th>
                        <th>ชั่วโมงทำงาน</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$records): ?>
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">
                                ยังไม่มีข้อมูลการบันทึกเวลา
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td><?= h($row['date']) ?></td>
                                <td><?= h($row['clock_in'] ?: '-') ?></td>
                                <td><?= h($row['clock_out'] ?: '-') ?></td>
                                <td><?= number_format($row['work_hours'] ?: 0, 2) ?></td>
                                <td>
                                    <?php
                                        $status = $row['status'];
                                        $badge = [
                                            'present' => 'success',
                                            'late'    => 'warning',
                                            'absent'  => 'danger'
                                        ][$status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badge ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
