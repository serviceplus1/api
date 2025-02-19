<?php

namespace App\Core;

use \App\Core\Config;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;

class Email
{
    /** @var array */
    private $data;

    /** @var Config */
    private $config;

    /** @var PHPMailer */
    private $mail;

    public function __construct()
    {
        $this->config = Config::get("mail");

        $this->mail = new PHPMailer(true);

        // Setup
        $this->mail->isSMTP();
        $this->mail->setLanguage( $this->config["option"]["lang"] );
        $this->mail->isHTML( $this->config["option"]["html"] );
        $this->mail->SMTPAuth = $this->config["option"]["auth"];
        $this->mail->CharSet = $this->config["option"]["charset"];

        if ($this->config["option"]["debug"])
        $this->mail->SMTPDebug = $this->config["option"]["debug"];

        //Auth
        $this->mail->Host = $this->config["smtp"]["host"];
        $this->mail->Port = $this->config["smtp"]["port"];
        $this->mail->Username = $this->config["smtp"]["user"];
        $this->mail->Password = $this->config["smtp"]["pass"];
        $this->mail->SMTPSecure = $this->config["smtp"]["secure"];

        // Sender
        $this->mail->Sender = $this->config["sender"]["email"];

        $this->data = new \stdClass();

        $this->data->fromEmail = $this->config["sender"]["email"];

    }

    public function setDebug($debug)
    {
        $this->mail->SMTPDebug = $debug;
        return $this;
    }

    public function setSubject(string $subject): Email
    {
        $this->data->subject = $subject;
        return $this;
    }

    public function setSender(string $email, string $name=null): Email
    {
        $this->mail->Sender = $email;
        return $this;
    }

    public function setMessage(string $message): Email
    {
        $this->data->message = $message;
        return $this;
    }

    public function address($email=null, $name = null)
    {

        if ($email) {

            $email = (array)$email;
            $name = (array)$name;

            foreach ($email as $k => $m) {

                if (!is_email($m)) {

                    die("O e-mail {$m} é inválido");

                } else {

                    $this->data->address[$m] = $name[$k]??null;

                }
            }
        }
        return $this;
    }

    public function cc($email=null, $name = null)
    {

        if ($email) {

            $email = (array)$email;
            $name = (array)$name;

            foreach ($email as $k => $m) {

                if (!is_email($m)) {

                    die("O e-mail {$m} é inválido");

                } else {

                    $this->data->cc[$m] = $name[$k]??null;

                }
            }
        }
        return $this;
    }

    public function bcc($email=null, $name = null)
    {
        if ($email) {

            $email = (array)$email;
            $name = (array)$name;

            foreach ($email as $k => $m) {

                if (!is_email($m)) {

                    die("O e-mail {$m} é inválido");

                } else {

                    $this->data->bcc[$m] = $name[$k]??null;

                }
            }
        }
        return $this;
    }

    public function attach(string $filePath, string $fileName): Email
    {
        $this->data->attach[$filePath] = $fileName;
        return $this;
    }

    public function setFrom($email, $name=null)
    {
        $this->data->fromEmail = $email;
        if ($name)
            $this->data->fromName = $name;
        return $this;
    }

    public function setFromName($name)
    {
        $this->data->fromName = $name;
        return $this;
    }

    public function reply($email, $name=null)
    {
        $this->data->replyEmail = $email;
        if ($name)
            $this->data->replyName = $email;
        return $this;
    }

    public function send($fromEmail=null, $fromName=null): bool
    {
        if (empty($this->data)) {
            return false;
        }

        if ($fromEmail) {

            if (!is_email($fromEmail)) {
                echo "E-mail de remetente inválido";
                return false;
            }

            $this->data->fromEmail = $fromEmail;
        }

        if ($fromName)
            $this->data->fromName = $fromName;

        if (empty($this->data->address)) {
            echo "E-mail de destinatário inválido";
            return false;
        }

        try {

            $this->mail->Subject = $this->data->subject;
            $this->mail->Body = $this->data->message;

            foreach($this->data->address as $email => $name) {
                $this->mail->addAddress($email, $name);
            }

            if (!empty($this->data->cc)) {
                foreach($this->data->cc as $email => $name) {
                    $this->mail->addCC($email, $name);
                }
            }

            if (!empty($this->data->bcc)) {
                foreach($this->data->bcc as $email => $name) {
                    $this->mail->addBCC($email, $name);
                }
            }

            $this->mail->setFrom($this->data->fromEmail, $this->data->fromName);

            if (!empty($this->data->attach)) {
                foreach($this->data->attach as $path => $name) {
                    $this->mail->addAttachment($path, $name);
                }
            }

            if (!empty($this->data->replyEmail)) {

                $this->mail->AddReplyTo($this->data->replyEmail, $this->data->replyName);
            }

            $this->mail->send();

            $this->mail->ClearAllRecipients();
            $this->mail->ClearAttachments();

            return true;

        } catch (Exception $e) {

            echo $e->getMessage();
            return false;
        }

    }

    public function mail(): PHPMailer
    {
        return $this->mail;
    }

    public function template($text, $center=false, $date=true)
    {

        $body = "<html>
                    <body style=\"color:#000;font-size:16px;line-height:24px;font-family:Arial;\">";

        $body .= $center ? "<center>" : "";

        $body .= $text;

        $body .= $date ? "<br><small>Enviado em: ".date("d/m/Y \à\s H:i") : "";

        $body .= $center ? "</center>" : "";

        $body .= "</body>
                    </html>";

        return $body;
    }

}