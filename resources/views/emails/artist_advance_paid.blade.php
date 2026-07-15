<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advance Payment Sent</title>
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
@php
    $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
    $artistName  = $booking->artistProfile?->stage_name ?? $booking->artistProfile?->full_name ?? 'Artist';
    $advance     = number_format((float) $booking->advance_amount, 2);
    $totalPrice  = number_format((float) $booking->agreed_price, 2);
    $eventDate   = $booking->event_date ? $booking->event_date->format('d M Y') : 'N/A';
    $eventType   = $booking->event_type ?? 'N/A';
    $orderId     = $booking->payhere_order_id ?? $booking->id;
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f8fafc;padding:40px 0;">
    <tr>
        <td align="center">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:580px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 15px -3px rgba(0,0,0,0.05);border:1px solid #f1f5f9;">

                <!-- Accent Bar -->
                <tr><td height="6" style="background-color:#E8194B;"></td></tr>

                <!-- Body -->
                <tr>
                    <td style="padding:40px 35px 30px 35px;">
                        <!-- Branding -->
                        <p style="margin:0 0 24px 0;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;">PERFORMA PLATFORM</p>

                        <!-- Greeting -->
                        <h1 style="margin:0 0 8px 0;font-size:22px;font-weight:800;color:#0f172a;line-height:1.25;">
                            Your advance payment is on the way! 🎉
                        </h1>
                        <p style="margin:0 0 28px 0;font-size:14px;color:#64748b;line-height:1.6;">
                            Hi {{ $artistName }}, we've sent your advance payment for the booking below.
                        </p>

                        <!-- Payment Amount -->
                        <div style="background:linear-gradient(135deg,#E8194B,#c8133b);border-radius:16px;padding:24px;text-align:center;margin-bottom:28px;">
                            <p style="margin:0 0 4px 0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.75);">ADVANCE PAYMENT SENT</p>
                            <p style="margin:0;font-size:36px;font-weight:900;color:#ffffff;">LKR {{ $advance }}</p>
                        </div>

                        <!-- Booking Details -->
                        <div style="background-color:#f8fafc;border-radius:12px;padding:20px;margin-bottom:28px;">
                            <p style="margin:0 0 14px 0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;">BOOKING DETAILS</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding:6px 0;font-size:13px;color:#64748b;font-weight:500;width:40%;">Order ID</td>
                                    <td style="padding:6px 0;font-size:13px;color:#0f172a;font-weight:700;">#{{ $orderId }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;font-size:13px;color:#64748b;font-weight:500;">Event Date</td>
                                    <td style="padding:6px 0;font-size:13px;color:#0f172a;font-weight:700;">{{ $eventDate }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;font-size:13px;color:#64748b;font-weight:500;">Event Type</td>
                                    <td style="padding:6px 0;font-size:13px;color:#0f172a;font-weight:700;">{{ $eventType }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;font-size:13px;color:#64748b;font-weight:500;">Customer</td>
                                    <td style="padding:6px 0;font-size:13px;color:#0f172a;font-weight:700;">{{ $booking->customer_name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;font-size:13px;color:#64748b;font-weight:500;">Total Agreed</td>
                                    <td style="padding:6px 0;font-size:13px;color:#0f172a;font-weight:700;">LKR {{ $totalPrice }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;font-size:13px;color:#64748b;font-weight:500;">Balance Due</td>
                                    <td style="padding:6px 0;font-size:13px;color:#E8194B;font-weight:700;">
                                        LKR {{ number_format((float)$booking->agreed_price - (float)$booking->advance_amount, 2) }}
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Note -->
                        <div style="border-left:4px solid #E8194B;padding:14px 18px;border-radius:0 8px 8px 0;background-color:#fff5f7;margin-bottom:28px;">
                            <p style="margin:0;font-size:13px;color:#334155;line-height:1.6;">
                                The advance amount has been transferred to your registered bank account. Please allow 1–3 business days for the funds to reflect. The remaining balance will be settled after the event is completed.
                            </p>
                        </div>

                        <!-- CTA -->
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td align="center">
                                    <a href="{{ $frontendUrl }}/bookingRequests" target="_blank"
                                       style="display:inline-block;background-color:#E8194B;color:#ffffff;padding:14px 32px;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;">
                                        View My Bookings &rarr;
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:0 35px 35px 35px;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top:1px solid #f1f5f9;padding-top:24px;">
                            <tr>
                                <td align="center" style="font-size:11px;color:#94a3b8;line-height:1.5;text-align:center;">
                                    <p style="margin:0 0 6px 0;font-weight:600;">You received this because you are a registered artist on Performa.</p>
                                    <p style="margin:0;">&copy; {{ date('Y') }} Performa. All rights reserved.</p>
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
