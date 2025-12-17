<?php
define('APP_ACCESS', true);
require_once '../config.php';
requireAdmin();

$pdo = getDB();

if (!isset($_GET['id'])) {
    redirect('../admin/dashboard.php');
}

$id = intval($_GET['id']);

$stmt = $pdo->prepare("UPDATE leave_requests SET status = 'approved' WHERE id = :id");
$stmt->execute(['id' => $id]);

setFlashMessage('success', 'อนุมัติคำขอลาเรียบร้อย');

// กลับไป Dashboard แทน leave_requests.php
redirect('../admin/dashboard.php');
