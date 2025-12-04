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