USE db1;

CREATE TABLE IF NOT EXISTS `ScheduledEmails` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `target_scholarship` VARCHAR(255) NOT NULL,
    `target_status` VARCHAR(255) NOT NULL,
    `scheduled_at` DATETIME NOT NULL,
    `status` ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_scheduled_at_status ON ScheduledEmails (scheduled_at, status);

-- Create a table for Email History (Sent/Failed/Pending)
CREATE TABLE IF NOT EXISTS EmailLogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body TEXT,
    target_group VARCHAR(100),
    status ENUM('sent', 'pending', 'failed') NOT NULL,
    recipient_count INT DEFAULT 0,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_message TEXT
) ENGINE=InnoDB;


-- (Optional) If you already have ScheduledEmails, you can drop it and use EmailLogs for everything, 
-- but to keep your current logic safe, we will just sync them in the PHP code.

ALTER TABLE EmailLogs MODIFY COLUMN status ENUM('sent', 'pending', 'failed', 'cancelled');
ALTER TABLE ScheduledEmails MODIFY COLUMN status ENUM('sent', 'pending', 'failed', 'cancelled');