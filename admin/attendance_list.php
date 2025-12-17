<?php
/**
 * admin/attendance_list.php - รายการบันทึกเวลาเข้าออก
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

// ดึงข้อมูล Attendance ทั้งหมด
try {
    $stmt = $pdo->prepare("
        SELECT a.*, e.employee_code, e.first_name, e.last_name, e.department
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id
        ORDER BY a.date DESC, a.clock_in ASC
    ");
    $stmt->execute();
    $attendances = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h3 class="mb-4">รายการ Attendance ทั้งหมด</h3>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>วันที่</th>
                <th>รหัสพนักงาน</th>
                <th>ชื่อ-สกุล</th>
                <th>แผนก</th>
                <th>เวลาเข้า</th>
                <th>เวลาออก</th>
                <th>ชั่วโมงงาน</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($attendances)): ?>
                <tr><td colspan="8" class="text-center">ยังไม่มีข้อมูล</td></tr>
            <?php else: ?>
                <?php foreach ($attendances as $row): ?>
                    <tr>
                        <td><?= h($row['date']) ?></td>
                        <td><?= h($row['employee_code']) ?></td>
                        <td><?= h($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= h($row['department']) ?></td>
                        <td><?= h($row['clock_in'] ?? '-') ?></td>
                        <td><?= h($row['clock_out'] ?? '-') ?></td>
                        <td><?= number_format($row['work_hours'] ?? 0, 2) ?></td>
                        <td>
                            <?php
                                $color = 'secondary';
                                if ($row['status'] == 'present') $color = 'success';
                                if ($row['status'] == 'late') $color = 'warning';
                                if ($row['status'] == 'absent') $color = 'danger';
                            ?>
                            <span class="badge bg-<?= $color ?>">
                                <?= h($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</div>

<?php include '../includes/footer.php'; ?>
