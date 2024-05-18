<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
        }

        h1,
        h2,
        h3 {
            font-weight: normal;
            margin-bottom: 10px;
        }

        p {
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f5f5f5;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #aaa;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        a {
            color: #333;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>

            <div style="display: flex; align-items: center;">
                <div>
                    <img src="https://awstestbucket-pk.s3.eu-west-1.amazonaws.com/comet.png" width="40px"
                        style="margin-right:4px;" />
                </div>
                <div style="font-size: 1.5rem; margin-top: 4px;">
                    Comet <span style="vertical-align: super; font-size: 0.7rem; font-weight: lighter;">&#174;</span>
                </div>
            </div>

        </header>

        <br />

        <br />

        <main>
            <p>Hello {{ $firstname ?? '' }},</p>

            <p>Thank you for signing up on Mulla - we are excited to have you go on this journey with us.</p>

            <p>No one likes paying bills, not even us, we are simply stuck with them. But you see, making those bill
                payments experiences smoother, rewarding, and ultimately worth it, is why we started Mulla.</p>

            <p>Today, you will be able to make a number payments on our platform:</p>

            <p>&#9889; Electricity to keep the lights on.</p>
            <p>🌐 Airtime and Internet Data to stay connected to the people and things you love online.</p>
            <p>📺 Cable TV Subscriptions to follow your favorite shows.</p>
            <p>💳 Virtual Cards for your online shopping and payment needs.</p>
            <p>🎁 Digital Gift Cards for your most loved global stores.</p>
            <p>🎓 Education Payments.</p>

            <p>For most payments you make on Mulla, you will earn a cashback to make this experience rewarding for you. Our promise is that we will keep adding more services and features that make your life easier. So, if your run into any issues, or there's anything we can do to help, please reach out to us at <a href="mailto:support@mulla.africa">support@mulla.africa</a></p>

            <a href="https://mulla.africa"><button  style="padding-left:25px; font-weight: bold; margin-top:20px; margin-bottom:30px; border:none; color:white; font-size:20px; padding-right:25px; padding-top:20px; padding-bottom:20px; background-color:#007bff;">Get Started on Mulla</button></a>
            
            <p>Love and Rewards 💙💸<br />
            The Mulla Africa Team.</p>
        </main>
    </div>
</body>
</html>

