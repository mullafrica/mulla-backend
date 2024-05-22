{{-- <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, Helvetica, sans-serif;
            margin-top: 40px;
            margin-bottom: 40px;
        }

        h1,
        h2,
        h3 {
            font-weight: bold;
            margin-bottom: 10px;
        }

        p {
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .container {
            padding: 2.5rem;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-sizing: border-box;
            border-radius: 0.75rem;
        }

        a {
            color: #333;
            text-decoration: none;
        }
    </style>
</head>

<body style="background-color: #eeeeee;">
    <br /> <br />
    <div style="font-family: Arial, sans-serif; padding: 0;">
        <div class="container">
            <header>
                <div>
                    <img src="https://awstestbucket-pk.s3.eu-west-1.amazonaws.com/logo-black.png" alt="Mulla Africa Logo"
                        style="width: 130px;" />
                </div>
            </header>

            <div
                style="margin-top: 50px; justify-self: center; align-self: start; background-color: #fff; box-sizing: border-box; border-radius: 0.75rem;">
                <div style="padding-bottom: 2rem; font-size: 15px;">{{ $firstname ?? '' }},</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">Enter this 6-digit code to verify it's really you on
                    the other end of
                    this.</div>
                <div style="font-size: 30px; padding-bottom: 2rem; font-size: 25px;">{{ $token ?? '' }}</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">This code expires in 5 minutes.</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">If you did not request this password reset code, we
                    recommend that
                    you change your Mulla password
                    immediately by using this <a href="https://mulla.africa"
                        style="color: inherit; text-decoration: underline;">link</a>.</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">For your account safety, please do not forward this
                    email or provide
                    the details of this email to
                    anyone.</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">Thank you for helping us keep your account
                    secure.<br />Your Friends
                    at Mulla.</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">Something not looking right? Please reply to this
                    email or contact us
                    at <a href="mailto:support@mulla.africa"
                        style="color: inherit; text-decoration: underline;">support@mulla.africa</a> right away.</div>

                <div style="font-size: 12px; margin-top: 25px;">
                    <div style="padding-bottom: 8px;">&copy; 2024. Mulla Africa.</div>
                    <div>The everyday bill payments app for Africans.</div>
                </div>
            </div>
        </div>
    </div>
    <br /> <br />
</body>

</html> --}}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;700&display=swap');
    </style>
</head>

<body style="background-color: #eeeeee; font-family: 'Manrope', Arial, sans-serif; margin-top: 40px; margin-bottom: 40px;">
    <br /><br />
    <div style="padding: 0;">
        <div style="padding: 2.5rem; max-width: 600px; margin: 0 auto; background-color: #ffffff; box-sizing: border-box; border-radius: 0.75rem;">
            <header>
                <div>
                    <img src="https://awstestbucket-pk.s3.eu-west-1.amazonaws.com/logo-black.png" alt="Mulla Africa Logo"
                        style="width: 130px;" />
                </div>
            </header>

            <div style="margin-top: 50px; justify-self: center; align-self: start; background-color: #fff; box-sizing: border-box; border-radius: 0.75rem;">
                <div style="padding-bottom: 2rem; font-size: 15px; font-family: 'Manrope', Arial, sans-serif;">{{ $firstname ?? '' }},</div>
                <div style="padding-bottom: 2rem; font-size: 15px; font-family: 'Manrope', Arial, sans-serif;">Enter this 6-digit code to verify it's really you on the other end of this.</div>
                <div style="font-size: 30px; padding-bottom: 2rem; font-size: 25px; font-family: 'Manrope', Arial, sans-serif;">{{ $token ?? '' }}</div>
                <div style="padding-bottom: 2rem; font-size: 15px; font-family: 'Manrope', Arial, sans-serif;">This code expires in 5 minutes.</div>
                <div style="padding-bottom: 2rem; font-size: 15px; font-family: 'Manrope', Arial, sans-serif;">If you did not request this password reset code, we recommend that you change your Mulla password immediately by using this <a href="https://mulla.africa" style="color: #333; text-decoration: underline; font-family: 'Manrope', Arial, sans-serif;">link</a>.</div>
                <div style="padding-bottom: 2rem; font-size: 15px; font-family: 'Manrope', Arial, sans-serif;">For your account safety, please do not forward this email or provide the details of this email to anyone.</div>
                <div style="padding-bottom: 2rem; font-size: 15px; font-family: 'Manrope', Arial, sans-serif;">Thank you for helping us keep your account secure.<br />Your Friends at Mulla.</div>
                <div style="padding-bottom: 2rem; font-size: 15px; font-family: 'Manrope', Arial, sans-serif;">Something not looking right? Please reply to this email or contact us at <a href="mailto:support@mulla.africa" style="color: #333; text-decoration: underline; font-family: 'Manrope', Arial, sans-serif;">support@mulla.africa</a> right away.</div>
                <div style="font-size: 12px; margin-top: 25px; font-family: 'Manrope', Arial, sans-serif;">
                    <div style="padding-bottom: 8px;">&copy; 2024. Mulla Africa.</div>
                    <div>The everyday bill payments app for Africans.</div>
                </div>
            </div>
        </div>
    </div>
    <br /><br />
</body>

</html>
