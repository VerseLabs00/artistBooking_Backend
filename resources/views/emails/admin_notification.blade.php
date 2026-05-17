<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Notification</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
    @php
        // Theme color definitions matching the React admin dashboard icons
        $themeColor = '#3b82f6'; // default booking blue
        $themeBg = '#eff6ff';
        $badgeText = 'Booking';

        if ($notification->type === 'verification') {
            $themeColor = '#d97706'; // Gold/Yellow
            $themeBg = '#fef3c7';
            $badgeText = 'Verification';
        } elseif ($notification->type === 'artist') {
            $themeColor = '#8b5cf6'; // Purple
            $themeBg = '#f5f3ff';
            $badgeText = 'Artist';
        } elseif ($notification->type === 'customer') {
            $themeColor = '#ef4444'; // Red
            $themeBg = '#fee2e2';
            $badgeText = 'Customer';
        } elseif ($notification->type === 'system') {
            $themeColor = '#4b5563'; // Gray
            $themeBg = '#f3f4f6';
            $badgeText = 'System';
        }

        // Action dashboard link setup
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = rtrim($frontendUrl, '/') . '/' . ltrim($notification->link ?? '', '/');
    @endphp

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05); border: 1px border-gray-100;">
                    <!-- Header Accent Bar -->
                    <tr>
                        <td height="6" style="background-color: {{ $themeColor }};"></td>
                    </tr>

                    <!-- Main Body Card -->
                    <tr>
                        <td style="padding: 40px 35px 35px 35px;">
                            <!-- Header branding -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 25px;">
                                <tr>
                                    <td>
                                        <span style="font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8;">ARTIST LINK PLATFORM</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Title -->
                            <h1 style="margin: 0 0 15px 0; font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.25;">
                                {{ $notification->title }}
                            </h1>

                            <!-- Category Badge -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td style="background-color: {{ $themeBg }}; color: {{ $themeColor }}; padding: 6px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                        {{ $badgeText }}
                                    </td>
                                </tr>
                            </table>

                            <!-- Message Content -->
                            <div style="background-color: #f8fafc; border-left: 4px solid {{ $themeColor }}; padding: 18px 20px; border-radius: 0 8px 8px 0; margin-bottom: 30px;">
                                <p style="margin: 0; font-size: 15px; color: #334155; line-height: 1.6; font-weight: 500;">
                                    {{ $notification->message }}
                                </p>
                            </div>

                            <!-- CTA Button -->
                            @if($notification->link)
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 10px; margin-bottom: 15px;">
                                    <tr>
                                        <td align="center">
                                            <a href="{{ $actionUrl }}" target="_blank" style="display: inline-block; background-color: {{ $themeColor }}; color: #ffffff; padding: 14px 30px; border-radius: 12px; font-size: 14px; font-weight: 700; text-decoration: none; text-align: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); transition: background-color 0.2s;">
                                                View Action Dashboard &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 0 35px 35px 35px; background-color: #ffffff;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-t: 1px solid #f1f5f9; padding-top: 25px;">
                                <tr>
                                    <td align="center" style="font-size: 11px; color: #94a3b8; line-height: 1.5; text-align: center;">
                                        <p style="margin: 0 0 8px 0; font-weight: 600;">You received this email because you are a registered administrator on the platform.</p>
                                        <p style="margin: 0;">&copy; {{ date('Y') }} Artist Link. All rights reserved.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
