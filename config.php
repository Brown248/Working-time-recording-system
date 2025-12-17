<?php
/**
 * config.php - FINAL for AwardSpace Hosting
 * - Fix redirect issues (convert to absolute URL)
 * - Fix session issues
 * - Ensure no BOM / no header output
 */

// -----------------------------
// FIX Session สำหรับ AwardSpace
// -----------------------------
ini_set('session.save_path', '/tmp');
session_save_path('/tmp');

// ป้องกันการเข้าถึงไฟล์โดยตรง
defined('APP_ACCESS') or define('APP_ACCESS', true);

// ตั้งค่า timezone
date_default_timezone_set('Asia/Bangkok');

// การตั้งค่าฐานข้อมูล (AwardSpace)
define('DB_HOST', 'fdb1034.awardspace.net');
define('DB_NAME', '4705518_aie313');
define('DB_USER', '4705518_aie313');
define('DB_PASS', 'AIE123456');
define('DB_CHARSET', 'utf8mb4');

// Session settings
define('SESSION_TIMEOUT', 1800); // 30 นาที
define('SESSION_NAME', 'COMPANY_SYS_SESSION');

// CSRF
define('CSRF_TOKEN_NAME', 'csrf_token');

// System Config (IMPORTANT: USE HTTPS)
define('APP_NAME', 'Company Management System');
define('APP_URL', 'https://aie313uftf.atwebpages.com');  // FULL URL

// Debug Mode
ini_set('display_errors', 1);
error_reporting(E_ALL);

// -----------------------------
// START SESSION (ก่อนมี output)
// -----------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

/**
 * ฟังก์ชันเชื่อมต่อฐานข้อมูลด้วย PDO
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

/**
 * Escape HTML (ป้องกัน XSS)
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Session Timeout Handler
 */
function initSession() {
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) 
    {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

/**
 * CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * ตรวจสอบ login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * ตรวจสอบ role admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect (สำคัญสุด)
 * - เปลี่ยน path เป็น absolute URL เพื่อป้องกัน AwardSpace redirect ออกนอกโฟลเดอร์
 */
function redirect($url) {

    // ถ้าเป็น path เช่น "../admin/dashboard.php"
    if (strpos($url, 'http') !== 0) {

        // ลบ ../ หรือ ./ และพวก root-relative
        $url = ltrim($url, '/');
        $url = str_replace('../', '', $url);
        $url = APP_URL . '/' . $url;
    }

    header("Location: $url");
    exit();
}

/**
 * Flash Message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $data = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $data;
    }
    return null;
}

/**
 * ต้อง Login ก่อนเข้าหน้านี้
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
}

/**
 * ต้องเป็น Admin เท่านั้น
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('danger', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        redirect('index.php');
    }
}

// เริ่ม Session Timeout Handler
initSession();
?>
