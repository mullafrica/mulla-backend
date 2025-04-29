<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="background-color: #eeeeee; color:black; margin: 0; padding: 0;">
    <div
        style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; padding: 0; background-color: #eeeeee;">
        <div style="max-width: 600px; margin: 40px auto; background-color: #ffffff; box-sizing: border-box;">

            <header style="padding: 2.5rem; display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 1;">
                    <img src="https://awstestbucket-pk.s3.eu-west-1.amazonaws.com/logo-black.png" alt="Mulla Africa Logo"
                        style="width: 130px;" />
                </div>
                {{-- <div style="font-size: 12px; text-align: right; flex: 1; display: flex; justify-content: flex-end; align-items: center;">
                    <span>Receipt</span>
                </div> --}}
            </header>

            <div>
                <img src="https://default-wp.s3.eu-west-1.amazonaws.com/Other/Asset+12.png" alt="Header Image"
                    style="width: 100%; display: block;" />
            </div>

            <div
                style="padding-right: 2.5rem; padding-left: 2.5rem; padding-bottom: 2.5rem; margin-top: 40px; background-color: #fff; box-sizing: border-box; border-radius: 0 0 0.75rem 0.75rem;">
                <div style="padding-bottom: 2rem; font-size: 15px;">Hello {{ $firstname ?? '' }},</div>
                @if ($utility)
                    <div style="padding-bottom: 2rem; font-size: 15px;">Your {{ $utility ?? '' }} Purchase was
                        successful.
                @endif

                @if ($transfer)
                    <div style="padding-bottom: 2rem; font-size: 15px;">Your bank transfer to {{ $transfer ?? '' }} was
                        successful.
                @endif
            </div>

            <div style="padding-bottom: 2rem; font-size: 15px;">
                <div style="padding-bottom: 1.5rem;"><b>Date:</b> {{ $date ?? '' }}</div>
                <div style="padding-bottom: 1.5rem;"><b>Amount:</b> {{ $amount ?? '' }} NGN</div>

                @if ($cashback)
                    <div style="padding-bottom: 1.5rem;"><b>Cashback Earned:</b> {{ $cashback ?? '' }} NGN</div>
                @endif

                @if (!$token && $code)
                    <div style="padding-bottom: 1.5rem;"><b>Voucher Code:</b> {{ $code ?? '' }}</div>
                @endif

                @if ($serial)
                    <div style="padding-bottom: 1.5rem;"><b>Voucher Serial:</b> {{ $serial ?? '' }}</div>
                @endif

                @if ($device_id)
                    <div style="padding-bottom: 1.5rem;"><b>Device Identifier:</b> {{ $device_id ?? '' }}</div>
                @endif

                @if ($token)
                    <div style="padding-bottom: 1.5rem;"><b>Token:</b> {{ $token ?? '' }}</div>
                @endif

                @if ($units)
                    <div style="padding-bottom: 1.5rem;"><b>Units:</b> {{ $units ?? '' }}</div>
                @endif

                <div><b>Transaction Reference:</b>
                    {{ $transaction_reference ?? '' }}</div>
            </div>

            <div style="padding-bottom: 2rem; font-size: 15px;">
                <a href="https://app.mulla.money/dashboard/transactions" class="button"
                    style="display: block; width: 100%; background-color: #03F8C5; padding: 1.5rem; text-align: center; color: #000; font-size: 1.25rem; font-weight: 600; text-decoration: none; border-radius: 0.75rem; box-sizing: border-box;">
                    View your Transactions
                </a>
            </div>

            <div style="padding-bottom: 2rem; font-size: 15px;">
                Love and rewards,
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
