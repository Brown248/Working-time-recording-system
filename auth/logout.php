<?php
/**
 * auth/logout.php - Logout และลบ session
 */
define('APP_ACCESS', true);
require_once '../config.php';

// ลบ session token จาก database
if (isset($_SESSION['session_token'])) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_token = :token");
        $stmt->execute(['token' => $_SESSION['session_token']]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// ลบ session ทั้งหมด
session_unset();
session_destroy();

// เริ่ม session ใหม่เพื่อแสดง flash message
session_start();
setFlashMessage('success', 'ออกจากระบบสำเร็จ');

// Redirect กลับไปหน้า login แบบ absolute URL
redirect(APP_URL . '/auth/login.php');
?>
