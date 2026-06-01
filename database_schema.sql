-- =============================================
-- ANONYMOUS WHISTLEBLOWER PLATFORM - MySQL Schema
-- =============================================

-- Create database (optional - adjust as needed)
CREATE DATABASE IF NOT EXISTS whistleblower
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE whistleblower;

-- =============================================
-- 1. CATEGORIES TABLE (reference/lookup)
-- =============================================
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Corruption', 'Bribery, kickbacks, embezzlement'),
('Fraud', 'Financial fraud, accounting irregularities'),
('Harassment', 'Workplace harassment, discrimination, Sexual harassment'),
('Safety Violations', 'Workplace safety, Environmental hazards'),
('Data Breach', 'Unauthorized data access, leaks'),
('Other', 'Other misconduct not covered above');

-- =============================================
-- 2. REPORTS TABLE (main whistleblower reports)
-- =============================================
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anonymous_session_id VARCHAR(200) NOT NULL,
    tracking_code VARCHAR(50) UNIQUE NOT NULL,
    category_id INT NOT NULL,
    encrypted_description TEXT NOT NULL,
    status ENUM('new', 'investigating', 'resolved', 'closed') DEFAULT 'new',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    visibility ENUM('private', 'public') DEFAULT 'private',
    publication_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    view_once_enabled BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_anonymous_session_id (anonymous_session_id),
    INDEX idx_tracking_code (tracking_code),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at),
    INDEX idx_session_status (anonymous_session_id, status)
    INDEX idx_public_reports (visibility, publication_status, status);
);

-- =============================================
-- 3. EVIDENCES TABLE (file attachments)
-- =============================================
CREATE TABLE evidences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id int NOT NULL,
    encrypted_file_path TEXT NOT NULL,
    encrypted_name TEXT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    view_count INT UNSIGNED DEFAULT 0,
    max_views INT UNSIGNED NULL,
    is_public BOOLEAN DEFAULT FALSE
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_max_views (max_views),
    INDEX idx_auto_destruct (expires_at, view_count, max_views)
    INDEX idx_public_evidence (is_public, expires_at);
);

-- =============================================
-- 4. ACCESS TOKENS TABLE (one-time access for secure viewing)
-- =============================================
CREATE TABLE access_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(200) UNIQUE NOT NULL,
    type ENUM('view_evidence', 'view_message', 'delete_report', 'one_time_access') NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_unused (token, used_at, expires_at)
);

-- =============================================
-- 5. MESSAGES TABLE (real-time chat between whistleblower and admin)
-- =============================================
CREATE TABLE messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    sender_type ENUM('whistleblower', 'admin') NOT NULL,
    encrypted_message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_delivered BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id),
    INDEX idx_sender_type (sender_type),
    INDEX idx_unread (report_id, is_read),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at),
    INDEX idx_cleanup (expires_at, created_at)
);

-- =============================================
-- 6. MESSAGE ATTACHMENTS TABLE (links files to messages)
-- =============================================
CREATE TABLE message_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id BIGINT UNSIGNED NOT NULL,
    evidence_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (evidence_id) REFERENCES evidences(id) ON DELETE SET NULL,
    INDEX idx_message_id (message_id),
    INDEX idx_evidence_id (evidence_id)
);

-- =============================================
-- 7. AUDIT LOGS TABLE (compliance and security monitoring)
-- =============================================
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NULL,
    admin_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    ip_hash VARCHAR(64) NOT NULL,
    user_agent_hash VARCHAR(64) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE SET NULL,
    -- Foreign key to Laravel users table will be added after users table exists
    INDEX idx_report_id (report_id),
    INDEX idx_admin_user_id (admin_user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_hash (ip_hash)
);

-- =============================================
-- 8. SESSIONS TABLE (for anonymous session management)
-- =============================================
CREATE TABLE anonymous_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(200) UNIQUE NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_hash VARCHAR(64) NOT NULL,
    user_agent_hash VARCHAR(64) NOT NULL,
    report_count INT UNSIGNED DEFAULT 0,
    is_blocked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_session_id (session_id),
    INDEX idx_last_activity (last_activity),
    INDEX idx_is_blocked (is_blocked),
    INDEX idx_ip_hash (ip_hash)
);

-- =============================================
-- 9. USERS TABLE (admins only)
-- =============================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(200) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(200) NOT NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    role ENUM('admin', 'super_admin', 'viewer') DEFAULT 'admin',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_is_active (is_active),
    INDEX idx_role (role)
);

-- Add foreign key to audit_logs now that users table exists
ALTER TABLE audit_logs
ADD CONSTRAINT fk_audit_logs_admin_user_id
FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- =============================================
-- 10. PASSWORD RESETS
-- =============================================
CREATE TABLE password_reset_tokens (
    email VARCHAR(200) PRIMARY KEY,
    token VARCHAR(200) NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_email (email)
);

-- =============================================
-- 11. FAILED JOBS (for background processing)
-- =============================================
CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(200) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_uuid (uuid)
);

-- =============================================
-- 12. JOBS TABLE (queue management)
-- =============================================
CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(200) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    
    INDEX idx_queue (queue)
);

-- =============================================
-- 13. NOTIFICATIONS TABLE
-- =============================================
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    type VARCHAR(200) NOT NULL,
    notifiable_type VARCHAR(200) NOT NULL,
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data TEXT NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_notifiable (notifiable_type, notifiable_id)
);

-- =============================================
-- 14. SESSIONS TABLE
-- =============================================
CREATE TABLE sessions (
    id VARCHAR(200) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
);

-- =============================================
-- 15. CACHE TABLE
-- =============================================
CREATE TABLE cache (
    cache_key VARCHAR(200) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL,
    
    INDEX idx_expiration (expiration)
);

CREATE TABLE cache_locks (
    cache_locks_key VARCHAR(200) PRIMARY KEY,
    owner VARCHAR(200) NOT NULL,
    expiration INT NOT NULL
);

-- =============================================
-- OPTIONAL: STORED PROCEDURES FOR CLEANUP
-- =============================================

DELIMITER $$

-- Auto-clean expired data
CREATE PROCEDURE CleanupExpiredData()
BEGIN
    -- Delete expired evidences
    DELETE FROM evidences 
    WHERE expires_at IS NOT NULL 
      AND expires_at < NOW();
    
    -- Delete evidences that exceeded view limits
    DELETE FROM evidences 
    WHERE max_views IS NOT NULL 
      AND view_count >= max_views;
    
    -- Delete expired messages
    DELETE FROM messages 
    WHERE expires_at IS NOT NULL 
      AND expires_at < NOW();
    
    -- Mark expired reports as closed
    UPDATE reports 
    SET status = 'closed' 
    WHERE expires_at IS NOT NULL 
      AND expires_at < NOW() 
      AND status != 'closed';
    
    -- Delete expired access tokens
    DELETE FROM access_tokens 
    WHERE expires_at < NOW() 
       OR (used_at IS NOT NULL AND used_at < DATE_SUB(NOW(), INTERVAL 1 DAY));
    
    -- Clean old audit logs (keep for 1 year)
    DELETE FROM audit_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
END$$

-- Get report statistics for admin dashboard
CREATE PROCEDURE GetReportStats()
BEGIN
    SELECT 
        COUNT(*) as total_reports,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_reports,
        SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
        SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_priority,
        AVG(TIMESTAMPDIFF(HOUR, created_at, 
            CASE WHEN status IN ('resolved', 'closed') THEN updated_at ELSE NOW() END
        )) as avg_response_hours
    FROM reports;
END$$

DELIMITER ;

-- Insert a default admin user (password: Admin@123)
-- The password hash is for 'Admin@123'
INSERT INTO users (name, email, password, role, is_active, created_at) 
VALUES (
    'Kemgang Wilfried',
    'kemzywil@gmail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'super_admin',
    1,
    NOW()
);

-- To create additional admin users with a specific password, use:
-- Password hash generated with password_hash('YourPassword123', PASSWORD_DEFAULT)

-- Create user_settings table for notification preferences
CREATE TABLE IF NOT EXISTS user_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_enabled BOOLEAN DEFAULT TRUE,
    push_notification_token TEXT NULL,
    device_type ENUM('web', 'android', 'ios') DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    UNIQUE KEY uk_user_device (user_id, device_type)
);