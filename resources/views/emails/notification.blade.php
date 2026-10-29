<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ $mail_subject }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0; padding:0; background-color:#f4f4f4; font-family:Arial, Helvetica, sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:20px;">
    <tr>
      <td align="center">

        <!-- Email Container -->
        <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:6px; overflow:hidden;">

          <!-- Header -->
          <tr>
            <td style="background:#e53935; padding:30px; text-align:center;">
              <img src="http://webadmin.worldweb-directory.com/assets/img/logos/logo.png" alt="WWD Logo" style="max-width:140px;">
            </td>
          </tr>

          <!-- Body (admin-edited template HTML) -->
          <tr>
            <td style="padding:30px; color:#333333; font-size:15px; line-height:1.6;">
              {!! $body !!}

              <p style="margin-top:25px;">
                Warm regards,<br>
                <strong>Team WWD</strong><br>
                <span style="color:#666;">Your Business Discovery Partner</span>
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color:#fafafa; padding:15px; text-align:center; font-size:12px; color:#777;">
              © <?php echo date('Y'); ?> WWD. All rights reserved.<br>
              You’re receiving this email because you submitted a business profile on WWD.
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
