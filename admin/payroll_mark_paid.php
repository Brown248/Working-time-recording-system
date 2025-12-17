<?php
/**
 * admin/payroll_mark_paid.php - ทำเครื่องหมายว่าจ่ายเงินแล้ว
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

// รับ payroll ID
$payrollId = (int)($_GET['id'] ?? 0);

if ($payrollId <= 0) {
    setFlashMessage('danger', 'ไม่พบข้อมูล Payroll');
    redirect('payroll_list.php');
}

try {
    // ตรวจสอบว่ามี payroll นี้จริง
    $stmt = $pdo->prepare("SELECT * FROM payroll WHERE id = :id");
    $stmt->execute(['id' => $payrollId]);
    $payroll = $stmt->fetch();
    
    if (!$payroll) {
        setFlashMessage('danger', 'ไม่พบข้อมูล Payroll');
        redirect('payroll_list.php');
    }
    
    if ($payroll['payment_status'] === 'paid') {
        setFlashMessage('info', 'Payroll นี้ถูกทำเครื่องหมายจ่ายแล้วอยู่แล้ว');
        redirect('payroll_list.php?month=' . $payroll['month'] . '&year=' . $payroll['year']);
    }
    
    // Update สถานะเป็น paid
    $stmt = $pdo->prepare("
        UPDATE payroll 
        SET payment_status = 'paid', payment_date = CURDATE() 
        WHERE id = :id
    ");
    $stmt->execute(['id' => $payrollId]);
    
    setFlashMessage('success', 'ทำเครื่องหมายจ่ายเงินสำเร็จ');
    redirect('payroll_list.php?month=' . $payroll['month'] . '&year=' . $payroll['year']);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    setFlashMessage('danger', 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล');
    redirect('payroll_list.php');
}
?>