<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envío de emails transaccionales.
 *
 * Driver primario: SMTP via PHPMailer. Si MAIL_DRIVER=resend, usa la API de Resend.
 * Si MAIL_DRIVER=log, escribe a storage/logs/emails.log (útil en dev/tests).
 *
 * Para que un caller solo se preocupe del contenido, EmailService::send()
 * acepta un view template path (relative a src/Views/emails/) + variables
 * y se encarga de renderizar y enviar.
 */
final class EmailService
{
    /**
     * @param string|null $driverOverride Permite forzar el driver (útil para tests).
     */
    public function __construct(private readonly ?string $driverOverride = null)
    {
    }

    /**
     * @param string $template path relativo dentro de src/Views/emails/ (ej. 'booking_confirmation')
     * @param array<string,mixed> $vars variables para el template
     * @return bool true si se envió OK
     */
    public function send(string $to, string $subject, string $template, array $vars = []): bool
    {
        $html = $this->renderTemplate($template, $vars);
        if ($html === null) {
            error_log("[EmailService] Template no encontrado: $template");
            return false;
        }

        $driver = $this->driver();
        try {
            return match ($driver) {
                'resend' => $this->sendViaResend($to, $subject, $html),
                'log'    => $this->sendViaLog($to, $subject, $html),
                default  => $this->sendViaSmtp($to, $subject, $html),
            };
        } catch (\Throwable $e) {
            error_log("[EmailService] Send falló: " . $e->getMessage());
            return false;
        }
    }

    private function driver(): string
    {
        return $this->driverOverride ?? (string) config('services.mail.driver', 'smtp');
    }

    /**
     * Renderiza un template PHP de src/Views/emails/.
     * Hace `extract($vars)` para que el template pueda usar las variables como locales.
     *
     * @param array<string,mixed> $vars
     */
    private function renderTemplate(string $template, array $vars): ?string
    {
        $path = dirname(__DIR__) . '/Views/emails/' . $template . '.php';
        if (!is_file($path)) return null;
        extract($vars, EXTR_SKIP);
        ob_start();
        require $path;
        return (string) ob_get_clean();
    }

    private function sendViaSmtp(string $to, string $subject, string $html): bool
    {
        if (!class_exists(PHPMailer::class)) return false;

        $host = (string) config('services.mail.host');
        $username = (string) config('services.mail.username');
        $password = (string) config('services.mail.password');

        // Si no hay credenciales SMTP, fallar silencioso (no romper en local sin config).
        if ($host === '' || $username === '' || $password === '') {
            error_log("[EmailService] SMTP no configurado, email no enviado a $to");
            return false;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = (int) config('services.mail.port', 587);
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(
            (string) config('services.mail.from_address', 'noreply@turneroya.app'),
            (string) config('services.mail.from_name', 'TurneroYa')
        );
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = strip_tags((string) preg_replace('/<br\s*\/?>/i', "\n", $html));

        return $mail->send();
    }

    private function sendViaResend(string $to, string $subject, string $html): bool
    {
        $apiKey = (string) config('services.mail.resend_api_key');
        if ($apiKey === '') {
            error_log("[EmailService] RESEND_API_KEY no configurado");
            return false;
        }

        $from = sprintf(
            '%s <%s>',
            (string) config('services.mail.from_name', 'TurneroYa'),
            (string) config('services.mail.from_address', 'noreply@turneroya.app')
        );

        $ch = curl_init('https://api.resend.com/emails');
        if ($ch === false) return false;

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'from' => $from,
                'to' => [$to],
                'subject' => $subject,
                'html' => $html,
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 15,
        ]);

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 400) {
            error_log("[EmailService] Resend HTTP $code: " . (is_string($resp) ? $resp : ''));
            return false;
        }
        return true;
    }

    private function sendViaLog(string $to, string $subject, string $html): bool
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
        $entry = sprintf(
            "[%s] TO: %s | SUBJECT: %s\n%s\n%s\n",
            date('c'),
            $to,
            $subject,
            str_repeat('=', 60),
            $html
        );
        @file_put_contents($logDir . '/emails.log', $entry, FILE_APPEND);
        return true;
    }
}
