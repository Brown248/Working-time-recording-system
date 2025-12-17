<?php
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

if (!isset($_GET['id'])) {
    redirect('../admin/dashboard.php');
}

$id = intval($_GET['id']);

$stmt = $pdo->prepare("UPDATE leave_requests SET status = 'rejected' WHERE id = :id");
$stmt->execute(['id' => $id]);

setFlashMessage('danger', 'ปฏิเสธคำขอลาแล้ว');

// กลับไป Dashboard
redirect('../admin/dashboard.php');
