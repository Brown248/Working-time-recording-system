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
    // ตรวจสอบว่ามีข้อมูลวันนี้แล้วหรือยัง
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :id AND date = :date");
    $stmt->execute(['id' => $employeeId, 'date' => $today]);
    $record = $stmt->fetch();

    if ($record) {

        if ($record['clock_in'] && !$record['clock_out']) {
            $response['message'] = 'คุณ Clock In ไปแล้ววันนี้';
        
        } elseif ($record['clock_in'] && $record['clock_out']) {
            $response['message'] = 'คุณได้ทำการ Clock In และ Clock Out แล้ววันนี้';

        } else {
            // Update clock_in
            $lateTime = '08:30:00';
            $status = ($now > $lateTime) ? 'late' : 'present';

            $stmt = $pdo->prepare("
                UPDATE attendance
                SET clock_in = :in_time, status = :status
                WHERE id = :id
            ");
            $stmt->execute([
                'in_time' => $now,
                'status' => $status,
                'id' => $record['id']
            ]);

            $response['success'] = true;
            $response['message'] = "Clock In สำเร็จ เวลา $now";
        }

    } else {
        // บันทึกข้อมูลใหม่
        $lateTime = '08:30:00';
        $status = ($now > $lateTime) ? 'late' : 'present';

        $stmt = $pdo->prepare("
            INSERT INTO attendance (employee_id, date, clock_in, status)
            VALUES (:id, :date, :in_time, :status)
        ");
        $stmt->execute([
            'id' => $employeeId,
            'date' => $today,
            'in_time' => $now,
            'status' => $status
        ]);

        $response['success'] = true;
        $response['message'] = "Clock In สำเร็จ เวลา $now";
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    $response['message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
}

// ไม่ใช่ AJAX → Redirect
setFlashMessage($response['success'] ? 'success' : 'danger', $response['message']);
redirect('/employee/attendance.php');
