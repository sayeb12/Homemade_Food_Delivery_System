<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once __DIR__ . '/includes/env.php';

function sendPHPMailerVerificationEmail($email, $name, $code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = app_env('MAIL_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = app_env('MAIL_USERNAME', '');
        $mail->Password = app_env('MAIL_PASSWORD', '');
        $mail->SMTPSecure = 'tls';
        $mail->Port = (int) app_env('MAIL_PORT', 587);

        if (!$mail->Username || !$mail->Password) {
            throw new Exception('Mail credentials are not configured.');
        }

        // Recipients
        $mail->setFrom(app_env('MAIL_FROM', $mail->Username), app_env('MAIL_FROM_NAME', 'Homemade Food Delivery'));
        $mail->addAddress($email, $name ?: 'User');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verification Code for Homemade Food Delivery';
        $mail->Body = "
            <h2>Verification Code</h2>
            <p>Dear " . ($name ?: 'User') . ",</p>
            <p>Your verification code is: <strong>$code</strong></p>
            <p>Please enter this code to complete your login or registration.</p>
            <p>If you did not request this code, please ignore this email.</p>
            <p>Best regards,<br>Homemade Food Delivery Team</p>
        ";
        $mail->AltBody = "Your verification code is: $code\n\nPlease enter this code to complete your login or registration.\nIf you did not request this code, please ignore this email.\n\nBest regards,\nHomemade Food Delivery Team";

        $mail->send();
        error_log("Verification email sent to $email with code $code");
        return true;
    } catch (Exception $e) {
        error_log("Failed to send verification email to $email. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
