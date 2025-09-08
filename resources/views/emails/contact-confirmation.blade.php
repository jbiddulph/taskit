<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thank you for contacting ZapTask</title>
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
            background-color: #10b981;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Thank You for Contacting Us!</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $name }},</p>
        
        <p>Thank you for reaching out to ZapTask support! We've received your message and wanted to confirm that it's safely in our inbox.</p>
        
        <div class="highlight">
            <strong>Your message summary:</strong><br>
            <strong>Subject:</strong> {{ $subject }}<br>
            <strong>Submitted:</strong> {{ now()->format('F j, Y \a\t g:i A') }}
        </div>
        
        <p>Our support team typically responds within 24 hours during business days. We'll get back to you at <strong>{{ $email }}</strong> as soon as possible.</p>
        
        <p>In the meantime, you might find these resources helpful:</p>
        <ul>
            <li><a href="https://www.zaptask.co.uk/demo" style="color: #2563eb;">Try our demo</a> to explore ZapTask features</li>
            <li><a href="https://www.zaptask.co.uk/privacy" style="color: #2563eb;">Privacy Policy</a></li>
            <li><a href="https://www.zaptask.co.uk/terms" style="color: #2563eb;">Terms of Service</a></li>
        </ul>
        
        <p>Thanks for choosing ZapTask!</p>
        
        <p>Best regards,<br>
        The ZapTask Team</p>
    </div>
    
    <div class="footer">
        <p>© 2025 ZapTask.co.uk. All rights reserved.</p>
        <p>This is an automated confirmation email. Please do not reply to this message.</p>
    </div>
</body>
</html>
