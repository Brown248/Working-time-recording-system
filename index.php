<?php
/**
 * index.php - หน้าแรกของระบบ
 * Redirect ไปหน้า Login หรือ Dashboard ตาม session
 */
define('APP_ACCESS', true);
require_once 'config.php';

// ถ้า login แล้วให้ไปหน้า dashboard ตาม role
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('employee/dashboard.php');
    }
}

// ถ้ายังไม่ login ให้ไปหน้า login
redirect('auth/login.php');
?>