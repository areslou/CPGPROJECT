<?php
// send_scheduled_emails.php

// --- FORCE ERROR REPORTING ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set a consistent timezone
date_default_timezone_set('UTC');

// --------------------------------------------------------------------------
// 1. SETUP
// --------------------------------------------------------------------------
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require_once 'config.php';

// --- LOGGING SETUP ---
$log_file = __DIR__ . '/cron.log';
function write_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] " . $message . "\n", FILE_APPEND);
    echo "[$timestamp] " . $message . "\n"; // Also output to console
}

// Increase timeout limit
set_time_limit(300); 

write_log("--- Cron Job Started ---");

// Check database connection
if (!isset($conn)) {
    write_log("FATAL ERROR: Database connection not established!");
    exit(1);
}

// --------------------------------------------------------------------------
// 2. FETCH PENDING EMAILS
// --------------------------------------------------------------------------
try {
    $current_time = date('Y-m-d H:i:s');
    write_log("Current server time: " . $current_time);
    
    $stmt = $conn->prepare("SELECT * FROM ScheduledEmails WHERE scheduled_at <= ? AND status = 'pending' ORDER BY scheduled_at ASC");
    $stmt->execute([$current_time]);
    $scheduled_emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($scheduled_emails) === 0) {
        write_log("No pending emails to send.");
        write_log("--- Cron Job Finished ---");
        exit(0);
    }

    write_log("Found " . count($scheduled_emails) . " email(s) to send.");

    // --------------------------------------------------------------------------
    // 3. PROCESS AND SEND EACH EMAIL
    // --------------------------------------------------------------------------
    foreach ($scheduled_emails as $email) {
        write_log("========================================");
        write_log("Processing email ID: " . $email['id']);
        write_log("Subject: " . $email['subject']);
        write_log("Scheduled for: " . $email['scheduled_at']);
        write_log("Target Scholarship: " . $email['target_scholarship']);
        write_log("Target Status: " . $email['target_status']);

        // A. Find Recipients for this specific email
        $sql = "SELECT Email, FirstName, LastName FROM StudentDetails WHERE 1=1";
        $params = [];
        
        if ($email['target_scholarship'] != 'all') {
            $sql .= " AND Scholarship LIKE ?";
            $params[] = '%' . $email['target_scholarship'] . '%';
        }
        if ($email['target_status'] != 'all') {
            $sql .= " AND Status = ?";
            $params[] = $email['target_status'];
        }
        
        write_log("SQL Query: " . $sql);
        write_log("Parameters: " . json_encode($params));
        
        $recipient_stmt = $conn->prepare($sql);
        $recipient_stmt->execute($params);
        $recipients = $recipient_stmt->fetchAll(PDO::FETCH_ASSOC);

        write_log("Found " . count($recipients) . " potential recipients");

        if (count($recipients) === 0) {
            write_log("ERROR: No recipients found for email ID: " . $email['id'] . ". Marking as failed.");
            $update_stmt = $conn->prepare("UPDATE ScheduledEmails SET status = 'failed' WHERE id = ?");
            $update_stmt->execute([$email['id']]);
            continue;
        }

        // B. Send the email to all recipients
        $sent_count = 0;
        $failed_count = 0;

        // CREATE A NEW PHPMailer INSTANCE FOR EACH SCHEDULED EMAIL
        $mail = new PHPMailer(true);

        try {
            // --- SERVER SETTINGS (GMAIL) ---
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'societyscholars3@gmail.com';
            $mail->Password   = 'wznztwaofzqwrwqf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom('societyscholars3@gmail.com', 'LSS Admin');
            $mail->isHTML(true);
            $mail->Subject = $email['subject'];

            write_log("SMTP connection configured successfully");

            foreach ($recipients as $index => $student) {
                write_log("  Attempting to send to recipient " . ($index + 1) . "/" . count($recipients) . ": " . $student['Email']);
                
                try {
                    // Clear previous addresses
                    $mail->clearAddresses();
                    $mail->clearAllRecipients();
                    
                    // Add the current recipient
                    $mail->addAddress($student['Email'], $student['FirstName']);
                    
                    // Personalize the message
                    $personalized_body = str_replace('{name}', $student['FirstName'], $email['body']);
                    $mail->Body = $personalized_body;

                    // Send the email
                    if ($mail->send()) {
                        $sent_count++;
                        write_log("  ✓ SUCCESS: Email sent to " . $student['Email']);
                    } else {
                        $failed_count++;
                        write_log("  ✗ FAILED: Could not send to " . $student['Email'] . " - " . $mail->ErrorInfo);
                    }

                } catch (Exception $e) {
                    $failed_count++;
                    write_log("  ✗ EXCEPTION: Failed to send to " . $student['Email'] . ": " . $e->getMessage());
                    write_log("  Mailer Error: " . $mail->ErrorInfo);
                    
                    // Try to reset the SMTP connection
                    try {
                        $mail->getSMTPInstance()->reset();
                    } catch (Exception $resetEx) {
                        write_log("  Warning: Could not reset SMTP connection: " . $resetEx->getMessage());
                    }
                }
                
                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second delay
            }
            
        } catch (Exception $e) {
            write_log("CRITICAL ERROR setting up PHPMailer: " . $e->getMessage());
            $failed_count = count($recipients);
        }
        
        // C. Update the status in the database
        write_log("Summary: Sent=$sent_count, Failed=$failed_count, Total=" . count($recipients));
        
        if ($sent_count > 0) {
            $new_status = 'sent';
            write_log("Marking email ID " . $email['id'] . " as SENT");
        } else {
            $new_status = 'failed';
            write_log("Marking email ID " . $email['id'] . " as FAILED (no emails sent)");
        }

        $update_stmt = $conn->prepare("UPDATE ScheduledEmails SET status = ? WHERE id = ?");
        $update_stmt->execute([$new_status, $email['id']]);
        
        write_log("Email ID " . $email['id'] . " processing complete.");
    }

} catch (PDOException $e) {
    write_log("DATABASE ERROR: " . $e->getMessage());
    write_log("Stack trace: " . $e->getTraceAsString());
} catch (Exception $e) {
    write_log("GENERAL ERROR: " . $e->getMessage());
    write_log("Stack trace: " . $e->getTraceAsString());
}

write_log("--- Cron Job Finished ---");
write_log("");

?>