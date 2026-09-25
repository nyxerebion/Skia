<?php
// /backend/email.php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'in-v3.mailjet.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 587;
        $mail->setFrom($_ENV['SMTP_FROM'] ?? 'skia.practice@gmail.com', $_ENV['SMTP_NAME'] ?? 'Skia');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}

function getEmailTemplate($title, $content, $buttonText = null, $buttonLink = null)
{
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$title</title>
        <style>
            body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f1f5f9; color: #0f172a; }
            .container { max-width: 560px; margin: 0 auto; padding: 40px 24px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
            .header { text-align: center; padding-bottom: 24px; border-bottom: 1px solid #e2e8f0; }
            .logo { font-size: 28px; font-weight: 800; color: #60a5fa; letter-spacing: -0.5px; text-decoration: none; }
            .logo span { color: #a855f7; }
            .content { padding: 32px 0; }
            .content h1 { font-size: 24px; font-weight: 700; margin: 0 0 12px 0; color: #0f172a; }
            .content p { font-size: 16px; line-height: 1.6; color: #475569; margin: 0 0 16px 0; }
            .content .highlight { color: #0f172a; font-weight: 600; }
            .btn { display: inline-block; background: #60a5fa; color: #ffffff !important; padding: 12px 32px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 16px; margin: 8px 0 16px 0; }
            .btn-secondary { display: inline-block; background: #f1f5f9; color: #475569; padding: 10px 28px; border-radius: 8px; text-decoration: none; font-size: 14px; border: 1px solid #e2e8f0; }
            .divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
            .footer { text-align: center; padding-top: 24px; border-top: 1px solid #e2e8f0; }
            .footer p { font-size: 13px; color: #64748b; margin: 0 0 4px 0; }
            .footer a { color: #60a5fa; text-decoration: none; }
            .warning { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; font-size: 14px; color: #dc2626; }
            @media (max-width: 480px) { .container { padding: 24px 16px; } .logo { font-size: 22px; } .content h1 { font-size: 20px; } }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <a href='" . SITE_URL . "' class='logo'>Skia<span>.</span></a>
            </div>
            <div class='content'>
                $content
                " . ($buttonText && $buttonLink ? "<div style='text-align:center;'><a href='$buttonLink' class='btn'>$buttonText</a></div>" : "") . "
            </div>
            <hr class='divider'>
            <div class='footer'>
                <p>Skia &bull; Built with &#10084; by Axel</p>
                <p style='font-size:12px;color:#94a3b8;'>This email was sent because you requested a password reset.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function passwordResetEmail($username, $resetLink)
{
    $content = "
        <h1>&#128272; Reset Your Password</h1>
        <p>Hi <span class='highlight'>$username</span>,</p>
        <p>We received a request to reset your password. Click the button below to set a new one.</p>
        <p style='font-size:14px;color:#6b7280;'>This link expires in <strong>1 hour</strong>.</p>
        <div style='text-align:center;'>
            <a href='$resetLink' style='background:#60a5fa;color:#ffffff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:16px;display:inline-block;'>Reset Password</a>
        </div>
        <p style='font-size:14px;color:#6b7280;text-align:center;margin-top:16px;'>
            Or copy this link: <br>
            <span style='word-break:break-all;color:#60a5fa;'>$resetLink</span>
        </p>
        <div class='warning'>
            &#9888; If you didn't request this, please ignore this email.
        </div>
    ";

    return getEmailTemplate('Reset Password', $content);
}
