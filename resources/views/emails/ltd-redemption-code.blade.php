<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your ZapTask Lifetime Deal Redemption Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #000000;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .logo {
            width: 32px;
            height: 32px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .highlight {
            background-color: #dbeafe;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
            word-break: break-all;
        }
        .redeem-button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .redeem-button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://www.zaptask.co.uk/zap_icon_white.svg" alt="ZapTask" class="logo">
        <h1 style="display: inline-block; margin: 0; vertical-align: middle;">ZapTask Lifetime Deal</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $recipientName }},</p>

        <p>Thank you for purchasing the <strong>{{ $planName }}</strong> lifetime deal{{ $company ? ' for ' . $company->name : '' }}.</p>

        <p>Your unique redemption code is:</p>

        <div class="highlight">
            <strong>{{ $code }}</strong>
        </div>

        <p>
            To activate your lifetime access:
        </p>
        <ol>
            <li>Login to your ZapTask account.</li>
            <li>Go to the <strong>Lifetime Deal Redemption</strong> page.</li>
            <li>Enter the code above and follow the steps to complete your setup.</li>
        </ol>

        <div style="text-align: center;">
            <a href="{{ $redeemUrl }}" class="redeem-button">Redeem Your Lifetime Deal</a>
        </div>

        <p>
            You can always access the redemption page directly at:<br>
            <a href="{{ $redeemUrl }}">{{ $redeemUrl }}</a>
        </p>

        <p>If you have any questions or need help getting started, just reply to this email and we'll be happy to assist.</p>

        <p>Best regards,<br>The ZapTask Team</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} ZapTask. All rights reserved.</p>
        </div>
    </div>
</body>
</html>


