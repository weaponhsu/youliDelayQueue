<?php


namespace src;


use conf\Config;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class EmailHandler
{
    static public $instance = null;
    public $mail;
    public $log_path = null;

    static public function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }

    private function __construct()
    {
        date_default_timezone_set("PRC");
        $this->log_path = realpath(__DIR__) . '/../log/email_handler-' . date('Y-m-d', time()) . '.log';
    }

    /**
     * 发送邮件
     * @param $email_address
     * @param $subject
     * @param $body
     */
    public function mail($email_address, $subject, $body) {
        self::log($this->log_path, "INFO - sent mail: $email_address");
        $mail = new PHPMailer(true);
        try {
            if (empty($email_address) || ! in_array($email_address, Config::ALLOWED_EMAIL_ADDRESS))
                throw new Exception("无效邮箱地址");

            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host = Config::SMTP_SERVER;
            $mail->SMTPAuth = true;
            $mail->Username = Config::SMTP_USERNAME;
            $mail->Password = Config::SMTP_PWD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 25;

            $mail->setFrom(Config::SMTP_FROM, 'Mailer');
            $mail->addAddress($email_address);

            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
        } catch (Exception $e) {
            self::log($this->log_path, "ERROR - message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }

    static protected function log($log_path = null, $content = '') {
        if (!is_null($log_path)) {
            error_log('[' . date('Y-m-d H:i:s', time()) . '] - '.$content . "\r\n", 3, $log_path);
        }
    }
}