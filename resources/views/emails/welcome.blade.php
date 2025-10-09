<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Noobz Cinema</title>
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
            border-bottom: 2px solid #e74c3c;
        }
        .header h1 {
            color: #e74c3c;
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #e74c3c;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #c0392b;
        }
        .info-box {
            background-color: #ecf0f1;
            border-left: 4px solid #e74c3c;
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
        .quick-links {
            margin: 20px 0;
        }
        .quick-links a {
            display: inline-block;
            margin: 5px 10px;
            color: #3498db;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 Welcome to Noobz Cinema!</h1>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $username }}</strong>,</p>
            
            <p>Thank you for joining <strong>Noobz Cinema</strong> - your ultimate destination for movies and series!</p>
            
            <p>Your account has been successfully created and you're all set to start exploring our vast collection of entertainment.</p>
            
            @if($inviteCode)
            <div class="info-box">
                <strong>📨 Registration Details:</strong><br>
                You registered using invite code: <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">{{ $inviteCode }}</code>
            </div>
            @endif
            
            <div style="text-align: center;">
                <a href="{{ $homeUrl }}" class="button">Start Exploring 🍿</a>
            </div>
            
            <div class="info-box">
                <strong>✨ Quick Start Guide:</strong><br>
                1. Browse our latest movies and series<br>
                2. Add favorites to your watchlist<br>
                3. Search for specific titles<br>
                4. Enjoy unlimited streaming!
            </div>
            
            <div class="quick-links">
                <strong>Quick Links:</strong><br>
                <a href="{{ $moviesUrl }}">Browse Movies</a> |
                <a href="{{ $seriesUrl }}">Browse Series</a> |
                <a href="{{ $profileUrl }}">My Profile</a>
            </div>
            
            <p>If you have any questions or need assistance, feel free to reach out to our support team.</p>
            
            <p>Happy watching! 🎉</p>
            
            <p>Best regards,<br>
            <strong>The Noobz Cinema Team</strong></p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Noobz Cinema. All rights reserved.</p>
            <p>This email was sent to {{ $email }}</p>
        </div>
    </div>
</body>
</html>
