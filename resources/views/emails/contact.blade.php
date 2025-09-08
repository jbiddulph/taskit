<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contact Form Submission - ZapTask</title>
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
        .field {
            margin-bottom: 20px;
        }
        .label {
            font-weight: bold;
            color: #374151;
            display: block;
            margin-bottom: 5px;
        }
        .value {
            background-color: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }
        .message-box {
            background-color: white;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            white-space: pre-wrap;
            font-family: inherit;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://www.zaptask.co.uk/zap_icon_white.svg" alt="ZapTask" class="logo">
        <h1 style="display: inline-block; margin: 0; vertical-align: middle;">ZapTask Contact Form Submission</h1>
    </div>
    
    <div class="content">
        <p>A new contact form submission has been received from the ZapTask website.</p>
        
        <div class="field">
            <span class="label">Name:</span>
            <div class="value">{{ $data['name'] }}</div>
        </div>
        
        <div class="field">
            <span class="label">Email:</span>
            <div class="value">{{ $data['email'] }}</div>
        </div>
        
        <div class="field">
            <span class="label">Subject:</span>
            <div class="value">{{ $data['subject'] }}</div>
        </div>
        
        <div class="field">
            <span class="label">Message:</span>
            <div class="message-box">{{ $data['message'] }}</div>
        </div>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">
        
        <p style="font-size: 14px; color: #6b7280;">
            <strong>Submitted:</strong> {{ now()->format('F j, Y \a\t g:i A T') }}<br>
            <strong>Reply to:</strong> {{ $data['email'] }}
        </p>
    </div>
</body>
</html>
