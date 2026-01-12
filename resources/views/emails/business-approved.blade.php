<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Congratulations! Your WWD Business Profile Is Approved</title>
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
              <img src="http://webadmin.worldweb-directory.com/assets/img/logos/logo.png" alt="WWD Logo" style="max-width:140px; margin-bottom:15px;">
              <h1 style="color:#ffffff; margin:0; font-size:28px;">Congratulations! 🎉</h1>
              <p style="color:#ffecec; margin:10px 0 0; font-size:16px;">
                Your business profile has been approved on WWD
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:30px; color:#333333; font-size:15px; line-height:1.6;">

              <p><strong>Dear {{ $user->first_name . ' ' . $user->last_name }},</strong></p>

              <p>
                We’re excited to let you know that your submitted business profile for
                <strong>{{ $business->business_name }}</strong> has been successfully reviewed and approved by the WWD team.
              </p>

              <hr style="border:none; border-top:1px solid #eeeeee; margin:25px 0;">

              <h3 style="color:#e53935; margin-top:0;">Complete Your Business Profile</h3>

              <p>
                To get the best visibility and attract more customers, please log in and update the following details:
              </p>

              <ul style="padding-left:18px;">
                <li> Business logo</li>
                <li> Business description</li>
                <li> Business address</li>
                <li> Business hours & working days</li>
                <li> Social media links</li>
              </ul>

              <p>
                A complete profile builds trust, improves local SEO, and helps customers choose your business with confidence.
              </p>

              <!-- CTA Button -->
              <div style="text-align:center; margin:30px 0;">
                <a href="{{route('login')}}" 
                   style="background-color:#e53935; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:4px; font-size:16px; display:inline-block;">
                  Login & Update Your Profile
                </a>
              </div>

             

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
