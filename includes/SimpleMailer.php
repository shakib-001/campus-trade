<?php
/**
 * includes/SimpleMailer.php
 *
 * A minimal, dependency-free SMTP client. Sends real emails using nothing but
 * PHP's built-in stream sockets + OpenSSL — no Composer, no PHPMailer install
 * needed. Works with Gmail SMTP (smtp.gmail.com:587 + an App Password) or any
 * other standard SMTP server that supports STARTTLS + AUTH LOGIN.
 *
 * This is intentionally simple (a few dozen lines of raw SMTP protocol) so it
 * has zero external dependencies — handy in environments where running
 * `composer install` isn't convenient.
 */

class SimpleMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;

    public function __construct($host, $port, $username, $password, $fromEmail, $fromName) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * Sends an HTML email. Returns true on success, or throws an Exception
     * with the SMTP server's error message on failure (caller should catch it).
     */
    public function send($toEmail, $toName, $subject, $htmlBody) {
        $socket = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno, $errstr, 15
        );
        if (!$socket) {
            throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
        }

        $this->expect($socket, 220);
        $this->command($socket, "EHLO localhost", 250);
        $this->command($socket, "STARTTLS", 220);

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception("STARTTLS negotiation failed.");
        }

        $this->command($socket, "EHLO localhost", 250);
        $this->command($socket, "AUTH LOGIN", 334);
        $this->command($socket, base64_encode($this->username), 334);
        $this->command($socket, base64_encode($this->password), 235);

        $this->command($socket, "MAIL FROM: <{$this->fromEmail}>", 250);
        $this->command($socket, "RCPT TO: <{$toEmail}>", 250);
        $this->command($socket, "DATA", 354);

        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "To: {$toName} <{$toEmail}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // Escape lines that start with a lone "." per the SMTP DATA spec
        $escapedBody = str_replace("\r\n.", "\r\n..", $htmlBody);

        fwrite($socket, $headers . "\r\n" . $escapedBody . "\r\n.\r\n");
        $this->expect($socket, 250);

        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    }

    private function command($socket, $cmd, $expectedCode) {
        fwrite($socket, $cmd . "\r\n");
        $this->expect($socket, $expectedCode);
    }

    private function expect($socket, $expectedCode) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Multi-line SMTP responses use "code-" until the final "code ".
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        $code = (int) substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new Exception("SMTP error (expected $expectedCode): " . trim($response));
        }
        return $response;
    }
}
?>
