<?php
/**
 * admin/leave_requests.php - รายการคำขอลาทั้งหมด
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

// Filter
$status = $_GET['status'] ?? 'all';

try {
    $sql = "
        SELECT 
            lr.*,
            e.employee_code,
            e.first_name,
            e.last_name,
            e.department,
            u.username as approved_by_username
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        LEFT JOIN users u ON lr.approved_by = u.id
    ";
    
    if ($status !== 'all') {
        $sql .= " WHERE lr.status = :status";
    }
    
    $sql .= " ORDER BY lr.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($status !== 'all') {
        $stmt->execute(['status' => $status]);
    } else {
        $stmt->execute();
    }
    
    $leaves = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $leaves = [];
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>คำขอลาทั้งหมด</h2>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="leave_requests.php?status=all" 
                   class="btn btn-<?= $status === 'all' ? 'primary' : 'outline-primary' ?>">
                    ทั้งหมด
                </a>
                <a href="leave_requests.php?status=pending" 
                   class="btn btn-<?= $status === 'pending' ? 'warning' : 'outline-warning' ?>">
                    รออนุมัติ
                </a>
                <a href="leave_requests.php?status=approved" 
                   class="btn btn-<?= $status === 'approved' ? 'success' : 'outline-success' ?>">
                    อนุมัติแล้ว
                </a>
                <a href="leave_requests.php?status=rejected" 
                   class="btn btn-<?= $status === 'rejected' ? 'danger' : 'outline-danger' ?>">
                    ปฏิเสธ
                </a>
            </div>
        </div>
    </div>
    
    <!-- ตาราง Leave Requests -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อ-สกุล</th>
                            <th>แผนก</th>
                            <th>ประเภท</th>
                            <th>วันที่ลา</th>
                            <th>จำนวน</th>
                            <th>เหตุผล</th>
                            <th>สถานะ</th>
                            <th>อนุมัติโดย</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leaves)): ?>
                            <tr><td colspan="10" class="text-center">ไม่มีข้อมูลคำขอลา</td></tr>
                        <?php else: ?>
                            <?php foreach ($leaves as $leave): ?>
                                <tr>
                                    <td><?= h($leave['employee_code']) ?></td>
                                    <td><?= h($leave['first_name'] . ' ' . $leave['last_name']) ?></td>
                                    <td><?= h($leave['department']) ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= h($leave['leave_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= h($leave['start_date']) ?><br>
                                        <small>ถึง <?= h($leave['end_date']) ?></small>
                                    </td>
                                    <td><?= h($leave['days']) ?> วัน</td>
                                    <td>
                                        <small><?= h(substr($leave['reason'], 0, 50)) ?><?= strlen($leave['reason']) > 50 ? '...' : '' ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger'
                                        ][$leave['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= h($leave['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($leave['approved_by']): ?>
                                            <?= h($leave['approved_by_username']) ?><br>
                                            <small><?= h($leave['approved_at']) ?></small>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($leave['status'] === 'pending'): ?>
                                            <a href="leave_approve.php?id=<?= $leave['id'] ?>&action=approve" 
                                               class="btn btn-sm btn-success"
                                               onclick="return confirm('ยืนยันการอนุมัติ?')">
                                                <i class="bi bi-check-circle"></i> อนุมัติ
                                            </a>
                                            <a href="leave_approve.php?id=<?= $leave['id'] ?>&action=reject" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('ยืนยันการปฏิเสธ?')">
                                                <i class="bi bi-x-circle"></i> ปฏิเสธ
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">ดำเนินการแล้ว</span>
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