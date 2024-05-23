<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="background-color: #eeeeee; margin: 0; color:black; padding: 0;">
    <div
        style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; padding: 0; background-color: #eeeeee;">
        <div
            style="padding: 2.5rem; max-width: 600px; margin: 40px auto; background-color: #ffffff; box-sizing: border-box;">
            <header style="border-bottom: 0.05px solid #e2e2e2; padding-bottom: 2.5rem;">
                <div>
                    <img src="https://awstestbucket-pk.s3.eu-west-1.amazonaws.com/logo-gray.png" alt="Mulla Africa Logo"
                        style="width: 130px;" />
                </div>
            </header>

            <div
                style="margin-top: 40px; background-color: #fff; box-sizing: border-box; border-radius: 0 0 0.75rem 0.75rem;">
                <div style="padding-bottom: 2rem; font-size: 15px;">Hi {{ $firstname ?? '' }},</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">A login attempt has been made on your Mulla account.</div>
                {{-- <div style="padding-bottom: 2rem; font-size: 15px;">We want to believe this is you, but to be absolutely
                    certain, please let us know to help us keep your account secure:</div> --}}
                <div style="padding-bottom: 2rem; font-size: 15px;">
                    <div><b>When:</b> {{ $datetime ?? '' }}</div>
                    <div><b>What:</b> {{ $browser ?? '' }} on {{ $os ?? '' }}</div>
                    <div><b>Approximate Location:</b> {{ $location ?? '' }}</div>
                </div>

                <div style="padding-bottom: 2rem; font-size: 15px;">
                    Something not looking right? Please reply to this email or contact us at <a
                        href="mailto:support@mulla.africa"
                        style="color: inherit; text-decoration: underline;">support@mulla.africa</a> right away.
                </div>

                <div style="padding-bottom: 2rem; font-size: 15px;">Thank your for helping us keep you secure, <br />
                    Your Friends at Mulla.</div>

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
