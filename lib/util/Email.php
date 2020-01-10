<?php

class Email
{
    public static function send($recipient, $subject, $content, $attachments = [])
    {
        require_once App::$config['root'].'/lib/vendor/phpmailer/PHPMailer.php';

        $smtp = App::$config['smtp'];
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mailer->isSMTP();
        $mailer->Host = $smtp['host'];
        $mailer->Port = $smtp['port'];
        $mailer->CharSet = $smtp['charset'];
        $mailer->SMTPAuth = true;
        $mailer->SMTPSecure = $smtp['secure'];
        $mailer->Username = $smtp['username'];
        $mailer->Password = $smtp['password'];
        $mailer->setFrom($smtp['from']['email'], $smtp['from']['name']);
        $mailer->addAddress($recipient['email'], $recipient['name']);
        $mailer->Subject = $subject;
        $mailer->msgHTML($content);

        if(!empty($attachments))
        {
            foreach ($attachments as $attachment)
            {
                $mailer->addAttachment($attachment);
            }
        }

        $mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mailer->SMTPDebug = 0;
        return $mailer->send();
    }
}