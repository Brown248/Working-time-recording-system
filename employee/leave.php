<?php
/**
 * employee/leave.php - รายการคำขอลาของพนักงาน
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$pdo = getDB();
$employeeId = $_SESSION['employee_id'];

// ดึงข้อมูลคำขอลาทั้งหมดของพนักงาน
$stmt = $pdo->prepare("
    SELECT *
    FROM leave_requests
    WHERE employee_id = :id
    ORDER BY created_at DESC
");
$stmt->execute(['id' => $employeeId]);
$leaves = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">รายการคำขอลาของฉัน</h2>

    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">ประวัติคำขอลา</h5>
        </div>

        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ประเภทการลา</th>
                        <th>วันที่เริ่ม</th>
                        <th>วันที่สิ้นสุด</th>
                        <th>จำนวน (วัน)</th>
                        <th>สถานะ</th>
                        <th>วันที่ส่งคำขอ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$leaves): ?>
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">
                                ยังไม่มีคำขอลา
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaves as $r): ?>
                            <tr>
                                <td><?= h($r['leave_type']) ?></td>
                                <td><?= h($r['start_date']) ?></td>
                                <td><?= h($r['end_date']) ?></td>
                                <td><?= h($r['days']) ?></td>
                                <td>
                                    <?php 
                                        $status = $r['status'];
                                        $badge = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger'
                                        ][$status];
                                    ?>
                                    <span class="badge bg-<?= $badge ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td><?= h($r['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <a href="/leave/request.php" class="btn btn-primary mt-3">
        <i class="bi bi-pl
