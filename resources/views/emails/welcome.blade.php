<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to ZapTask!</title>
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
        .login-button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .login-button:hover {
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
        .feature-list {
            background-color: white;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .feature-list ul {
            margin: 0;
            padding-left: 20px;
        }
        .feature-list li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://www.zaptask.co.uk/zap_icon_white.svg" alt="ZapTask" class="logo">
        <h1 style="display: inline-block; margin: 0; vertical-align: middle;">Welcome to ZapTask!</h1>
    </div>
    
    <div class="content">
        @if($company)
            <p>Hi {{ $name }},</p>
            
            <p><strong>Welcome {{ $name }} of {{ $company }} to ZapTask!</strong></p>
            
            <p>Congratulations on setting up your company's project management workspace. Your team is now ready to collaborate more effectively with ZapTask's powerful features.</p>
        @else
            <p>Hi {{ $name }},</p>
            
            <p><strong>Welcome to ZapTask!</strong></p>
            
            <p>Thank you for joining ZapTask! Your account has been successfully created and you're ready to start managing your projects more efficiently.</p>
        @endif
        
        <div class="highlight">
            <strong>Your login details:</strong><br>
            <strong>Email:</strong> {{ $email }}<br>
            <strong>Password:</strong> The password you used when signing up
        </div>
        
        <div style="text-align: center;">
            <a href="https://www.zaptask.co.uk/login" class="login-button">Login to ZapTask</a>
        </div>
        
        <div class="feature-list">
            <h3 style="margin-top: 0; color: #374151;">What you can do with ZapTask:</h3>
            <ul>
                <li><strong>Project Management:</strong> Create and organize unlimited projects</li>
                <li><strong>Task Tracking:</strong> Break down work with detailed to-dos and subtasks</li>
                <li><strong>Team Collaboration:</strong> Assign tasks, add comments, and track progress</li>
                <li><strong>File Attachments:</strong> Upload images and documents to tasks</li>
                <li><strong>Kanban Boards:</strong> Visualize workflow with drag-and-drop columns</li>
                <li><strong>Real-time Updates:</strong> Get instant notifications on task changes</li>
                <li><strong>Company Branding:</strong> Upload your logo for a personalized experience</li>
            </ul>
        </div>
        
        @if($company)
            <p><strong>Next steps for your team:</strong></p>
            <ol>
                <li>Share your company code <strong>{{ $company_code ?? 'your company code' }}</strong> with team members</li>
                <li>Create your first project and start adding tasks</li>
                <li>Invite colleagues to join using your company code during registration</li>
                <li>Explore the dashboard and customize your workflow</li>
            </ol>
        @else
            <p><strong>Get started:</strong></p>
            <ol>
                <li>Log in to your ZapTask account</li>
                <li>Create your first project</li>
                <li>Add some tasks and start organizing your work</li>
                <li>Consider upgrading to a team plan to collaborate with others</li>
            </ol>
        @endif
        
        <p>If you have any questions or need help getting started, don't hesitate to reach out to our support team at <a href="https://www.zaptask.co.uk/contact" style="color: #2563eb;">our contact page</a>.</p>
        
        <p>Welcome aboard!</p>
        
        <p>Best regards,<br>
        The ZapTask Team</p>
    </div>
    
    <div class="footer">
        <p>© 2025 ZapTask.co.uk. All rights reserved.</p>
        <p>You're receiving this email because you created an account with ZapTask.</p>
    </div>
</body>
</html>
