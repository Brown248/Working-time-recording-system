<?php
/**
 * admin/employees.php - จัดการข้อมูลพนักงาน
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

// ดึงรายการพนักงานทั้งหมด
try {
    $stmt = $pdo->query("
        SELECT e.*, u.username 
        FROM employees e 
        LEFT JOIN users u ON e.id = u.employee_id 
        ORDER BY e.employee_code ASC
    ");
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $employees = [];
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>จัดการพนักงาน</h2>
        </div>
        <div class="col text-end">
            <a href="employee_create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> เพิ่มพนักงาน
            </a>
        </div>
    </div>
    
    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
        <div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show">
            <?= h($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อ-สกุล</th>
                            <th>อีเมล</th>
                            <th>แผนก</th>
                            <th>ตำแหน่ง</th>
                            <th>เงินเดือน</th>
                            <th>สถานะ</th>
                            <th>Username</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr><td colspan="9" class="text-center">ไม่มีข้อมูลพนักงาน</td></tr>
                        <?php else: ?>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?= h($emp['employee_code']) ?></td>
                                    <td><?= h($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                                    <td><?= h($emp['email']) ?></td>
                                    <td><?= h($emp['department']) ?></td>
                                    <td><?= h($emp['position']) ?></td>
                                    <td><?= number_format($emp['salary'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $emp['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= h($emp['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= h($emp['username'] ?? '-') ?></td>
                                    <td>
                                        <a href="employee_edit.php?id=<?= $emp['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                                        <a href="employee_delete.php?id=<?= $emp['id'] ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบพนักงานคนนี้?')">ลบ</a>
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