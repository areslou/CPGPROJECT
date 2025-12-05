-- ========================================================================
-- SCHEDULED EMAILS - DATABASE SETUP SCRIPT
-- ========================================================================
-- Run this script in your MySQL/PHPMyAdmin to set up the scheduled emails system
-- ========================================================================

-- 1. Create the ScheduledEmails table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS ScheduledEmails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    target_scholarship VARCHAR(255) NOT NULL,
    target_status VARCHAR(50) NOT NULL,
    scheduled_at DATETIME NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_status_scheduled (status, scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create a table for Email History (Sent/Failed/Pending)
CREATE TABLE IF NOT EXISTS EmailLogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body TEXT,
    target_group VARCHAR(100),
    status ENUM('sent', 'pending', 'failed', 'cancelled') NOT NULL,
    recipient_count INT DEFAULT 0,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_message TEXT
) ENGINE=InnoDB;

-- 3. Verify table structure
DESCRIBE ScheduledEmails;
DESCRIBE EmailLogs;

-- 4. Sample data insert (for testing - delete after testing)
-- Uncomment the lines below to insert test data:

/*
INSERT INTO ScheduledEmails 
(subject, body, target_scholarship, target_status, scheduled_at, status) 
VALUES 
(
    'Test Email - Immediate', 
    'Hello {name}, this is a test email that should send immediately.',
    'all',
    'all',
    NOW(),
    'pending'
),
(
    'Test Email - Future', 
    'Hello {name}, this is a test email scheduled for the future.',
    'all',
    'all',
    DATE_ADD(NOW(), INTERVAL 2 MINUTE),
    'pending'
);
*/

-- 5. Useful queries for monitoring

-- View all scheduled emails
SELECT * FROM ScheduledEmails ORDER BY scheduled_at DESC;

-- View pending emails only
SELECT * FROM ScheduledEmails WHERE status = 'pending' ORDER BY scheduled_at ASC;

-- View overdue pending emails (should have been sent already)
SELECT * FROM ScheduledEmails 
WHERE status = 'pending' 
AND scheduled_at <= NOW() 
ORDER BY scheduled_at ASC;

-- Count emails by status
SELECT status, COUNT(*) as count 
FROM ScheduledEmails 
GROUP BY status;

-- View emails scheduled for today
SELECT * FROM ScheduledEmails 
WHERE DATE(scheduled_at) = CURDATE() 
ORDER BY scheduled_at ASC;

-- View failed emails
SELECT * FROM ScheduledEmails 
WHERE status = 'failed' 
ORDER BY scheduled_at DESC;

-- 6. Maintenance queries

-- Delete old sent emails (older than 30 days)
-- CAREFUL: This permanently deletes records!
/*
DELETE FROM ScheduledEmails 
WHERE status = 'sent' 
AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
*/

-- Delete old failed emails (older than 7 days)
-- CAREFUL: This permanently deletes records!
/*
DELETE FROM ScheduledEmails 
WHERE status = 'failed' 
AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
*/

-- Reset a specific email to pending (if it failed but you want to retry)
-- Replace 1 with the actual ID
/*
UPDATE ScheduledEmails 
SET status = 'pending' 
WHERE id = 1;
*/

-- 7. Performance optimization (optional - for large datasets)

-- Add indexes if not already present
/*
ALTER TABLE ScheduledEmails ADD INDEX idx_created_at (created_at);
*/

-- ========================================================================
-- VERIFICATION CHECKLIST
-- ========================================================================
-- ☐ Table created successfully
-- ☐ All columns present (id, subject, body, target_scholarship, target_status, scheduled_at, status, created_at)
-- ☐ Indexes created
-- ☐ Test data inserted (optional)
-- ☐ Queries run successfully
-- ========================================================================

-- TROUBLESHOOTING
-- ========================================================================
-- If you get "Table already exists" error:
--   → That's fine! The table is already set up
--
-- If you get "Unknown column" error:
--   → Drop the table and recreate it: DROP TABLE ScheduledEmails;
--   → Then run the CREATE TABLE statement again
--
-- To completely reset and start fresh:
--   → DROP TABLE IF EXISTS ScheduledEmails;
--   → Then run this entire script again
-- ========================================================================