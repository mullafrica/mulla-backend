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
            margin: 0;
            padding: 0;
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
                    {{-- <svg style="margin-top: 4px; margin-right:4px; width: 40px; fill: currentColor; color: #007bff;"
                        viewBox="0 0 24 24">
                        <path
                            d="M22.602 4.961l1.398 1.414-3.264 3.278c-1.551 1.551-2.535 4.262-2.801 6.44-.529 4.378-4.259 7.907-8.935 7.907-4.971 0-9-4.029-9-9 0-4.668 3.523-8.405 7.906-8.937 2.184-.265 4.889-1.245 6.445-2.801l3.278-3.262 1.414 1.399-3.277 3.277c-1.857 1.858-5.005 3.056-7.618 3.372-3.505.426-6.148 3.414-6.148 6.952 0 3.86 3.141 7 7 7 1.922 0 3.682-.78 4.957-2.055 3.191-3.192.865-7.206 5.365-11.707l3.28-3.277zm1.398-3.547l-1.414-1.414-9.457 9.461 1.414 1.414 9.457-9.461zm-15 19.086c-3.032 0-5.5-2.467-5.5-5.5s2.468-5.5 5.5-5.5 5.5 2.467 5.5 5.5-2.468 5.5-5.5 5.5zm0-9c-1.93 0-3.5 1.57-3.5 3.5s1.57 3.5 3.5 3.5 3.5-1.57 3.5-3.5-1.57-3.5-3.5-3.5zm-1 3.25c0-.414-.336-.75-.75-.75s-.75.336-.75.75.336.75.75.75.75-.336.75-.75zm2 2.25c0-.552-.447-1-1-1s-1 .448-1 1 .447 1 1 1 1-.448 1-1zm1-3c0-.552-.448-1-1-1s-1 .448-1 1 .448 1 1 1 1-.448 1-1z" />
                    </svg> --}}
                    <img src="https://awstestbucket-pk.s3.eu-west-1.amazonaws.com/comet.png" width="40px" style="margin-right:4px;" />
                </div>
                <div style="font-size: 1.5rem; margin-top: 4px;">
                    Comet <span style="vertical-align: super; font-size: 0.7rem; font-weight: lighter;">&#174;</span>
                </div>
            </div>

        </header>

        <br />

        <br />

        <main>
            <p>Here's your token:</p>

            <p style="font-size: 30px;">{{ $token ?? '' }}</p>

            <p style="font-size: 10px;">This expires in 5mins</p>
        </main>
        <footer class="footer">
            <p>Copyright &copy; 2024 Comet</p>
        </footer>
    </div>
</body>

</html>
