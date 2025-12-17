<?php
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$pdo = getDB();
$employeeId = $_SESSION['employee_id'];

$today = date('Y-m-d');

// ดึงข้อมูลวันนี้
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :id AND date = :date");
$stmt->execute(['id' => $employeeId, 'date' => $today]);
$todayRecord = $stmt->fetch();

// ดึงประวัติย้อนหลัง
$stmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = :id
    ORDER BY date DESC
");
$stmt->execute(['id' => $employeeId]);
$history = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container mt-4">

    <h2 class="mb-4">บันทึกเวลาเข้า-ออกงาน</h2>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">สถานะวันนี้</h5>
        </div>
        <div class="card-body text-center">

            <p class="text-muted">วันที่: <strong><?= $today ?></strong></p>

            <form method="POST" action="/attendance/clockin.php" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <button type="submit"
                        class="btn btn-success btn-lg m-2"
                        <?= ($todayRecord && $todayRecord['clock_in']) ? 'disabled' : '' ?>>
                    Clock In
                </button>
            </form>

            <form method="POST" action="/attendance/clockout.php" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <button type="submit"
                        class="btn btn-danger btn-lg m-2"
                        <?= (!$todayRecord || !$todayRecord['clock_in'] || $todayRecord['clock_out']) ? 'disabled' : '' ?>>
                    Clock Out
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">ประวัติการบันทึกเวลา</h5>
        </div>

        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>วันที่</th>
                    <th>เข้า</th>
                    <th>ออก</th>
                    <th>ชั่วโมงทำงาน</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>

                <?php if (!$history): ?>
                    <tr><td colspan="5" class="text-center py-3 text-muted">ไม่มีประวัติ</td></tr>

                <?php else: ?>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= h($row['date']) ?></td>
                            <td><?= h($row['clock_in'] ?: '-') ?></td>
                            <td><?= h($row['clock_out'] ?: '-') ?></td>
                            <td><?= h($row['work_hours'] ?: '-') ?></td>
                            <td><?= h($row['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
