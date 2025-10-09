<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed Successfully</title>
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
            border-bottom: 2px solid #27ae60;
        }
        .header h1 {
            color: #27ae60;
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #27ae60;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #229954;
        }
        .security-info {
            background-color: #ecf0f1;
            border-left: 4px solid #27ae60;
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
        table {
            width: 100%;
            margin: 15px 0;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #ecf0f1;
        }
        table td:first-child {
            font-weight: bold;
            width: 40%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Password Changed Successfully</h1>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $username }}</strong>,</p>
            
            <p>Your password has been successfully changed. This confirms that your account security has been updated.</p>
            
            <div class="security-info">
                <strong>🔐 Change Details:</strong><br>
                <table>
                    <tr>
                        <td>Time:</td>
                        <td>{{ $timestamp }}</td>
                    </tr>
                    <tr>
                        <td>IP Address:</td>
                        <td><code>{{ $ipAddress }}</code></td>
                    </tr>
                    <tr>
                        <td>Location:</td>
                        <td>{{ $location }}</td>
                    </tr>
                </table>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ If you did not make this change:</strong><br>
                <p style="margin: 10px 0;">If you did NOT change your password, your account may have been compromised. Please take the following actions immediately:</p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Contact our support team immediately</li>
                    <li>Review your account activity</li>
                    <li>Enable two-factor authentication</li>
                    <li>Check for unauthorized access</li>
                </ul>
                <div style="text-align: center;">
                    <a href="{{ $supportUrl }}" class="button" style="background-color: #e74c3c;">Contact Support</a>
                </div>
            </div>
            
            <p><strong>Security Tips:</strong></p>
            <ul>
                <li>Never share your password with anyone</li>
                <li>Use a strong, unique password</li>
                <li>Enable two-factor authentication if available</li>
                <li>Regularly update your password</li>
                <li>Be cautious of phishing attempts</li>
            </ul>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ $profileUrl }}" class="button">View My Profile</a>
            </div>
            
            <p>Thank you for keeping your account secure!</p>
            
            <p>Best regards,<br>
            <strong>The Noobz Cinema Security Team</strong></p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Noobz Cinema. All rights reserved.</p>
            <p>This security notification was sent to {{ $email }}</p>
            <p style="margin-top: 10px; font-size: 11px;">This is an automated security email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
