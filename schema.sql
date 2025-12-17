CREATE DATABASE IF NOT EXISTS company_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE company_system;

-- ตาราง users (สำหรับ login)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    employee_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง employees (ข้อมูลพนักงาน)
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    position VARCHAR(100),
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_code (employee_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง attendance (บันทึกเวลาเข้า-ออก)
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    clock_in TIME NULL,
    clock_out TIME NULL,
    work_hours DECIMAL(4,2) DEFAULT 0.00,
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (employee_id, date),
    INDEX idx_date (date),
    INDEX idx_employee_date (employee_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง leave_requests (คำขอลา)
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type ENUM('sick', 'personal', 'vacation', 'other') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days INT NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee (employee_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง payroll (เงินเดือน)
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    allowances DECIMAL(10,2) DEFAULT 0.00,
    deductions DECIMAL(10,2) DEFAULT 0.00,
    overtime_hours DECIMAL(5,2) DEFAULT 0.00,
    overtime_pay DECIMAL(10,2) DEFAULT 0.00,
    total_salary DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    payment_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_payroll (employee_id, month, year),
    INDEX idx_month_year (month, year),
    INDEX idx_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง sessions (สำหรับจัดการ session)
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED DATA
-- ============================================

-- สร้าง Admin User
-- รหัสผ่าน: admin123 (ต้อง hash ก่อนใช้งานจริง)
-- ใช้คำสั่ง: php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
INSERT INTO users (username, password, role, employee_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL);

-- สร้างพนักงานตัวอย่าง
INSERT INTO employees (employee_code, first_name, last_name, email, phone, department, position, hire_date, salary, status) VALUES
('EMP001', 'สมชาย', 'ใจดี', 'somchai@company.com', '0812345678', 'IT', 'Developer', '2024-01-15', 35000.00, 'active'),
('EMP002', 'สมหญิง', 'สวยงาม', 'somying@company.com', '0823456789', 'HR', 'HR Manager', '2024-02-01', 40000.00, 'active'),
('EMP003', 'ประยุทธ', 'ทำงาน', 'prayut@company.com', '0834567890', 'Sales', 'Sales Executive', '2024-03-10', 30000.00, 'active');

-- สร้าง User accounts สำหรับพนักงาน
-- รหัสผ่าน: password123
INSERT INTO users (username, password, role, employee_id) VALUES
('somchai', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1),
('somying', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 2),
('prayut', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 3);

-- ตัวอย่างข้อมูล attendance
INSERT INTO attendance (employee_id, date, clock_in, clock_out, work_hours, status) VALUES
(1, '2024-11-15', '08:00:00', '17:00:00', 8.00, 'present'),
(1, '2024-11-16', '08:30:00', '17:30:00', 8.00, 'late'),
(2, '2024-11-15', '08:00:00', '17:00:00', 8.00, 'present'),
(3, '2024-11-15', '08:00:00', '17:00:00', 8.00, 'present');

-- ตัวอย่างคำขอลา
INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, days, reason, status) VALUES
(1, 'sick', '2024-11-20', '2024-11-20', 1, 'ป่วยเป็นไข้', 'pending'),
(2, 'vacation', '2024-12-01', '2024-12-03', 3, 'พักร้อน', 'approved');

-- ตัวอย่าง payroll
INSERT INTO payroll (employee_id, month, year, basic_salary, allowances, deductions, overtime_hours, overtime_pay, total_salary, payment_status) VALUES
(1, 10, 2024, 35000.00, 2000.00, 500.00, 5.00, 875.00, 37375.00, 'paid'),
(2, 10, 2024, 40000.00, 3000.00, 600.00, 0.00, 0.00, 42400.00, 'paid'),
(3, 10, 2024, 30000.00, 1500.00, 450.00, 10.00, 1875.00, 32925.00, 'pending');