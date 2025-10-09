<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #3498db;
        }
        .header h1 {
            color: #3498db;
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 15px 40px;
            background-color: #3498db;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
            font-size: 16px;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .info-box {
            background-color: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            color: #7f8c8d;
            font-size: 12px;
        }
        .verify-section {
            text-align: center;
            padding: 30px 0;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Verify Your Email Address</h1>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $username }}</strong>,</p>
            
            <p>Thank you for registering with Noobz Cinema! To complete your registration and start enjoying our content, please verify your email address.</p>
            
            <div class="verify-section">
                <p style="margin-bottom: 20px;">Click the button below to verify your email address:</p>
                <a href="{{ $verificationUrl }}" class="button">Verify Email Address ✓</a>
            </div>
            
            <div class="info-box">
                <strong>🔐 Security Information:</strong><br>
                <p style="margin: 10px 0;">This verification link will expire in <strong>{{ $expiresIn }}</strong> for your security.</p>
                <p style="margin: 10px 0;">If you did not create an account with Noobz Cinema, please ignore this email.</p>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ Verification Required:</strong><br>
                <p style="margin: 10px 0;">Until you verify your email address, some features may be restricted:</p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Password reset functionality</li>
                    <li>Email notifications</li>
                    <li>Account recovery options</li>
                </ul>
            </div>
            
            <p><strong>Why verify your email?</strong></p>
            <ul>
                <li>Secure your account and enable password recovery</li>
                <li>Receive important notifications and updates</li>
                <li>Access all features without restrictions</li>
                <li>Protect your account from unauthorized access</li>
            </ul>
            
            <p style="margin-top: 30px; font-size: 14px; color: #7f8c8d;">
                <strong>Having trouble clicking the button?</strong><br>
                Copy and paste this link into your browser:<br>
                <span style="word-break: break-all; color: #3498db;">{{ $verificationUrl }}</span>
            </p>
            
            <p style="margin-top: 30px;">If you didn't request this verification email, you can safely ignore it.</p>
            
            <p>Best regards,<br>
            <strong>The Noobz Cinema Team</strong></p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Noobz Cinema. All rights reserved.</p>
            <p>This verification email was sent to {{ $email }}</p>
            <p style="margin-top: 10px; font-size: 11px;">This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
