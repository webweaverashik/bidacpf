<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Verification Code</title>
</head>

<body style="margin:0; padding:0; background-color:#f4f6fa; font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6fa; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0"
                    style="background-color:#ffffff; border-radius:12px; overflow:hidden; max-width:480px; width:100%;">
                    <tr>
                        <td style="background-color:#1b84ff; padding:24px 32px; text-align:center;">
                            <span style="color:#ffffff; font-size:18px; font-weight:bold; letter-spacing:0.3px;">
                                BIDA CPF Management System
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 12px; font-size:16px; color:#1a1a1a;">
                                Hello {{ $userName }},
                            </p>
                            <p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#4b5563;">
                                Use the verification code below to complete your sign-in. This code is required to
                                access your account.
                            </p>

                            <div style="text-align:center; margin:0 0 24px;">
                                <span style="display:inline-block; background-color:#f1f5ff; color:#1b84ff;
                                    font-size:34px; font-weight:bold; letter-spacing:10px; padding:16px 28px;
                                    border-radius:10px;">
                                    {{ $code }}
                                </span>
                            </div>

                            <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                                This code expires in <strong>{{ $expiresInMinutes }} minute(s)</strong>.
                            </p>
                            <p style="margin:0; font-size:13px; color:#6b7280;">
                                If you did not try to sign in, you can safely ignore this email — your account remains
                                secure.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f9fafb; padding:18px 32px; text-align:center;">
                            <span style="font-size:12px; color:#9ca3af;">
                                This is an automated message from the BIDA CPF Management System. Please do not reply.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
