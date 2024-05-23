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
                <div style="padding-bottom: 2rem; font-size: 15px;">Hi {{ $firstname ?? '' }},</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">You have successfully updated your password for your
                    Mulla Account.</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">Thank you for using Mulla, <br /> Your Friends at
                    Mulla.</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">
                    <div><b>Date:</b> {{ $datetime ?? '' }}</div>
                    <div><b>Browser:</b> {{ $browser ?? '' }}</div>
                    <div><b>Operating System:</b> {{ $os ?? '' }}</div>
                    <div><b>Approximate Location:</b> {{ $location ?? '' }}</div>
                </div>

                <div style="padding-bottom: 2rem; font-size: 15px;">
                    Didn't request this change, Be sure to change your password
                    right away. Still need help? please reply to this email or contact us at <a
                        href="mailto:support@mulla.africa"
                        style="color: inherit; text-decoration: underline;">support@mulla.africa</a> right away.
                </div>

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
