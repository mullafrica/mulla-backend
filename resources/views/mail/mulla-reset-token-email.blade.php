<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="background-color: #eeeeee; margin: 0; padding: 0;">
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; padding: 0; background-color: #eeeeee;">
        <div style="padding: 2.5rem; max-width: 600px; margin: 40px auto; background-color: #ffffff; box-sizing: border-box; border-radius: 0.75rem;">
            <header>
                <div>
                    <img src="https://awstestbucket-pk.s3.eu-west-1.amazonaws.com/logo-black.png" alt="Mulla Africa Logo" style="width: 130px;" />
                </div>
            </header>

            <div style="margin-top: 50px; background-color: #fff; box-sizing: border-box; border-radius: 0.75rem;">
                <div style="padding-bottom: 2rem; font-size: 15px;">{{ $firstname ?? '' }},</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">Enter this 6-digit code to verify it's really you on the other end of this.</div>
                <div style="font-size: 30px; padding-bottom: 2rem;">{{ $token ?? '' }}</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">This code expires in 5 minutes.</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">If you did not request this password reset code, we recommend that you change your Mulla password immediately by using this <a href="https://mulla.africa" style="color: #333; text-decoration: underline;">link</a>.</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">For your account safety, please do not forward this email or provide the details of this email to anyone.</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">Thank you for helping us keep your account secure.<br />Your Friends at Mulla.</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">Something not looking right? Please reply to this email or contact us at <a href="mailto:support@mulla.africa" style="color: #333; text-decoration: underline;">support@mulla.africa</a> right away.</div>
                <div style="font-size: 12px; margin-top: 25px;">
                    <div style="padding-bottom: 8px;">&copy; 2024. Mulla Africa.</div>
                    <div>The everyday bill payments app for Africans.</div>
                </div>
            </div>
        </div>
    </div>
    <br /><br />
</body>
</html>
