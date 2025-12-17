<?php
/**
 * admin/payroll_export_csv.php - Export Payroll เป็น CSV
 */
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

// รับพารามิเตอร์เดือนและปี
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));

try {
    // ดึงข้อมูล payroll
    $stmt = $pdo->prepare("
        SELECT 
            e.employee_code,
            e.first_name,
            e.last_name,
            e.department,
            e.position,
            p.basic_salary,
            p.allowances,
            p.deductions,
            p.overtime_hours,
            p.overtime_pay,
            p.total_salary,
            p.payment_status,
            p.payment_date
        FROM payroll p
        JOIN employees e ON p.employee_id = e.id
        WHERE p.month = :month AND p.year = :year
        ORDER BY e.employee_code
    ");
    $stmt->execute(['month' => $month, 'year' => $year]);
    $payrolls = $stmt->fetchAll();
    
    if (empty($payrolls)) {
        setFlashMessage('warning', 'ไม่มีข้อมูล Payroll สำหรับเดือนที่เลือก');
        redirect('payroll_list.php');
        exit;
    }
    
    // ตั้งค่า header สำหรับ CSV
    $filename = "payroll_" . $year . "_" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // เปิด output stream
    $output = fopen('php://output', 'w');
    
    // เขียน BOM สำหรับ UTF-8 (ให้ Excel เปิดได้ถูกต้อง)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // เขียน Header
    fputcsv($output, [
        'รหัสพนักงาน',
        'ชื่อ',
        'นามสกุล',
        'แผนก',
        'ตำแหน่ง',
        'เงินเดือนพื้นฐาน',
        'เบี้ยเลี้ยง',
        'หักเงิน',
        'ชั่วโมง OT',
        'ค่า OT',
        'เงินเดือนรวม',
        'สถานะการจ่าย',
        'วันที่จ่าย'
    ]);
    
    // เขียนข้อมูล
    foreach ($payrolls as $p) {
        fputcsv($output, [
            $p['employee_code'],
            $p['first_name'],
            $p['last_name'],
            $p['department'],
            $p['position'],
            number_format($p['basic_salary'], 2, '.', ''),
            number_format($p['allowances'], 2, '.', ''),
            number_format($p['deductions'], 2, '.', ''),
            number_format($p['overtime_hours'], 2, '.', ''),
            number_format($p['overtime_pay'], 2, '.', ''),
            number_format($p['total_salary'], 2, '.', ''),
            $p['payment_status'] === 'paid' ? 'จ่ายแล้ว' : 'ยังไม่จ่าย',
            $p['payment_date'] ?? '-'
        ]);
    }
    
    // เขียน Summary
    fputcsv($output, []); // บรรทัดว่าง
    
    $totalBasic = array_sum(array_column($payrolls, 'basic_salary'));
    $totalOT = array_sum(array_column($payrolls, 'overtime_pay'));
    $totalSalary = array_sum(array_column($payrolls, 'total_salary'));
    
    fputcsv($output, ['', '', '', '', 'รวมทั้งหมด:', 
        number_format($totalBasic, 2, '.', ''), '', '', '', 
        number_format($totalOT, 2, '.', ''), 
        number_format($totalSalary, 2, '.', '')
    ]);
    
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    setFlashMessage('danger', 'เกิดข้อผิดพลาดในการ Export');
    redirect('payroll_list.php');
}
?>