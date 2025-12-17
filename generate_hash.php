<?php
/**
 * generate_hash.php - สร้าง Password Hash สำหรับใช้ใน schema.sql
 * 
 * วิธีใช้:
 * 1. เปิดไฟล์นี้ในเบราว์เซอร์: http://localhost/company-system/generate_hash.php
 * 2. กรอกรหัสผ่านที่ต้องการ
 * 3. คัดลอก hash ไปใช้ใน schema.sql
 * 
 * หรือรันในคอมมานด์ไลน์:
 * php generate_hash.php your_password
 */

// ถ้ารันจาก command line
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Usage: php generate_hash.php <password>\n";
        echo "Example: php generate_hash.php admin123\n";
        exit(1);
    }
    
    $password = $argv[1];
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    echo "Password: $password\n";
    echo "Hash: $hash\n";
    exit(0);
}

// ถ้าเปิดผ่าน browser
$hash = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 50px 0; }
        .container { max-width: 600px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Password Hash Generator</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    ใช้เครื่องมือนี้สำหรับสร้าง BCRYPT hash ของรหัสผ่าน
                    เพื่อนำไปใช้ใน <code>schema.sql</code>
                </p>
                
                <form method="POST" action="generate_hash.php">
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="text" class="form-control" id="password" name="password" 
                               value="<?= htmlspecialchars($password) ?>" 
                               placeholder="กรอกรหัสผ่านที่ต้องการ" required>
                        <small class="text-muted">ควรมีอย่างน้อย 6 ตัวอักษร</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">สร้าง Hash</button>
                </form>
                
                <?php if (!empty($hash)): ?>
                    <div class="mt-4">
                        <div class="alert alert-success">
                            <strong>สำเร็จ!</strong> Hash ถูกสร้างแล้ว
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password:</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($password) ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hash (คัดลอกไปใช้):</label>
                            <textarea class="form-control" rows="3" readonly><?= htmlspecialchars($hash) ?></textarea>
                        </div>
                        
                        <button class="btn btn-success" onclick="copyHash()">
                            <i class="bi bi-clipboard"></i> คัดลอก Hash
                        </button>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <strong>วิธีใช้:</strong>
                            <ol class="mb-0">
                                <li>คัดลอก Hash ด้านบน</li>
                                <li>เปิดไฟล์ <code>schema.sql</code></li>
                                <li>แทนที่ hash เดิมในคำสั่ง INSERT INTO users</li>
                                <li>บันทึกและ Import เข้า phpMyAdmin</li>
                            </ol>
                        </div>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="alert alert-warning">
                    <strong>คำเตือน:</strong>
                    <ul class="mb-0">
                        <li>ลบไฟล์นี้ออกหลังจากนำไปใช้แล้ว (เพื่อความปลอดภัย)</li>
                        <li>ไม่ควรใช้รหัสผ่านง่ายๆ ในสภาพแวดล้อมจริง</li>
                        <li>Hash ทุกครั้งจะไม่เหมือนกัน (แม้รหัสผ่านเดียวกัน)</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="auth/login.php" class="btn btn-link">กลับไปหน้า Login</a>
        </div>
    </div>
    
    <script>
        function copyHash() {
            const textarea = document.querySelector('textarea');
            textarea.select();
            document.execCommand('copy');
            alert('คัดลอก Hash สำเร็จ!');
        }
    </script>
</body>
</html>