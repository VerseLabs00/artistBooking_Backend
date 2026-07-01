<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="color: #DB0000; text-align: center;">Verify Your Email</h2>
        <p>Hello,</p>
        <p>Thank you for signing up with Artist Booking. Use the code below to verify your email address:</p>
        
        <div style="text-align: center; margin: 30px 0; padding: 20px; background-color: #f9f9f9; border-radius: 5px; font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #000;">
            {{ $code }}
        </div>
        
        <p>This code will expire in 10 minutes.</p>
        <p>If you did not request this verification, please ignore this email.</p>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="font-size: 12px; color: #777; text-align: center;">
            &copy; {{ date('Y') }} Artist Booking Platform. All rights reserved.
        </p>
    </div>
</body>
</html>
