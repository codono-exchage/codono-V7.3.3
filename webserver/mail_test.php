<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// SMTP configuration
$smtpHost = 'mail.darkcoin.pw';
$smtpPort = 110;
$smtpUser = 'support@ttmbase.com';
$smtpPass = 'mypass';
$fromEmail = 'support@ttmbase.com';
$fromName = 'Support';

// Email content
$toEmail = 'recipient@example.com';
$toName = 'Recipient Name';
$subject = 'Test Email';
$body = 'This is a test email sent using PHPMailer with SMTP.';

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = 'tls'; // or 'ssl' if your server requires SSL
    $mail->Port = $smtpPort;

    // Email settings
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($toEmail, $toName);
    $mail->Subject = $subject;
    $mail->Body = $body;

    // Send email
    if ($mail->send()) {
        echo 'Message has been sent';
    } else {
        echo 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
    }
} catch (Exception $e) {
    echo 'Message could not be sent. PHPMailer Exception: ' . $e->getMessage();
}
?>
