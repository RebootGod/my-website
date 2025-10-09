<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
            color: #333333;
            line-height: 1.6;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #ff9800;
        }
        .message {
            font-size: 15px;
            margin-bottom: 20px;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .warning-box p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #6c757d;
            text-align: right;
        }
        .appeal-section {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .appeal-section h3 {
            margin: 0 0 10px 0;
            color: #004085;
            font-size: 16px;
        }
        .appeal-section p {
            margin: 0 0 10px 0;
            color: #004085;
            font-size: 14px;
        }
        .appeal-section ul {
            margin: 10px 0 0 20px;
            color: #004085;
            font-size: 14px;
        }
        .appeal-section li {
            margin-bottom: 5px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin: 15px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            background-color: #343a40;
            padding: 25px 30px;
            text-align: center;
            color: #ffffff;
            font-size: 13px;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #ffffff;
            text-decoration: underline;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 30px 20px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-value {
                text-align: left;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="icon">⚠️</div>
            <h1>Account Suspended</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="greeting">Hello {{ $username }},</p>

            <p class="message">
                We're writing to inform you that your account on <strong>Noobz Cinema</strong> has been temporarily suspended due to violations of our Terms of Service or Community Guidelines.
            </p>

            <!-- Suspension Details -->
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value">{{ $username }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Suspension Date:</span>
                    <span class="info-value">{{ $suspensionDate }}</span>
                </div>
                @if($duration)
                <div class="info-row">
                    <span class="info-label">Duration:</span>
                    <span class="info-value">{{ $duration }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Reason:</span>
                    <span class="info-value">{{ $suspensionReason }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Actioned By:</span>
                    <span class="info-value">{{ $adminName }}</span>
                </div>
            </div>

            <!-- Warning -->
            <div class="warning-box">
                <p><strong>⚠️ What This Means:</strong> During the suspension period, you will not be able to access your account or use any features of Noobz Cinema. Your data remains safe and will be restored upon reactivation.</p>
            </div>

            <!-- Appeal Process -->
            <div class="appeal-section">
                <h3>📝 Appeal Process</h3>
                <p>If you believe this suspension was issued in error or would like to discuss your case, you may:</p>
                <ul>
                    <li>Email us at <strong>{{ $supportEmail }}</strong></li>
                    <li>Include your username and email address</li>
                    <li>Provide a detailed explanation of your situation</li>
                    <li>Attach any relevant evidence or screenshots</li>
                </ul>
                <p>We typically review appeals within 1-3 business days.</p>
            </div>

            <p class="message">
                <strong>Next Steps:</strong>
            </p>
            <ul style="margin: 10px 0 20px 20px; color: #6c757d;">
                <li>Review our <a href="https://noobz.space/terms" style="color: #007bff;">Terms of Service</a></li>
                <li>Understand what led to this suspension</li>
                <li>@if($duration) Wait for the suspension period to end @else Contact support for reactivation @endif</li>
                <li>Ensure future compliance with our guidelines</li>
            </ul>

            <center>
                <a href="mailto:{{ $supportEmail }}" class="button">Contact Support</a>
            </center>

            <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
                Best regards,<br>
                <strong>{{ $adminName }}</strong><br>
                Noobz Cinema Team
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2025 Noobz Cinema. All rights reserved.</p>
            <p>
                <a href="https://noobz.space/terms">Terms of Service</a> | 
                <a href="https://noobz.space/privacy">Privacy Policy</a>
            </p>
            <p style="margin-top: 10px; font-size: 12px; color: #adb5bd;">
                This is an automated message. Please do not reply directly to this email.
            </p>
        </div>
    </div>
</body>
</html>
