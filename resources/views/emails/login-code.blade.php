<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Your login code</title>
    </head>
    <body style="margin:0;padding:0;background-color:#050a12;font-family:Arial,Helvetica,sans-serif;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#050a12;padding:32px 16px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background-color:#0d1622;border:1px solid #1e2a3a;border-radius:16px;">
                        <tr>
                            <td style="padding:32px;">
                                <h1 style="margin:0 0 8px;font-size:20px;color:#f8fafc;">Your login code</h1>
                                <p style="margin:0 0 24px;font-size:14px;line-height:1.5;color:#9fb0c7;">
                                    Enter this code to sign in to {{ config('app.name', 'mailist DX') }}. It expires in {{ \App\Services\OtpService::TTL_MINUTES }} minutes.
                                </p>

                                <p style="margin:0 0 24px;padding:16px;background-color:#050a12;border:1px solid #1e2a3a;border-radius:12px;text-align:center;font-size:32px;font-weight:bold;letter-spacing:0.4em;color:#f8fafc;">
                                    {{ $code }}
                                </p>

                                <p style="margin:0;font-size:12px;line-height:1.5;color:#6b7c93;">
                                    If you didn't request this code, you can safely ignore this email.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
