<?php
/**
 * Email Verification System
 * Handles email verification token generation, email sending, and verification
 */

require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailVerification {
    
    /**
     * Generate a secure verification token
     * @return string Unique verification token
     */
    public static function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Generate token expiry time (24 hours from now)
     * @return string MySQL timestamp
     */
    public static function getTokenExpiry() {
        return date('Y-m-d H:i:s', strtotime('+24 hours'));
    }
    
    /**
     * Create and store verification token for user
     * @param int $userId User ID
     * @param PDO $pdo Database connection
     * @return string Generated token
     */
    public static function createVerificationToken($userId, $pdo) {
        $token = self::generateToken();
        $expiresAt = self::getTokenExpiry();
        
        $stmt = $pdo->prepare("
            UPDATE waitlist_users 
            SET verification_token = ?, verification_token_expires = ? 
            WHERE id = ?
        ");
        $stmt->execute([$token, $expiresAt, $userId]);
        
        return $token;
    }
    
    /**
     * Verify token validity
     * @param string $token Verification token
     * @param PDO $pdo Database connection
     * @return array User data if valid, false otherwise
     */
    public static function verifyToken($token, $pdo) {
        $stmt = $pdo->prepare("
            SELECT id, name, email, verification_token_expires, email_verified
            FROM waitlist_users 
            WHERE verification_token = ?
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Check if token has expired
        if (strtotime($user['verification_token_expires']) < time()) {
            return false;
        }
        
        // Check if already verified
        if ($user['email_verified']) {
            return $user;
        }
        
        return $user;
    }
    
    /**
     * Mark user as verified
     * @param int $userId User ID
     * @param PDO $pdo Database connection
     * @return bool Success status
     */
    public static function markAsVerified($userId, $pdo) {
        $stmt = $pdo->prepare("
            UPDATE waitlist_users 
            SET email_verified = 1, verification_token = NULL, verification_token_expires = NULL
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Send verification email
     * @param string $email User email
     * @param string $name User name
     * @param string $token Verification token
     * @return bool Success status
     */
    public static function sendVerificationEmail($email, $name, $token) {
        try {
            // Get SMTP configuration from .env file
            $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
            $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
            $smtpPort = EnvLoader::get('SMTP_PORT', 587);
            $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');
            
            // Create email content
            $siteUrl = self::getSiteUrl();
            $verificationLink = $siteUrl . '/verify_email.php?token=' . urlencode($token);
            
            $subject = "Verify Your Email - Funding4x Waitlist";
            $body = self::getEmailTemplate($name, $email, $verificationLink);
            
            // Create PHPMailer instance
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUsername);
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = (int)$smtpPort;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Recipients
            $mail->setFrom('noreply@funding4x.com', 'Funding4x');
            $mail->addAddress($email, $name);
            $mail->addReplyTo('support@funding4x.com', 'Funding4x Support');
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body); // Plain text alternative
            
            
            // Send email
            $sent = $mail->send();
            
            return $sent;
            
        } catch (Exception $e) {
            
            // Also log additional debug info
            $smtpHost = EnvLoader::get('SMTP_HOST', 'N/A');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', 'N/A');
            $smtpPort = EnvLoader::get('SMTP_PORT', 'N/A');
            error_log("SMTP Config - Host: $smtpHost, Username: $smtpUsername, Port: $smtpPort");
            
            return false;
        }
    }
    
    /**
     * send referral dashboard email
     */

    public static function sendReferralDashboardEmail($email, $name, $referral_code) {
        try {
            // Get SMTP config
            $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
            $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
            $smtpPort = EnvLoader::get('SMTP_PORT', 587);
            $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');

            // Load your waiting list confirmed email template (HTML)
            $templatePath = __DIR__ . "/email_templates/waiting_list_confirmed.html";
            $body = file_exists($templatePath) ? file_get_contents($templatePath) : "";

            // Replace placeholders
            $body = str_replace("FNAME", htmlspecialchars($name), $body);
            $body = str_replace("REFERRAL_CODE", urlencode($referral_code), $body);

            $subject = $name . " You're on the Waiting List";

            // PHPMailer
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUsername);
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = (int)$smtpPort;

            $mail->setFrom('noreply@funding4x.com', 'Funding4x');
            $mail->addAddress($email, $name);
            $mail->addReplyTo('support@funding4x.com', 'Funding4x Support');

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();

        } catch (Exception $e) {
            error_log("Referral Dashboard Email failed for $email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send account ready for trading email
     * @param string $email User email
     * @param string $name User name
     * @return bool Success status
     */
    public static function sendAccountReadyEmail($email, $name) {
        try {
            // Get SMTP config
            $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
            $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
            $smtpPort = EnvLoader::get('SMTP_PORT', 587);
            $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');

            // Load account ready email template (HTML)
            $templatePath = __DIR__ . "/email_templates/account_ready.html";
            $body = file_exists($templatePath) ? file_get_contents($templatePath) : "";


            // Replace placeholders
            $body = str_replace("USER_NAME", htmlspecialchars($name), $body);
            $body = str_replace("USER_EMAIL", htmlspecialchars($email), $body);

            $subject = "Your Account is Ready for Trading";

            // PHPMailer
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUsername);
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = (int)$smtpPort;

            $mail->setFrom('noreply@funding4x.com', 'Funding4x');
            $mail->addAddress($email, $name);
            $mail->addReplyTo('support@funding4x.com', 'Funding4x Support');
            $mail->addBCC('admin@funding4x.com');

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '</div>'], ["\n", "\n\n", "\n"], $body));

            return $mail->send();

        } catch (Exception $e) {
            error_log("Account Ready Email failed for $email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send trading test failed email
     * @param string $email User email
     * @param string $name User name
     * @param array $failReasons Array of fail reasons
     * @param string|null $attachmentPath Path to attachment file
     * @return bool Success status
     */
    public static function sendFailEmail($email, $name, $failReasons = [], $attachmentPath = null) {
        try {
            // Get SMTP config
            $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
            $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
            $smtpPort = EnvLoader::get('SMTP_PORT', 587);
            $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');

            // Load fail email template (HTML)
            $templatePath = __DIR__ . "/email_templates/fail_email_template.html";
            $body = file_exists($templatePath) ? file_get_contents($templatePath) : "";

            // Generate dynamic rules table
            $rulesTable = '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-size: 15px;">';
            foreach ($failReasons as $reason) {
                $rulesTable .= '<tr>';
                $rulesTable .= '<td width="30" valign="top" style="padding-bottom: 10px; color: #dc2626; font-size: 18px; font-weight: 900;">&#10004;</td>';
                $rulesTable .= '<td style="padding-bottom: 10px;">' . htmlspecialchars($reason) . '</td>';
                $rulesTable .= '</tr>';
            }
            $rulesTable .= '</table>';

            // Replace placeholders
            $body = str_replace("Hello FNAME,", "Hello " . htmlspecialchars($name) . ",", $body);
            $body = str_replace('{RULES_TABLE}', $rulesTable, $body);

            $subject = "Trading Test 1 Result - Important Update";

            // PHPMailer
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUsername);
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = (int)$smtpPort;

            $mail->setFrom('noreply@funding4x.com', 'Funding4x');
            $mail->addAddress($email, $name);
            $mail->addReplyTo('support@funding4x.com', 'Funding4x Support');
            $mail->addBCC('admin@funding4x.com');

            // Add attachment if provided
            if ($attachmentPath && file_exists($attachmentPath)) {
                $mail->addAttachment($attachmentPath);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '</div>'], ["\n", "\n\n", "\n"], $body));

            return $mail->send();

        } catch (Exception $e) {
            error_log("Fail Email failed for $email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send trading test passed email with certificate
     * @param string $email User email
     * @param string $name User name
     * @param array $attachmentPaths Array of paths to pass certificate files
     * @return bool Success status
     */
    public static function sendPassEmail($email, $name, $attachmentPaths = []) {
        try {
            // Get SMTP config
            $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
            $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
            $smtpPort = EnvLoader::get('SMTP_PORT', 587);
            $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');

            // Load pass email template (HTML)
            $templatePath = __DIR__ . "/email_templates/pass-email.html";
            $body = file_exists($templatePath) ? file_get_contents($templatePath) : "";

            // Replace placeholders
            $body = str_replace("-FNAME-", htmlspecialchars($name), $body);

            $subject = "Congratulations! You Passed Phase 1!";

            // PHPMailer
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUsername);
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = (int)$smtpPort;

            $mail->setFrom('noreply@funding4x.com', 'Funding4x');
            $mail->addAddress($email, $name);
            $mail->addReplyTo('support@funding4x.com', 'Funding4x Support');
            $mail->addBCC('admin@funding4x.com');

            // Add attachments if provided
            if (!empty($attachmentPaths)) {
                foreach ($attachmentPaths as $path) {
                    if (file_exists($path)) {
                        $mail->addAttachment($path);
                    }
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '</div>'], ["\n", "\n\n", "\n"], $body));

            return $mail->send();

        } catch (Exception $e) {
            error_log("Pass Email failed for $email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send an "Under Review" email to the user with optional attachments
     * Uses an HTML template located at email_templates/under_review.html
     * @param string $email User email
     * @param string $name User name
     * @param array $attachmentPaths Array of paths to files to attach
     * @return bool Success status
     */
    public static function sendUnderReviewEmail($email, $name, $attachmentPaths = []) {
        try {
            // Get SMTP config
            $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
            $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
            $smtpPort = EnvLoader::get('SMTP_PORT', 587);
            $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');

            // Load the under review HTML template (preferred) or fall back to a simple body
            $templatePath = __DIR__ . "/email_templates/under_review.html";
            if (file_exists($templatePath)) {
                $body = file_get_contents($templatePath);
                $body = str_replace("USER_NAME", htmlspecialchars($name), $body);
                $body = str_replace("USER_EMAIL", htmlspecialchars($email), $body);
            } else {
                $body = "<div style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>"
                      . "<p>Hello " . htmlspecialchars($name) . ",</p>"
                      . "<p>Your trading test has been marked <strong>Under Review</strong> by our team. Please find any relevant documents attached.</p>"
                      . "<p>We will notify you with further updates after review.</p>"
                      . "<p>Best regards,<br/>Funding4x Team</p>"
                      . "</div>";
            }

            $subject = "Your Trading Test Is Under Review";

            // PHPMailer
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUsername);
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = (int)$smtpPort;

            $mail->setFrom('noreply@funding4x.com', 'Funding4x');
            $mail->addAddress($email, $name);
            $mail->addReplyTo('support@funding4x.com', 'Funding4x Support');
            $mail->addBCC('admin@funding4x.com');

            // Attach files if provided
            if (!empty($attachmentPaths)) {
                foreach ($attachmentPaths as $path) {
                    if (file_exists($path)) {
                        $mail->addAttachment($path);
                    }
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '</div>'], ["\n", "\n\n", "\n"], $body));

            return $mail->send();

        } catch (Exception $e) {
            error_log("Under Review Email failed for $email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get email template
     * @param string $name User name
     * @param string $email User email
     * @param string $verificationLink Verification link
     * @return string Email HTML template
     */
    private static function getEmailTemplate($name, $email, $verificationLink) {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Email Verification - Funding4x</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f4f4f4;
                }
                .container {
                    background-color: #ffffff;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    background: linear-gradient(135deg, #f97316, #ea580c);
                    color: white;
                    padding: 20px;
                    border-radius: 10px 10px 0 0;
                    margin: -30px -30px 20px -30px;
                }
                .button {
                    display: inline-block;
                    background: linear-gradient(135deg, #f97316, #ea580c);
                    color: white !important;
                    padding: 15px 30px;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                    font-size: 12px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Welcome to Funding4x!</h1>
                    <p>Your exclusive access awaits</p>
                </div>
                
                <h2>Hello ' . htmlspecialchars($name) . ',</h2>
                
                <p>Thank you for joining our exclusive waitlist! You\'re now part of an elite group of skilled Forex traders who will have the opportunity to trade our $5000 accounts.</p>
                
                <p><strong>What happens next?</strong></p>
                <ul>
                    <li><strong>Verify your email address</strong> (required to activate your account)</li>
                    <li>Wait for our team to review applications</li>
                    <li>Get notified when we go live and trading begins to Test Your Trading Skill</li>
                    <li>Start Referring other Forex Trader so that you can Enter the Test for FREE.</li>
                </ul>
                
                <p>To complete your registration and ensure you receive important updates, please verify your email address by clicking the button below:</p>
                
                <div style="text-align: center;">
                    <a href="' . $verificationLink . '" class="button">Verify Email Address</a>
                </div>
                
                <p><small>This verification link will expire in 24 hours for security purposes.</small></p>
                
                <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
                <p><a href="' . $verificationLink . '">' . $verificationLink . '</a></p>
                
                <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
                
                <h3> Why Join Our Program?</h3>
                <ul>
                    <li><strong>Private Prop Fund:</strong> Trade our money, share the profits</li>
                    <li><strong>50:50 Profit Share:</strong> Fair and transparent profit distribution</li>
                    <li><strong>No Hidden Rules:</strong> Unlike other prop firms, we have clear, fair rules</li>
					<li><strong>Simple Trading Test with clear simple rules to follow to ensure you dont lose too much money</li>
                    <li><strong>$5000 Starting Accounts:</strong> Trade with substantial capital</li>
                </ul>
                
                <div class="footer">
                    <p>This email was sent to ' . htmlspecialchars($email) . '</p>
                    <p>If you didn\'t request this account, please ignore this email.</p>
                    <p>&copy; 2024 Funding4x. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Send password reset email
     * @param string $email User email
     * @param string $name User name
     * @param string $resetLink Password reset link
     * @return bool Success status
     */
    public static function sendPasswordResetEmail($email, $name, $resetLink) {
        try {
            // Get SMTP config
            $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
            $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
            $smtpPort = EnvLoader::get('SMTP_PORT', 587);
            $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');

            // Load password reset email template (HTML)
            $templatePath = __DIR__ . "/email_templates/password_reset.html";
            $body = file_exists($templatePath) ? file_get_contents($templatePath) : "";

            // Replace placeholders
            $body = str_replace("USER_NAME", htmlspecialchars($name), $body);
            $body = str_replace("USER_EMAIL", htmlspecialchars($email), $body);
            $body = str_replace("RESET_LINK", $resetLink, $body);

            $subject = "Reset Your Password - Funding4x";

            // PHPMailer
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUsername);
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = (int)$smtpPort;

            $mail->setFrom('noreply@funding4x.com', 'Funding4x');
            $mail->addAddress($email, $name);
            $mail->addReplyTo('support@funding4x.com', 'Funding4x Support');

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '</div>'], ["\n", "\n\n", "\n"], $body));

            return $mail->send();

        } catch (Exception $e) {
            error_log("Password reset email failed for $email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send MT5 details update email to support
     * @param string $mt5Username MT5 account username
     * @return bool Success status
     */
    public static function sendMT5UpdateEmail($mt5Username) {
        try {
            // Get SMTP configuration
            $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
            $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
            $smtpPort = EnvLoader::get('SMTP_PORT', 587);
            $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');

            // Create PHPMailer instance
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUsername);
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = (int)$smtpPort;

            // Recipients
            $mail->setFrom('noreply@funding4x.com', 'Funding4x');
            $mail->addAddress('support@funding4x.com', 'Support Team');
            $mail->addBCC('admin@funding4x.com');

            // Content
            $mail->isHTML(false);
            $mail->Subject = 'MT5 Details Updated by User';
            $mail->Body = 'MT5 details updated by user, MT5 account number: ' . $mt5Username;

            // Send email
            return $mail->send();

        } catch (Exception $e) {
            error_log('Failed to send MT5 update email: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendCustomEmail($to, $name, $subject, $body, $attachment = null) {
        try {
            // Get SMTP configuration from .env file (same as EmailVerification)
            $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
            $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
            $smtpPort = EnvLoader::get('SMTP_PORT', 587);
            $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');

            // Replace placeholders in body
            $body = str_replace('{name}', htmlspecialchars($name), $body);
            $body = str_replace('{email}', htmlspecialchars($to), $body);

            // Add greeting at the top
            // $body = "Hi " . $name . ",<br/>" . $body;

            // Load HTML email template
            $htmlBody = file_get_contents(__DIR__ . '/email_templates/custom_email_template.html');
            $htmlBody = str_replace(['{$subject}', '{$body}'], [$subject, $body], $htmlBody);

            // Create PHPMailer instance
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = !empty($smtpUsername);
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = (int)$smtpPort;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Recipients
            $mail->setFrom('support@funding4x.com', 'Funding4x Support');
            $mail->addAddress($to, $name);
            $mail->addReplyTo('noreply@funding4x.com', 'Funding4x');
            $mail->addBCC('admin@funding4x.com');

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($body); // Plain text alternative

            // Handle attachment
            if ($attachment !== null) {
                // Validate file size (max 10MB)
                if ($attachment['size'] > 10 * 1024 * 1024) {
                    throw new Exception('Attachment file size exceeds 10MB limit');
                }

                // Validate file type
                $allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain',
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/gif'
                ];

                $fileType = mime_content_type($attachment['tmp_name']);
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception('Invalid file type. Only PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, GIF files are allowed');
                }

                // Add attachment
                $mail->addAttachment($attachment['tmp_name'], $attachment['name']);
            }

            // Send email
            $sent = $mail->send();

            return $sent;

        } catch (Exception $e) {
            // Log error for debugging
            error_log("Admin Email failed for $to: " . $e->getMessage());

            // Also log SMTP config for debugging
            $smtpHost = EnvLoader::get('SMTP_HOST', 'N/A');
            $smtpUsername = EnvLoader::get('SMTP_USERNAME', 'N/A');
            $smtpPort = EnvLoader::get('SMTP_PORT', 'N/A');
            error_log("SMTP Config - Host: $smtpHost, Username: $smtpUsername, Port: $smtpPort");

            return false;
        }
    }

    /**
     * Get site URL
     * @return string Site URL
     */
    private static function getSiteUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
?>