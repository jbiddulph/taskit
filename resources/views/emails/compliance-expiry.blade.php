<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compliance expiry reminder</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #000000; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .highlight { background-color: #fef3c7; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #d97706; }
        .login-button { display: inline-block; background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">ZapTask Compliance</h1>
    </div>
    <div class="content">
        <p>Hi {{ $recipientName }},</p>
        <p>A compliance item for <strong>{{ $companyName }}</strong> needs attention.</p>

        <div class="highlight">
            <p style="margin: 0 0 8px;"><strong>{{ $label }}</strong> — {{ $siteName }}</p>
            <p style="margin: 0;">{{ $whenText }}@if($expiryDate) ({{ $expiryDate }})@endif.</p>
        </div>

        <p>Upload the renewed certificate or contract on the site in ZapTask so expiry dates stay up to date.</p>
        <a href="{{ $complianceUrl }}" class="login-button">Open compliance</a>
    </div>
    <div class="footer">
        ZapTask — expiry reminders for your company
    </div>
</body>
</html>
