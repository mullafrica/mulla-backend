{{-- <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email</title>
</head>

<body style="margin: 0; padding: 0; background-color: #e1e1e1; font-family: Arial, sans-serif;">
    <div style="display: grid; min-height: 100vh; background-color: #f0f0f0; padding: 20px;">
        <div style="display: grid; grid-gap: 2rem; justify-self: center; align-self: start; background-color: #fff; padding: 2.5rem; width: 100%; max-width: 700px; box-sizing: border-box; border-radius: 0.75rem;">
            <div>
                <img src="https://ff2eb64284efdb303548adc4cf12423fe042f3a1832c7a8dcec35fe-apidata.googleusercontent.com/download/storage/v1/b/mulla/o/logo-black.png?jk=ASOpP9jOgL2jzBa-claKFVUlzroqGGcdFv4YJBy0eI6B38kLUzgT4Gv7aekfqiMXt2KVV1c1TOCy3NmVDSnjq5BAXFf5vz81THsSRs3QZQTFl_1ugMjOVPdKKW7ya0Vvsmnzzju7B7dTE5rSb4pza6g8ONoegJNI1x2ifkEFMnNQdJc2V0wiFNDm4Q-MrBK1FMrViOU5h_ty842cjcOYFwSSU2jAe5IihtD_apqUNv_y96CwfGixxQHWpbO2HMWdDnE4fC4MnuaPVAfDSsDySqM2WNOnvF3QiiOLI4PIJjcLYBV5Jzg1puWxBdM7WoaB07G6kORC2Q7DwRDk7v1bSrJ1EkZI92PS2nSBQuAN6szydHt7ij3sBWwGaOMp3I2DJp6KOCmx4DJgoI7mJls0fBLMYALT0QR7MV0R9GeIoPKLwdcPWJ-zqjwK1POczLRa_d4oIyOq4tWsbw7RZ5LldymNRlPpSrgyFjcUz3GESGpbrzwtyy_YIQcIROKKmwTIEKJXdEPgeiDS8XalLgADMKix--DbSexpyAB3zqq34SazeIVMcal1Kk77JHZaJj4HhJ98iRJdtFKQ00_vgyPIRnOsi4McLj3vUTgB0VUm0I-fhaPll3CNGpLMZZEgUC5_u53NlrdIjRAX_J1jfdkCMLIZQeV4S3lq87UhhQkto__KeKmczEhvPagPrdYT665fGWm-f7wponP0jCeJFeiz4Uce_4Ai32248Snf9Z4hRjov5BNjRO8297tuXcea5mqi3FnGA-UPV1dcTqHp4lEJe_F3O0ykidvoh_3cReVKrRZCmhfeowELs1Bg5XjkJ5Cn9nEYd0BPYNvfNsBmpf4CBxVLS1Y09HfqjIbsi6RcPr9oaqUH2NEeeq82wMTbOn-sMTiE7_2tiRQ9vypMBMI5lCL0qif6AQbA6aXwoB-HaXwJn7O3_QxXAXc6IGw5iTtzjhaTE6iwnBgT9fmIJe5jXdCd8UjhXzL1xITflWgFiCc3-QYFA8bzIol7Tz224fnhLn0ZWuocw0BkAE85ZHNYUQPTVLgOOFjN1ULVYzMD89_o1uHKUIFg52W414iSIQPrdtbcSBcJq4I5SKbTeAd3KOjuFYcwR35RQddUCuDQQfRYkAZLBkHZqChqUx6JkLqmTPOprMl-taNrrPwjzuxVnKfFUgyn4RCgZ2e5WYhg0CkjnL09k1DMWxJumIYnSBY-lorGaqP68xUMyQK1XhKgAMMLzbBgclLyLWfRgne8_Si3GR0MvvtZC-MRWjAZGHlo2VGLqEMC2H0NBDMwKKAntIr9JRbd14LmFSvAiKG4V6EPvz5sKFaeurxl5DRT_Z8&isca=1" alt="Mulla Africa Logo" style="width: 120px; margin-bottom: 20px;">
            </div>

            <div>{{ $firstname ?? '' }},</div>
            <div>Enter this 6-digit code to verify it's really you on the other end of this.</div>
            <div style="font-size: 30px;">{{ $token ?? '' }}</div>
            <div>This code expires in 5 minutes.</div>
            <div>If you did not request this password reset code, we recommend that you change your Mulla password immediately by using this <a href="https://mulla.africa" style="color: inherit; text-decoration: underline;">link</a>.</div>
            <div>For your account safety, please do not forward this email or provide the details of this email to anyone.</div>
            <div>Thank you for helping us keep your account secure.<br />Your Friends at Mulla.</div>
            <div>Something not looking right? Please reply to this email or contact us at <a href="mailto:support@mulla.africa" style="color: inherit; text-decoration: underline;">support@mulla.africa</a> right away.</div>
        </div>

        <div style="display: grid; font-size:12px; justify-self: center; align-self: start; padding: 2.5rem; width: 100%; max-width: 700px; box-sizing: border-box; border-radius: 0.75rem;">
            &copy; 2024. Mulla Africa. <br /> The everyday bill payments app for Africans.
            <br /><br />
            <div style="display: flex; justify-self: left; align-items: left; gap: 0.5rem;">
                <div style="cursor: pointer;">
                    <a href="https://twitter.com/mullaafrica" target="_blank" rel="noopener noreferrer">
                        <svg width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg" style="stroke: #141B34; stroke-width: 1.5; stroke-linejoin: round;">
                            <path d="M1 15.5C2.76504 16.521 4.81428 17 7 17C13.4808 17 18.7617 11.8625 18.9922 5.43797L21 1.5L17.6458 2C16.9407 1.37764 16.0144 1 15 1C12.4276 1 10.5007 3.51734 11.1209 5.98003C7.56784 6.20927 4.34867 4.0213 2.48693 1.10523C1.25147 5.30185 2.39629 10.3561 5.5 13.4705C5.5 14.647 2.5 15.3488 1 15.5Z"/>
                        </svg>
                    </a>
                </div>
               
                <div style="cursor: pointer;">
                    <a href="https://www.linkedin.com/company/mulla-africa/" target="_blank" rel="noopener noreferrer">
                        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" style="stroke: #141B34; stroke-width: 1.5; stroke-linejoin: round;">
                            <path d="M6 9V16" stroke="#141B34" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M10 12V16M10 12C10 10.3431 11.3431 9 13 9C14.6569 9 16 10.3431 16 12V16M10 12V9" stroke="#141B34" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6.00801 6L5.99902 6" stroke="#141B34" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M1.5 11C1.5 6.52166 1.5 4.28249 2.89124 2.89124C4.28249 1.5 6.52166 1.5 11 1.5C15.4783 1.5 17.7175 1.5 19.1088 2.89124C20.5 4.28249 20.5 6.52166 20.5 11C20.5 15.4783 20.5 17.7175 19.1088 19.1088C17.7175 20.5 15.4783 20.5 11 20.5C6.52166 20.5 4.28249 20.5 2.89124 19.1088C1.5 17.7175 1.5 15.4783 1.5 11Z" stroke="#141B34" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
                <div style="cursor: pointer;">
                    <a href="https://www.instagram.com/mulla.africa/" target="_blank" rel="noopener noreferrer">
                        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" style="stroke: #141B34; stroke-width: 1.5; stroke-linejoin: round;">
                            <path d="M1.5 11C1.5 6.52166 1.5 4.28249 2.89124 2.89124C4.28249 1.5 6.52166 1.5 11 1.5C15.4783 1.5 17.7175 1.5 19.1088 2.89124C20.5 4.28249 20.5 6.52166 20.5 11C20.5 15.4783 20.5 17.7175 19.1088 19.1088C17.7175 20.5 15.4783 20.5 11 20.5C6.52166 20.5 4.28249 20.5 2.89124 19.1088C1.5 17.7175 1.5 15.4783 1.5 11Z"/>
                            <path d="M15.5 11C15.5 13.4853 13.4853 15.5 11 15.5C8.51472 15.5 6.5 13.4853 6.5 11C6.5 8.51472 8.51472 6.5 11 6.5C13.4853 6.5 15.5 8.51472 15.5 11Z"/>
                            <path d="M16.5078 5.5L16.4988 5.5" stroke="#141B34" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html> --}}


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- <title>Simple Email Template</title> --}}
    <style>
        body {
            font-family: sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, Helvetica, sans-serif;
            /* Fallback fonts for Manrope */
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

<body
    style="margin-top:40px; margin-bottom:40px; padding: 0; background-color: #eeeeee; font-family: Arial, sans-serif;">
    <div class="container" style="display: grid; grid-gap: 4rem;">
        <header>
            <div>
                <img src="https://awstestbucket-pk.s3.eu-west-1.amazonaws.com/logo-black.png" alt="Mulla Africa Logo"
                    style="width: 130px;" />
            </div>
        </header>

        {{-- <main>
            <p>Here's your token:</p>

            <p style="font-size: 30px;">{{ $token ?? '' }}</p>

            <p style="font-size: 10px;">This expires in 5mins</p>
        </main> --}}

        {{-- <div
            style="display: grid; grid-gap: 2rem; justify-self: center; align-self: start; background-color: #fff; box-sizing: border-box; border-radius: 0.75rem;">
            <div>{{ $firstname ?? '' }},</div>
            <div>Enter this 6-digit code to verify it's really you on the other end of this.</div>
            <div style="font-size: 30px;">{{ $token ?? '' }}</div>
            <div>This code expires in 5 minutes.</div>
            <div>If you did not request this password reset code, we recommend that you change your Mulla password
                immediately by using this <a href="https://mulla.africa"
                    style="color: inherit; text-decoration: underline;">link</a>.</div>
            <div>For your account safety, please do not forward this email or provide the details of this email to
                anyone.</div>
            <div>Thank you for helping us keep your account secure.<br />Your Friends at Mulla.</div>
            <div>Something not looking right? Please reply to this email or contact us at <a
                    href="mailto:support@mulla.africa"
                    style="color: inherit; text-decoration: underline;">support@mulla.africa</a> right away.</div>

            <div style="font-size: 12px; display: grid; grid-gap: 20%; margin-top:35px;">
                <div>&copy; 2024. Mulla Africa.</div>
                <div>The everyday bill payments app for Africans.</div>
            </div>
        </div> --}}

            <div
                style="justify-self: center; align-self: start; background-color: #fff; box-sizing: border-box; border-radius: 0.75rem;">
                <div style="padding-bottom: 2rem;">{{ $firstname ?? '' }},</div>
                <div style="padding-bottom: 2rem;">Enter this 6-digit code to verify it's really you on the other end of
                    this.</div>
                <div style="font-size: 30px; padding-bottom: 2rem;">{{ $token ?? '' }}</div>
                <div style="padding-bottom: 2rem;">This code expires in 5 minutes.</div>
                <div style="padding-bottom: 2rem;">If you did not request this password reset code, we recommend that
                    you change your Mulla password
                    immediately by using this <a href="https://mulla.africa"
                        style="color: inherit; text-decoration: underline;">link</a>.</div>
                <div style="padding-bottom: 2rem;">For your account safety, please do not forward this email or provide
                    the details of this email to
                    anyone.</div>
                <div style="padding-bottom: 2rem;">Thank you for helping us keep your account secure.<br />Your Friends
                    at Mulla.</div>
                <div style="padding-bottom: 2rem;">Something not looking right? Please reply to this email or contact us
                    at <a href="mailto:support@mulla.africa"
                        style="color: inherit; text-decoration: underline;">support@mulla.africa</a> right away.</div>

                <div style="font-size: 12px; margin-top: 35px;">
                    <div style="padding-bottom: 10px;">&copy; 2024. Mulla Africa.</div>
                    <div>The everyday bill payments app for Africans.</div>
                </div>
            </div>
    </div>
</body>

</html>
