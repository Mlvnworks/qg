<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Tools
{
    private $connection;
    private $app;

    public function __construct($connection, $app = null)
    {
        $this->connection = $connection;
        $this->app = $app;
    }


    // -------------- SANITIZE INPUT ----------------//
    public function sanitizeInput($input)
    {
        $input = trim((string) $input);

        return $input;
    }

    public function escapeForHtml($input)
    {
        return htmlspecialchars($this->sanitizeInput($input), ENT_QUOTES, 'UTF-8');
    }

    public function escapeForSql($input)
    {
        if (!$this->connection instanceof PDO) {
            throw new RuntimeException('Database connection is not available.');
        }

        return $this->sanitizeInput($input);
    }

    // --------------  GENERATE RANDOM USER ID ----------------//
    public function generateUserId()
    {
        return str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
    }


    // --------------  GENERATE REFERRAL CODE ----------------//
    public function generateReferralCode($length = 6)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }


    // --------------  ALERT ----------------//
    public function alert()
    {
        if (isset($_SESSION["alert"]) || isset($_SESSION["flash"])) {
            $app = $this->app;
            include __DIR__ . '/../components/alert-modal.php';

            unset($_SESSION["alert"]);
            unset($_SESSION["flash"]);
        }
    }

    // --------------  SEND EMAIL ----------------//
    public function sendEmail($content, $to, $subject)
    {
        $mail = new PHPMailer(true);
        $mailHost = Env::get('MAIL_HOST');
        $mailPort = Env::getInt('MAIL_PORT', 587);
        $mailEncryption = strtolower(Env::get('MAIL_ENCRYPTION', 'tls'));
        $mailUsername = Env::get('MAIL_USERNAME');
        $mailPassword = Env::get('MAIL_PASSWORD');
        $mailFromAddress = Env::get('MAIL_FROM_ADDRESS', $mailUsername);
        $mailFromName = Env::get('MAIL_FROM_NAME', Env::get('APP_NAME', 'Questra'));

        if ($mailHost === null || $mailUsername === null || $mailPassword === null || $mailFromAddress === null) {
            return [
                'success' => false,
                'err' => new RuntimeException('Mail configuration is incomplete.')
            ];
        }

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $mailHost;
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;
            $mail->SMTPSecure = $mailEncryption === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $mailPort;

            // Recipients
            $mail->setFrom($mailFromAddress, $mailFromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $content;

            $mail->send();
            return [
                'success' => true,
                'err' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'err' => $e
            ];
        }
    }


    // --------------  MASK EMAIL ----------------//
    public function maskEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false; // invalid email
        }

        list($username, $domain) = explode('@', $email);

        // Always show the first character of the username
        $visibleChar = substr($username, 0, 1);
        $maskedPart = str_repeat('*', max(strlen($username) - 1, 0));

        return $visibleChar . $maskedPart . '@' . $domain;
    }




    // --------------  FORMAT TIMESTAMP  ----------------//
    public function formatTimestamp($timestamp, $format = "F j, Y h:i a")
    {
        if (is_numeric($timestamp)) {
            return date($format, (int) $timestamp);
        }

        $time = strtotime((string) $timestamp);

        if ($time === false) {
            return false;
        }

        return date($format, $time);
    }

    // --------------  MASK A USERNAME  ----------------//
    public function maskUsername($username)
    {
        $length = strlen($username);

        if ($length <= 1) {
            return str_repeat('*', $length);
        }

        if ($length == 2) {
            return $username[0] . '*';
        }

        if ($length <= 4) {
            return $username[0] . str_repeat('*', $length - 2) . $username[$length - 1];
        }

        // For usernames longer than 4 characters
        return $username[0] . '*' . '...' . '*' . $username[$length - 1];
    }

}
