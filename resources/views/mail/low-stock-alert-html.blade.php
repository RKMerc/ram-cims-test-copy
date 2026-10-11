<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RAM-CIMS Inventory Alert</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; color:#1f2933; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px; background:#ffffff; border:1px solid #d9e2ec; border-radius:8px;">
                    <tr>
                        <td style="padding:24px 28px 8px; color:#003B7A; font-size:18px; font-weight:bold;">RAM-CIMS Inventory Alert</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 20px; font-size:15px; line-height:1.5;">
                            <p style="margin:0 0 16px;">Dear {{ config('clinic.alert_greeting') }},</p>
                            <p style="margin:0 0 16px;">RAM-CIMS has detected an inventory item requiring your attention.</p>
                            <p style="margin:0 0 8px;"><strong>Item Name:</strong> {{ $item->GENERIC_NAME }}</p>
                            <p style="margin:0 0 8px;"><strong>Category:</strong> {{ $item->ITEM_CATEGORY }}</p>
                            <p style="margin:0 0 8px;"><strong>Current Stock:</strong> {{ $item->ITEM_QUANTITY }} {{ $item->stockUnit() }}</p>
                            <p style="margin:0 0 8px;"><strong>Automatic Low-Stock Threshold:</strong> {{ $item->stockThreshold() === null ? 'Baseline not recorded' : $item->stockThreshold().' '.$item->stockUnit() }}</p>
                            <p style="margin:0 0 8px;"><strong>Inventory Status:</strong> {{ $status }}</p>
                            <p style="margin:0 0 16px;"><strong>Date and Time:</strong> {{ $detectedAt }}</p>
                            <p style="margin:0 0 16px;">Please review the inventory and arrange replenishment when necessary.</p>
                            <p style="margin:0;">Thank you.</p>
                            <p style="margin:16px 0 0;">RAM-CIMS<br>Asia Pacific College School Clinic</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 28px 20px; color:#627d98; font-size:12px; line-height:1.4;">This is an automated notification. Please do not reply to this email.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
