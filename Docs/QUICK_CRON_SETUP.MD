Crons Setup [replace here cronkey with key you have setup in pure_config.php]

Get Updated Coin Pricing [To be shown on Finance Page ,Grabs prices from Coincap run every 10 mins] 

https://yourdomain.com/Queue/cmcUpdate/securecode/cronkey
https://yourdomain.com/Queue/cmcUpdateRate/securecode/cronkey

Get Updated Market Pricing [To be shown on Trade Page and HomePage Grabs prices from Binance run every 5 mins or more] 

https://yourdomain.com/Queue/BinanceUpdate/securecode/cronkey

This Updates market price and creates Orderbook on behalf of price available
https://yourdomain.com/Queue/ExchangeBinanceUpdate/securecode/cronkey


[Optional]ONLY Run if you are running WavesPlatform API [Run every minute and make sure your block is synced]
https://yourdomain.com/Coin/wallet_waves_deposit/securecode/cronkey


[Optional]ONLY Run if you are running Coinpayments.net API [Every 10 mins or more]
https://yourdomain.com/Coin/wallet_coinpay_deposit/securecode/cronkey
https://yourdomain.com/Coin/wallet_coinpay_withdraw/securecode/cronkey


[Optional]ONLY Run if you are running Block.net API [10 mins or more]
https://yourdomain.com/Coin/wallet_blockio_deposit/securecode/cronkey
https://yourdomain.com/Coin/wallet_blockio_withdraw/securecode/cronkey

[Optional]Bitcoin and Bitcoin Type [every 5 mins]
https://yourdomain.com/Coin/deposit_btctype/securecode/cronkey

[Optional]ONLY EVM Type [BNB, ETH etc][Depends upon txs:Usual 5 mins]
https://yourdomain.com/Coin/esmart_deposit/securecode/cronkey/chain/bnb

[Tool for Esmart to main]
https://yourdomain.com/Coin/esmart_to_main/securecode/cronkey/coin/coinsymbol

[Tool for Tron Move TRX to main]
https://yourdomain.com/Coin/esmart_token_to_main/securecode/cronkey/coinname/tokensymbol


[Optional]Tron/TRC20/TRC10 [Every 1 min]
https://yourdomain.com/Tron/cronDeposits/securecode/cronkey

[Tool for Tron Move TRX to main]
https://yourdomain.com/Tron/moveTronToMain/securecode/cronkey

[Tool for Tron Move TRX to main]
https://yourdomain.com/Tron/moveTokenToMain/securecode/cronkey/token/tokensymbol


[Optional]ONLY XRP [Usages Public Network]
https://yourdomain.com/Coin/xrpdeposit/securecode/cronkey/


[OPTIONAL:Investbox]ONLY if Investment Release Cron 
Cron will check any matured investment and release it compare to current time
https://yourdomain.com/Queue/checkInvest/securecode/cronkey



[REQUIRED] Other to run 
https://yourdomain.com/Queue/chart_v3ryun10u3n4m3/securecode/cronkey [Every minute for selfengine or local liquidity]

https://yourdomain.com/Queue/send_notifications/securecode/cronkey [Sends deposit and other notification : set for every min]


[CAUTION: DO NOT RUN UNTIL YOU UNDERSTAND IT] Move Balance to Cold wallet 
RISK : MAKE SURE YOU CONFIGURE COLDWALLETS CORRECTLY IN other_config.php before running this cron
https://yourdomain.com/Queue/move2cold/securecode/cronkey


[Fix Command]
FIX : Agree when you run this command : It creates missing coin fields in codono_user_coin table 
https://yourdomain.com/Queue/fix_user_coin/securecode/cronkey

/* Usages Experimental  If you need to run a cron continues or in duration of few seconds */

Running a cron using using command Line

Goto your ssh 

Find your php path -> also mentioned in pure_config

command syntax
