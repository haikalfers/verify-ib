<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kode Verifikasi Login</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding:40px 0;">
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="max-width:500px;background:#ffffff;border-radius:12px;padding:30px;">
                
                <!-- HEADER -->
                <tr>
                    <td align="center" style="padding-bottom:20px;">
                        <h2 style="margin:0;color:#0f172a;">Portal Verifikasi Sertifikat</h2>
                        <p style="margin:5px 0 0;color:#64748b;font-size:14px;">
                            PT Indo Bismar
                        </p>
                    </td>
                </tr>

                <!-- BODY -->
                <tr>
                    <td style="color:#0f172a;font-size:15px;line-height:1.6;">
                        <p>Halo Admin,</p>

                        <p>
                            Berikut adalah <strong>kode verifikasi login admin</strong> Anda:
                        </p>

                        <!-- OTP BOX -->
                        <div style="margin:30px 0;text-align:center;">
                            <span style="
                                display:inline-block;
                                background:#f1f5f9;
                                color:#4f46e5;
                                font-size:32px;
                                font-weight:bold;
                                letter-spacing:6px;
                                padding:15px 30px;
                                border-radius:10px;
                            ">
                                {{ $code }}
                            </span>
                        </div>

                        <p>
                            Jangan bagikan kode ini kepada siapa pun demi keamanan akun Anda.
                        </p>

                        <p style="font-size:13px;color:#64748b;">
                            Kode ini hanya berlaku selama <strong>15 menit</strong>.
                        </p>

                        @php
                            $ctx = $context ?? [];
                        @endphp

                        <!-- SECURITY INFO -->
                        <div style="margin-top:24px;padding:14px 16px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                            <p style="margin:0 0 8px;font-size:14px;font-weight:bold;color:#0f172a;">
                                Informasi Keamanan
                            </p>
                            <p style="margin:0 0 10px;font-size:13px;color:#334155;line-height:1.6;">
                                Jika Anda tidak merasa mencoba login, abaikan email ini dan segera periksa keamanan akun Anda.
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;color:#0f172a;">
                                <tr>
                                    <td style="padding:6px 0;color:#64748b;width:140px;">Alamat IP</td>
                                    <td style="padding:6px 0;">{{ $ctx['ip'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#64748b;">Perangkat / Browser</td>
                                    <td style="padding:6px 0;">{{ $ctx['user_agent'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#64748b;">Lokasi Perkiraan</td>
                                    <td style="padding:6px 0;">{{ $ctx['location'] ?? 'Tidak tersedia' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#64748b;">Waktu Login</td>
                                    <td style="padding:6px 0;">{{ $ctx['login_time'] ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>

                        <p style="margin-top:30px;">
                            Salam,<br>
                            <strong>Tim Verify Indobismar</strong>
                        </p>
                    </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                    <td align="center" style="padding-top:30px;font-size:12px;color:#94a3b8;">
                        © {{ date('Y') }} PT Indo Bismar. All rights reserved.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
