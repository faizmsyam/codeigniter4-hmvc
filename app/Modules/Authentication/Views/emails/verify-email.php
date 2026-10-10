<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Email</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,Helvetica,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:30px 15px">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1)">
          <!-- Header -->
          <tr>
            <td style="background-color:#4f46e5;padding:30px 40px;text-align:center">
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:bold"><?php echo esc($brandName); ?></h1>
              <p style="margin:8px 0 0;color:#c7d2fe;font-size:14px">Verifikasi Alamat Email</p>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding:40px">
              <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.6">
                Halo <strong><?php echo esc($recipientName); ?></strong>,
              </p>
              <p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.6">
                Kami menerima permintaan untuk membuat atau memulihkan akun Anda. Klik tombol di bawah ini untuk memverifikasi alamat email Anda:
              </p>
              <!-- CTA Button -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="<?php echo esc($verificationUrl); ?>"
                       style="display:inline-block;background-color:#4f46e5;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:6px;font-size:15px;font-weight:bold">
                      Verifikasi Email Saya
                    </a>
                  </td>
                </tr>
              </table>
              <!-- Details -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0 0;background-color:#f9fafb;border-radius:6px">
                <tr>
                  <td style="padding:16px 20px">
                    <p style="margin:0 0 8px;color:#6b7280;font-size:13px">
                      <strong>Berlaku sampai:</strong> <?php echo esc($expiresAt); ?>
                    </p>
                    <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
                      Jika Anda tidak merasa meminta ini, abaikan email ini. Link di atas hanya sekali pakai dan akan hangus setelah masa berlaku habis.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="background-color:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb">
              <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5">
                Email ini dikirim oleh <strong><?php echo esc($brandName); ?></strong>.<br>
                Jangan balas email ini secara langsung.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
