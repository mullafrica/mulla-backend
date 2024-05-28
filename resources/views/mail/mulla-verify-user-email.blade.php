<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="background-color: #eeeeee; color:black; margin: 0; padding: 0;">
    <div
        style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; padding: 0; background-color: #eeeeee;">
        <div
            style="padding: 2.5rem; max-width: 600px; margin: 40px auto; background-color: #ffffff; box-sizing: border-box;">
            <header style="border-bottom: 0.05px solid #e2e2e2; padding-bottom: 2.5rem;">
                <div>
                    <img src="https://awstestbucket-pk.s3.eu-west-1.amazonaws.com/logo-black.png"
                        alt="Mulla Africa Logo" style="width: 130px;" />
                </div>
            </header>

            <div
                style="margin-top: 40px; background-color: #fff; box-sizing: border-box; border-radius: 0 0 0.75rem 0.75rem;">
                <div style="padding-bottom: 2rem; font-size: 15px;">{{ $firstname ?? '' }},</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">Please enter the following 6-digit code to verify
                    your email address.</div>
                <div style="font-size: 30px; padding-bottom: 2rem;">{{ $token ?? '' }}</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">This code expires in 5 minutes.</div>
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
