<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="color: #DB0000; text-align: center;">Password Reset Request</h2>
        <p>Hello,</p>
        <p>We received a request to reset your password for your Artist Booking account. Use the code below to complete the process:</p>
        
        <div style="text-align: center; margin: 30px 0; padding: 20px; background-color: #f9f9f9; border-radius: 5px; font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #000;">
            {{ $token }}
        </div>
        
        <p>This code will expire in 60 minutes.</p>
        <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="font-size: 12px; color: #777; text-align: center;">
            &copy; {{ date('Y') }} Artist Booking Platform. All rights reserved.
        </p>
    </div>
</body>
</html>
