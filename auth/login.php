<?php
/**
 * auth/login.php - หน้า Login
 */
define('APP_ACCESS', true);
require_once '../config.php';

// ถ้า login แล้วให้ redirect ไป dashboard
if (isLoggedIn()) {
    redirect(isAdmin() ? '../admin/dashboard.php' : '../employee/dashboard.php');
}

$error = '';

// ตรวจสอบการ submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอก Username และ Password';
    } else {
        try {
            $pdo = getDB();

            // ค้นหาข้อมูลจากฐานข้อมูล
            $stmt = $pdo->prepare("
                SELECT u.*, e.first_name, e.last_name, e.employee_code
                FROM users u
                LEFT JOIN employees e ON u.employee_id = e.id
                WHERE u.username = :username
                LIMIT 1
            ");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            // ------------------------------
            // MODE: ไม่ใช้ hash ชั่วคราว
            // ------------------------------
            if ($user && $password === $user['password']) {

                // เก็บข้อมูลลง session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['full_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');

                // token session
                $sessionToken = bin2hex(random_bytes(32));
                $_SESSION['session_token'] = $sessionToken;

                // บันทึกลง DB
                $stmt = $pdo->prepare("
                    INSERT INTO sessions (user_id, session_token, ip_address, user_agent)
                    VALUES (:user_id, :token, :ip, :agent)
                ");
                $stmt->execute([
                    'user_id' => $user['id'],
                    'token' => $sessionToken,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);

                // Redirect
                setFlashMessage('success', 'เข้าสู่ระบบสำเร็จ');
                redirect($user['role'] === 'admin' ? '../admin/dashboard.php' : '../employee/dashboard.php');

            } else {
                $error = 'Username หรือ Password ไม่ถูกต้อง';
            }

        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 400px; width: 100%; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card p-5">
            <h2 class="text-center mb-4">เข้าสู่ระบบ</h2>
            <p class="text-center text-muted mb-4"><?= APP_NAME ?></p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= h($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" 
                        value="<?= h($_POST['username'] ?? '') ?>" required autofocus>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
            </form>

            <div class="mt-4 text-center text-muted small">
                ทดสอบระบบ:<br>
                Admin → <code>admin / admin123</code><br>
                Employee → <code>somchai / password123</code><br>
            </div>
        </div>
    </div>
</body>
</html>
