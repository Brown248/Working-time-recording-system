<?php
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$pdo = getDB();
$response = ['success' => false, 'message' => ''];

// ต้องเป็น POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// ตรวจสอบ CSRF
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $response['message'] = 'Invalid CSRF token';
    echo json_encode($response);
    exit;
}

// ต้องมี employee_id
if (empty($_SESSION['employee_id'])) {
    $response['message'] = 'ไม่พบข้อมูลพนักงาน';
    echo json_encode($response);
    exit;
}

$employeeId = $_SESSION['employee_id'];
$today = date('Y-m-d');
$now   = date('H:i:s');

try {
    // หาข้อมูล attendance วันนี้
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :id AND date = :date");
    $stmt->execute(['id' => $employeeId, 'date' => $today]);
    $record = $stmt->fetch();

    if (!$record || !$record['clock_in']) {
        $response['message'] = 'กรุณา Clock In ก่อน';
    
    } elseif ($record['clock_out']) {
        $response['message'] = 'คุณได้ Clock Out ไปแล้ววันนี้';

    } else {

        // คำนวณชั่วโมงทำงาน
        $clockIn  = new DateTime("$today {$record['clock_in']}");
        $clockOut = new DateTime("$today $now");

        $diff = $clockIn->diff($clockOut);
        $hours = round($diff->h + ($diff->i / 60), 2);

        // Update clock_out
        $stmt = $pdo->prepare("
            UPDATE attendance
            SET clock_out = :out_time, work_hours = :hours
            WHERE id = :id
        ");
        $stmt->execute([
            'out_time' => $now,
            'hours' => $hours,
            'id' => $record['id']
        ]);

        $response['success'] = true;
        $response['message'] = "Clock Out สำเร็จ เวลา $now (ทำงาน $hours ชั่วโมง)";
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    $response['message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
}

setFlashMessage($response['success'] ? 'success' : 'danger', $response['message']);
redirect('/employee/attendance.php');
