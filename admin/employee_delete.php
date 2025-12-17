<?php
/**
 * admin/employee_delete.php - ลบพนักงาน
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

// รับ employee ID
$employeeId = (int)($_GET['id'] ?? 0);

if ($employeeId <= 0) {
    setFlashMessage('danger', 'ไม่พบข้อมูลพนักงาน');
    redirect('employees.php');
}

try {
    // ตรวจสอบว่ามีพนักงานคนนี้จริง
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id");
    $stmt->execute(['id' => $employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        setFlashMessage('danger', 'ไม่พบข้อมูลพนักงาน');
        redirect('employees.php');
    }
    
    // ตรวจสอบว่ามี User Account เชื่อมโยงหรือไม่
    $stmt = $pdo->prepare("SELECT id FROM users WHERE employee_id = :id");
    $stmt->execute(['id' => $employeeId]);
    $hasUserAccount = $stmt->fetch();
    
    // เริ่ม Transaction
    $pdo->beginTransaction();
    
    // ถ้ามี User Account ให้ลบก่อน (หรือ SET NULL)
    if ($hasUserAccount) {
        // ลบ User Account ที่เชื่อมโยง
        $stmt = $pdo->prepare("DELETE FROM users WHERE employee_id = :id");
        $stmt->execute(['id' => $employeeId]);
    }
    
    // ลบพนักงาน (ข้อมูลที่เกี่ยวข้องจะถูกลบอัตโนมัติเพราะ ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = :id");
    $stmt->execute(['id' => $employeeId]);
    
    // Commit Transaction
    $pdo->commit();
    
    setFlashMessage('success', 'ลบพนักงาน ' . h($employee['first_name'] . ' ' . $employee['last_name']) . ' สำเร็จ');
    redirect('employees.php');
    
} catch (PDOException $e) {
    // Rollback หากเกิดข้อผิดพลาด
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log($e->getMessage());
    setFlashMessage('danger', 'เกิดข้อผิดพลาดในการลบพนักงาน');
    redirect('employees.php');
}
?>