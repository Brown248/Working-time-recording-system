<?php
if (!defined('APP_ACCESS')) die('Direct access not permitted');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
        }
        .sidebar a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #34495e;
            border-left: 4px solid #3498db;
        }
        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="p-3 text-center">
                    <h4 class="text-white"><?= APP_NAME ?></h4>
                    <hr class="bg-light">
                    <div class="text-white-50 small mb-3">
                        <?= h($_SESSION['full_name'] ?: $_SESSION['username']) ?><br>
                        <span class="badge bg-primary"><?= h($_SESSION['role']) ?></span>
                    </div>
                </div>
                
                <nav>
                    <?php if (isAdmin()): ?>
                        <a href="../admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="../admin/employees.php">
                            <i class="bi bi-people"></i> จัดการพนักงาน
                        </a>
                        <a href="../admin/attendance_list.php">
                            <i class="bi bi-calendar-check"></i> Attendance
                        </a>
                        <a href="../admin/leave_requests.php">
                            <i class="bi bi-envelope-paper"></i> คำขอลา
                        </a>
                        <a href="../admin/payroll_list.php">
                            <i class="bi bi-cash-stack"></i> Payroll
                        </a>
                        <hr class="bg-secondary">
                    <?php else: ?>
                        <a href="../employee/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="../employee/attendance.php">
                            <i class="bi bi-clock"></i> บันทึกเวลา
                        </a>
                        <a href="../employee/leave.php">
                            <i class="bi bi-envelope-paper"></i> ขอลา
                        </a>
                        <a href="../employee/payroll.php">
                            <i class="bi bi-cash"></i> เงินเดือนของฉัน
                        </a>
                        <hr class="bg-secondary">
                    <?php endif; ?>
                    
                    <a href="../auth/change_password.php">
                        <i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน
                    </a>
                    <a href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                    <div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show">
                        <?= h($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
