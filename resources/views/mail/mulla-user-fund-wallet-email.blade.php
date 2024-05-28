<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="background-color: #eeeeee; color:black; margin: 0; padding: 0;">
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; padding: 0; background-color: #eeeeee;">
        <div style="max-width: 600px; margin: 40px auto; background-color: #ffffff; box-sizing: border-box;">

            <header style="padding: 2.5rem; display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 1;">
                    <img src="https://awstestbucket-pk.s3.eu-west-1.amazonaws.com/logo-black.png" alt="Mulla Africa Logo" style="width: 130px;" />
                </div>
                {{-- <div style="font-size: 12px; text-align: right; flex: 1; display: flex; justify-content: flex-end; align-items: center;">
                    <span>Receipt</span>
                </div> --}}
            </header>

            <div style="">
                <img src="https://default-wp.s3.eu-west-1.amazonaws.com/Other/Asset+12.png" alt="Header Image" style="width: 100%; display: block;" />
            </div>

            <div style="padding-right: 2.5rem; padding-left: 2.5rem; padding-bottom: 2.5rem; margin-top: 40px; background-color: #fff; box-sizing: border-box; border-radius: 0 0 0.75rem 0.75rem;">
                <div style="padding-bottom: 2rem; font-size: 15px;">Hi {{ $firstname ?? '' }},</div>
                <div style="padding-bottom: 2rem; font-size: 15px;">Your Mulla wallet has been successfully funded.</div>

                <div style="padding-bottom: 2rem; font-size: 15px;">
                    <div style="padding-bottom: 1.5rem;"><b>Amount:</b> {{ $amount ?? '' }}</div>
                    <div style="padding-bottom: 1.5rem;"><b>Fee:</b> {{ $fee ?? '' }}</div>
                    <div style="padding-bottom: 1.5rem;"><b>Sender:</b> {{ $sender ?? '' }}</div>
                    <div style="padding-bottom: 1.5rem;"><b>Bank:</b> {{ $bank ?? '' }}</div>
                    <div style="padding-bottom: 1.5rem;"><b>Transaction Reference:</b> {{ $transaction_reference ?? '' }}</div>
                    <div style="padding-bottom: 1.5rem;"><b>Description:</b> {{ $description ?? '' }}</div>
                    <div style="padding-bottom: 1.5rem;"><b>Date:</b> {{ $date ?? '' }}</div>
                    <div style="padding-bottom: 1.5rem;"><b>Status:</b> {{ $status ?? '' }}</div>
                </div>

                <div style="padding-bottom: 2rem; font-size: 15px;">
                    <a href="https://mulla.africa" class="button" style="display: block; width: 100%; background-color: #03F8C5; padding: 1.5rem; text-align: center; color: #000; font-size: 1.25rem; font-weight: 600; text-decoration: none; border-radius: 0.75rem; box-sizing: border-box;">
                        Sign in to your Mulla Account
                    </a>
                </div>

                <div style="padding-bottom: 2rem; font-size: 15px;">
                    Experiencing an issue, or believe you are getting this notification in error, please reply to this email or reach us at <a href="mailto:support@mulla.africa" style="color: inherit; text-decoration: inherit;">support@mulla.africa</a>
                </div>

                <div style="padding-bottom: 2rem; font-size: 15px;">
                    Love and rewarding payments experiences,
                    <br>
                    Your Friends at Mulla.
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