<!DOCTYPE html>
<html>
<head>
    <title>Contact Form Submission</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { padding: 20px; border: 1px solid #eee; border-radius: 5px; max-width: 600px; margin: 0 auto; }
        .header { background: #f8f8f8; padding: 10px; margin-bottom: 20px; text-align: center; }
        .field { margin-bottom: 10px; }
        .label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Contact Form Submission</h2>
        </div>
        <div class="field">
            <span class="label">Name:</span> {{ $data['first_name'] }} {{ $data['last_name'] }}
        </div>
        <div class="field">
            <span class="label">Email:</span> {{ $data['email'] }}
        </div>
        <div class="field">
            <span class="label">Message:</span>
            <p>{{ $data['message'] }}</p>
        </div>
    </div>
</body>
</html>
