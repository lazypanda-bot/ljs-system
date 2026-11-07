<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Your LJS Realty verification code</title>
    <style>
        @media only screen and (max-width: 480px) {
            .email-content { padding: 28px 24px !important; }
            .email-heading { font-size: 28px !important; }
            .verification-code { font-size: 36px !important; letter-spacing: 6px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f2f5f9; color: #172b42; font-family: Tahoma, Verdana, sans-serif; -webkit-text-size-adjust: 100%;">
    <div style="display: none; max-height: 0; overflow: hidden; opacity: 0; mso-hide: all;">Your login verification code expires in 10 minutes. Enter it in your LJS login window.</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; background-color: #f2f5f9;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <!--[if mso]><table role="presentation" width="560" cellspacing="0" cellpadding="0" border="0"><tr><td><![endif]-->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; max-width: 560px; border: 1px solid #dce4ef; border-radius: 16px; background-color: #ffffff; overflow: hidden;">
                    {{-- Previous blue top accent retained for reference:
                    <tr>
                        <td style="height: 5px; background-color: #1f6cff; font-size: 1px; line-height: 5px;">&nbsp;</td>
                    </tr>
                    --}}
                    <tr>
                        <td class="email-content" style="padding: 30px 36px 24px; border-bottom: 1px solid #e8edf4;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="74" style="width: 74px; padding-right: 16px; vertical-align: middle;">
                                        <img src="{{ $logoSource ?? $message->embed(public_path('images/ljs-logo.jpg')) }}" width="64" height="64" alt="LJS Realty logo" style="display: block; width: 64px; height: 64px; border: 0;">
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <p style="margin: 0 0 5px; color: #172b42; font-size: 16px; font-weight: 700; line-height: 22px;">LJS Realty &amp; Brokerage</p>
                                        <p style="margin: 0; color: #6a7c90; font-size: 11px; line-height: 16px; letter-spacing: 1.5px; text-transform: uppercase;">Account verification</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-content" style="padding: 34px 36px 36px;">
                            <p style="margin: 0 0 12px; color: #1f6cff; font-size: 11px; font-weight: 700; line-height: 18px; letter-spacing: 1.8px; text-transform: uppercase;">One more step</p>
                            <h1 class="email-heading" style="margin: 0 0 16px; color: #172b42; font-family: Georgia, 'Times New Roman', serif; font-size: 34px; font-weight: 400; line-height: 1.2;">Your login code.</h1>
                            {{-- <p style="margin: 0 0 26px; color: #526479; font-size: 14px; line-height: 23px;">Enter this verification code in the LJS login window you already have open.</p> --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; border: 1px solid #d9e5ff; border-radius: 12px; background-color: #f0f5ff;">
                                <tr>
                                    <td align="center" style="padding: 23px 12px 21px;">
                                        <p style="margin: 0 0 10px; color: #526e9a; font-size: 10px; font-weight: 700; line-height: 16px; letter-spacing: 1.8px; text-transform: uppercase;">Verification code</p>
                                        <p class="verification-code" dir="ltr" style="margin: 0; color: #1554cf; font-family: 'Courier New', Courier, monospace; font-size: 44px; font-weight: 700; line-height: 56px; letter-spacing: 8px; white-space: nowrap;">{{ $code }}</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 17px 0 27px; color: #526479; font-size: 12px; line-height: 20px; text-align: center;">This code expires in <strong style="color: #172b42;">10 minutes.</strong></p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; border-top: 1px solid #e8edf4;">
                                <tr>
                                    <td style="padding-top: 22px;">
                                        <p style="margin: 0 0 7px; color: #172b42; font-size: 12px; font-weight: 700; line-height: 20px;">Keep your code private.</p>
                                        <p style="margin: 0; color: #6a7c90; font-size: 12px; line-height: 20px;">Never share this code with anyone. If you did not request it, you can safely ignore this email.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
                {{-- Previous footer outside the card retained for reference:
                <p style="margin: 20px 0 0; color: #8190a3; font-size: 11px; line-height: 18px; text-align: center;">LJS Realty and Brokerage<br>Automatic account verification email</p>
                --}}
            </td>
        </tr>
    </table>
</body>
</html>
