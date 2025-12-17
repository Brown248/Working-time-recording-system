<?php
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT lr.*, e.first_name, e.last_name, e.employee_code
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    WHERE lr.status = 'pending'
    ORDER BY lr.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <h2>คำขอลารออนุมัติ</h2>
    <hr>

    <?php if (empty($requests)): ?>
        <p class="text-muted">ไม่มีคำขอลาที่รออนุมัติ</p>
    <?php else: ?>
        <?php foreach ($requests as $req): ?>
            <div class="mb-3 p-3 border rounded">

                <strong><?= h($req['first_name'] . " " . $req['last_name']) ?></strong>
                <small class="text-muted">(<?= h($req['employee_code']) ?>)</small>
                <br>

                <small>ประเภท: <?= h($req['leave_type']) ?></small><br>
                <small>วันที่: <?= h($req['start_date']) ?> ถึง <?= h($req['end_date']) ?></small><br>
                <small>จำนวน: <?= h($req['days']) ?> วัน</small>

                <div class="mt-2">
                    <a href="leave_approve.php?id=<?= $req['id'] ?>" 
                       class="btn btn-success btn-sm">อนุมัติ</a>

                    <a href="leave_reject.php?id=<?= $req['id'] ?>" 
                       class="btn btn-danger btn-sm">ปฏิเสธ</a>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
