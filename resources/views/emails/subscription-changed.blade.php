<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your ZapTask Subscription Has Been Updated</title>
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
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }
        .plan-name {
            font-weight: bold;
            color: #111827;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://www.zaptask.co.uk/zap_icon_white.svg" alt="ZapTask" class="logo">
        <h1 style="display: inline-block; margin: 0; vertical-align: middle;">Subscription Updated</h1>
    </div>

    <div class="content">
        <p>Hi {{ $recipientName }},</p>

        <p>Thank you for updating your subscription with ZapTask.</p>

        <div class="highlight">
            <p>Your current plan is now:</p>
            <p class="plan-name">{{ $planName }}</p>
            <p>
                @if($isLifetime)
                    This is a one-time lifetime access plan. You won't be charged again for this plan.
                @else
                    This is a recurring subscription. You can manage or change your plan any time from your account settings.
                @endif
            </p>
        </div>

        <p>You can view and manage your subscription details at any time from your ZapTask dashboard.</p>

        <p>If you have any questions or need help with your plan, just reply to this email or contact us via the site.</p>

        <p>Best regards,<br>The ZapTask Team</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} ZapTask. All rights reserved.</p>
        </div>
    </div>
</body>
</html>


