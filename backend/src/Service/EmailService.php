<?php

namespace App\Service;

class EmailService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly string $supportEmail
    ) {}

    private function send(string $to, string $subject, string $htmlBody): bool
    {
        $payload = json_encode([
            'from'    => "{$this->fromName} <{$this->fromEmail}>",
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $htmlBody,
        ]);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    // ── Auth emails ───────────────────────────────────────────────────

    public function sendVerificationCode(string $toEmail, string $username, string $code, string $type = 'registration'): bool
    {
        $subject = match ($type) {
            'password_reset' => 'Reset your MyPocketMarket password',
            default          => 'Verify your MyPocketMarket account',
        };

        $headline = match ($type) {
            'password_reset' => 'Password Reset Code',
            default          => 'Email Verification Code',
        };

        $intro = match ($type) {
            'password_reset' => 'You requested to reset your password. Use the code below:',
            default          => 'Thanks for registering! Use the code below to verify your email:',
        };

        $html = $this->baseTemplate($headline, "
            <p>Hi <strong>$username</strong>,</p>
            <p>$intro</p>
            <div style='font-size:36px;font-weight:bold;letter-spacing:8px;text-align:center;
                        padding:20px;background:#f4f4f4;border-radius:8px;margin:20px 0;'>
                $code
            </div>
            <p>This code expires in 24 hours. Do not share it with anyone.</p>
        ");

        return $this->send($toEmail, $subject, $html);
    }

    public function sendWelcome(string $toEmail, string $username): bool
    {
        $html = $this->baseTemplate('Welcome to MyPocketMarket!', "
            <p>Hi <strong>$username</strong>,</p>
            <p>Your account has been verified and is ready to use. You can now post ads,
               message sellers, and build your reputation on the platform.</p>
            <p>Happy trading!</p>
        ");
        return $this->send($toEmail, 'Welcome to MyPocketMarket!', $html);
    }

    // ── Moderation emails ─────────────────────────────────────────────

    public function sendBanNotice(string $toEmail, string $username, string $type, int $minutes, string $reason): bool
    {
        $label = match ($type) {
            'ad_ban'    => 'advertisement ban',
            'mute'      => 'mute',
            'staff_ban' => 'staff suspension',
            default     => 'account ban',
        };
        $dur  = $minutes > 0 ? "{$minutes} minutes" : 'indefinitely';
        $html = $this->baseTemplate('MyPocketMarket — Moderation Notice', "
            <p>Hi <strong>$username</strong>,</p>
            <p>You have received a <strong>$label</strong> for <strong>$dur</strong>.</p>
            <p><strong>Reason:</strong> $reason</p>
            <p>If you believe this is a mistake, please contact our support team.</p>
        ");
        return $this->send($toEmail, 'MyPocketMarket — Moderation Notice', $html);
    }

    public function sendSupportReply(string $toEmail, string $username, string $subject, string $preview): bool
    {
        $html = $this->baseTemplate('Support Reply', "
            <p>Hi <strong>$username</strong>,</p>
            <p>Our support team has replied to your request: <strong>$subject</strong></p>
            <blockquote style='border-left:4px solid #ccc;padding-left:12px;color:#555;'>
                " . htmlspecialchars(mb_substr($preview, 0, 300)) . "
            </blockquote>
            <p>Please log in to view the full reply and respond.</p>
        ");
        return $this->send($toEmail, "Support Reply: $subject", $html);
    }

    public function sendSupportClosed(string $toEmail, string $username, string $subject): bool
    {
        $html = $this->baseTemplate('Support Ticket Closed', "
            <p>Hi <strong>$username</strong>,</p>
            <p>Your support request <strong>$subject</strong> has been closed.</p>
            <p>If you have further questions, please open a new support request.</p>
        ");
        return $this->send($toEmail, "Support Closed: $subject", $html);
    }

    // ── Base template ─────────────────────────────────────────────────

    private function baseTemplate(string $title, string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>$title</title></head>
<body style="margin:0;padding:0;background:#f9f9f9;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9;padding:40px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:8px;padding:32px;box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <tr>
          <td style="border-bottom:2px solid #4f46e5;padding-bottom:16px;margin-bottom:24px;">
            <span style="font-size:22px;font-weight:bold;color:#4f46e5;">MyPocketMarket</span>
          </td>
        </tr>
        <tr><td style="padding-top:24px;color:#333;font-size:15px;line-height:1.6;">
          $body
        </td></tr>
        <tr><td style="padding-top:32px;font-size:12px;color:#999;border-top:1px solid #eee;margin-top:24px;">
          This email was sent by MyPocketMarket. Do not reply directly to this email.
          If you need help, visit our support centre inside the app.
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
