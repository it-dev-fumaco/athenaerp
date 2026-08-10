@php
    $sentAt = \Carbon\Carbon::parse($sentAt);
    $dateReserve = \Carbon\Carbon::parse($dateReserve)->format('d F Y');
    $validUntilFormatted = \Carbon\Carbon::parse($validUntil)->format('d F Y');
    $dayLabel = $daysUntilExpiry === 1 ? 'in 1 day' : "in {$daysUntilExpiry} days";
    $year = $sentAt->format('Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Reservation Alert</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1a2b4a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f6f8;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;width:100%;background-color:#ffffff;border-collapse:collapse;overflow:hidden;">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#0b2a5b;padding:22px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="left" valign="middle" style="color:#ffffff;">
                                        <div style="font-size:18px;font-weight:700;letter-spacing:0.5px;line-height:1.3;">STOCK RESERVATION ALERT</div>
                                        <div style="font-size:13px;font-weight:400;margin-top:4px;opacity:0.95;">Stock Reserved is About to Expire</div>
                                    </td>
                                    <td align="right" valign="middle" style="color:#ffffff;white-space:nowrap;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="right">
                                            <tr>
                                                <td valign="middle" style="padding-right:10px;">
                                                    <div style="width:34px;height:34px;border-radius:50%;border:2px solid #ffffff;text-align:center;line-height:34px;font-size:16px;color:#ffffff;">&#128276;</div>
                                                </td>
                                                <td valign="middle" style="color:#ffffff;font-size:12px;line-height:1.35;text-align:left;">
                                                    {{ $sentAt->format('d F Y') }}<br>
                                                    {{ $sentAt->format('h:i A') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Alert summary --}}
                    <tr>
                        <td style="background-color:#dceaf8;padding:22px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="56" valign="middle" style="padding-right:16px;">
                                        <div style="width:48px;height:48px;border-radius:50%;background-color:#0b2a5b;text-align:center;line-height:48px;font-size:22px;color:#ffffff;">&#9203;</div>
                                    </td>
                                    <td valign="middle">
                                        <div style="font-size:16px;font-weight:700;color:#0b2a5b;margin-bottom:6px;">Stock Reserved is About to Expire</div>
                                        <div style="font-size:13px;color:#2c3e50;line-height:1.45;">
                                            The following reserved stock will expire soon. Please take necessary action before the reservation expires.
                                        </div>
                                    </td>
                                    <td width="40" valign="middle" align="right" style="padding-left:12px;font-size:28px;color:#0b2a5b;">&#9888;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Details --}}
                    <tr>
                        <td style="padding:28px 28px 8px 28px;">
                            <div style="font-size:14px;font-weight:700;color:#0b2a5b;letter-spacing:0.4px;margin-bottom:14px;">RESERVED STOCK DETAILS</div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e5e9ef;">
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;width:28px;vertical-align:middle;font-size:16px;color:#5a6a7a;">&#128197;</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;width:160px;"><strong>Date Reserve:</strong></td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;">{{ $dateReserve }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;width:28px;vertical-align:middle;font-size:16px;color:#5a6a7a;">&#128100;</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;"><strong>Sales Person:</strong></td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;">{{ $salesPerson ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;width:28px;vertical-align:middle;font-size:16px;color:#5a6a7a;">&#128100;</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;"><strong>Reserved by:</strong></td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;">{{ $reservedBy ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;width:28px;vertical-align:middle;font-size:16px;color:#5a6a7a;">&#128230;</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;"><strong>Item Code:</strong></td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;">{{ $itemCode }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;width:28px;vertical-align:middle;font-size:16px;color:#5a6a7a;">&#128196;</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;"><strong>Description:</strong></td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;">{{ strip_tags($description ?? '') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;width:28px;vertical-align:middle;font-size:16px;color:#5a6a7a;">&#128197;</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;color:#1a2b4a;"><strong>Valid until date:</strong></td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e9ef;vertical-align:middle;font-size:13px;">
                                        <strong style="color:#d32f2f;">{{ $validUntilFormatted }} ({{ $dayLabel }})</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td style="padding:16px 28px 24px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#e8f1fa;border-radius:4px;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:13px;color:#1a2b4a;line-height:1.4;">
                                        <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background-color:#1e6bb8;color:#ffffff;text-align:center;line-height:18px;font-size:11px;font-weight:700;margin-right:8px;vertical-align:middle;">i</span>
                                        <span style="vertical-align:middle;">Please process the sales order or extend the reservation if needed.</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="border-top:1px solid #e5e9ef;padding:18px 28px 24px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="top" style="font-size:11px;color:#6b7a8c;line-height:1.5;">
                                        <span style="margin-right:4px;">&#9993;</span>
                                        This is an automated message. Please do not reply to this email.<br>
                                        Need help? Contact <a href="mailto:itsupport@fumaco.com" style="color:#1e6bb8;text-decoration:none;font-weight:600;">IT Support</a>.
                                    </td>
                                    <td valign="top" align="right" style="font-size:11px;color:#6b7a8c;white-space:nowrap;padding-left:12px;">
                                        &copy; {{ $year }} All rights reserved.
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
