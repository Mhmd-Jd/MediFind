<?php
// ----------- Import PHPMailer Classes into the Global Namespace -----------
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ----------- Include Composer's Autoloader for PHPMailer -----------
require 'vendor/autoload.php';

/**
 * Sends an email notification using PHPMailer and Gmail SMTP.
 *
 * @param string $recipientEmail The recipient's email address.
 * @param string $subject The subject of the email.
 * @param string $body The HTML body of the email.
 * @param bool $isHTML Whether the email body is HTML or plain text.
 * @return bool True on success, false on failure.
 */
function sendEmailNotification(string $recipientEmail, string $subject, string $body, bool $isHTML = true): bool
{
    // Create a new PHPMailer instance with exception handling enabled
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration for Gmail
        $mail->isSMTP();                                     // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com';                // Gmail SMTP server
        $mail->SMTPAuth = true;                            // Enable SMTP authentication
        $mail->Username = 'skk713628@gmail.com';           // Your Gmail address
        $mail->Password = 'nxixcwnbknnyozsc';              // Your Gmail app password (no spaces)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
        $mail->Port = 587;                             // TCP port for TLS

        // Sender and recipient
        $mail->setFrom('skk713628@gmail.com', 'mediFind'); // Sender's email and name
        $mail->addAddress($recipientEmail); // Recipient's email

        // Email content
        $mail->isHTML($isHTML); // Set email format to HTML
        $mail->Subject = $subject; // Set email subject
        $mail->Body = $body; // Set email body (HTML)
        $mail->AltBody = strip_tags($body); // Plain text version of the email (for non-HTML clients)

        //Send the Email
        $mail->send();
        return true;// Email sent successfully
    } catch (Exception $e) {
        // Log error message if sending fails
        error_log("Email could not be sent. PHPMailer Error: {$mail->ErrorInfo}");
        return false;// Indicate failure
    }
}
?>