<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compliance document read</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #000000; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .highlight { background-color: #dbeafe; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #2563eb; }
        .login-button { display: inline-block; background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; }
        dl { margin: 0; }
        dt { color: #6b7280; font-size: 12px; text-transform: uppercase; margin-top: 12px; }
        dd { margin: 0; font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">ZapTask Compliance</h1>
    </div>
    <div class="content">
        <p>Hi {{ $recipientName }},</p>
        <p>OpenAI has read a document uploaded for <strong>{{ $companyName }}</strong> at <strong>{{ $siteName }}</strong>.</p>

        <div class="highlight">
            <p style="margin: 0 0 8px;"><strong>{{ $label }}</strong></p>
            <p style="margin: 0;">{{ $summary ?? 'Review the extracted details in ZapTask.' }}</p>
        </div>

        <dl>
            @if($expiryDate)
                <dt>Expiry date</dt>
                <dd>{{ $expiryDate }}</dd>
            @endif
            @if($issueDate)
                <dt>Issue date</dt>
                <dd>{{ $issueDate }}</dd>
            @endif
            @if($certificateNumber)
                <dt>Certificate / contract number</dt>
                <dd>{{ $certificateNumber }}</dd>
            @endif
            @if($engineerName)
                <dt>Engineer / contractor</dt>
                <dd>{{ $engineerName }}</dd>
            @endif
        </dl>

        <a href="{{ $reviewUrl }}" class="login-button">Review in ZapTask</a>
        <p>Everyone in your company can see this because they joined with your company code.</p>
    </div>
    <div class="footer">
        ZapTask — compliance tracking for your team
    </div>
</body>
</html>
