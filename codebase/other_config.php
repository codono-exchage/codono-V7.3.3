<?php
//Default currency for system
const SYSTEMCURRENCY = 'USDT'; //caps letter
const DEFAULT_FIAT = 'usd'; //usd, eur ,zar ,gbp

const FIAT_ALLOWED = 1;

const OTC_ALLOWED = 1;

//enable disable trading module
const TRADING_ALLOWED = 1;

//enable disable ICO module
const ICO_ALLOWED = 1;

//enable disable shop module
const SHOP_ALLOWED = 1;

//Dex Module allowed
const DEX_ALLOWED = 1;

//C2c Module allowed
const C2C_ALLOWED = 1;

//Dex Module allowed
const P2P_ALLOWED = 1;

//Dex Module allowed
const FX_ALLOWED = 1;

//Mining Allowed
const POOL_ALLOWED = 1;


//Mining Allowed
const INVEST_ALLOWED = 1;

//Mining Allowed
const VOTING_ALLOWED = 1;

//Margin Trading allowed
const MARGIN_ALLOWED =1;
//Dust coin
const DUST_COIN = 'bnb';
//If trading needs password enabled
const IF_TRADING_PASS = 0;

//allow users to login /signup via Google account
const GOOGLE_LOGIN_ALLOWED =1;

const GOOGLE_CLIENT_ID='xxxxxxxx';
const GOOGLE_CLIENT_SECRET='xxxxxxxxx';

//Etherscan API KEY
const GETH_MODE = 'light'; //light , full
const ETHERSCAN_KEY = 'xxxxxxxxxxx'; //https://etherscan.io/apis
const BSCSCAN_KEY = 'xxxxxxxxxxxxxxxxxxx';
const INFURA_PROJECT_ID = 'xxxxxxxxxxxxxxxxxx';//https://infura.io/

//Enable /disable subscriptions
const ENABLE_SUBS = 1;
const SUBSCRIPTION_PLANS = array(array('id' => 1, 'coin' => "usd", 'duration' => "12", 'price' => '1', 'name' => 'Premium', 'is_popular' => 0, 'discount' => '50', 'condition' => 'For <b>Everyone</b>'), array('id' => 2, 'coin' => "usd", 'duration' => "6", 'price' => '0', 'name' => 'Premium Plus', 'is_popular' => 1, 'discount' => '50', 'condition' => 'Free for First <b>100k users</b>'));

//If trading needs password enabled
const SHOW_TRADING_FEES = 1;

// Enable Disable RECAPTCHA
const RECAPTCHA = 0;
const RECAPTCHA_KEY = 'getown';
const RECAPTCHA_SECRET = 'getown';
// Enable Disable RECAPTCHA

const DEFAULT_MAILER = 'smtpmail'; //Choose between sendgrid,sendinblue,mailgun,mandrill,amazonses,postmark,mailjet,phpmail,smtpmail
const GLOBAL_EMAIL_SENDER = 'nomail@sitenamehere.com'; //Make sure you change this

const MAILGUN_API_KEY = 'key-DDD';
const MAILGUN_DOMAIN = 'https://api.mailgun.net/v3/DDD.DDD';

// MailJet
/* In case of cacert.pem error Read https://codono.com/curl-error-60-ssl-certificate-problem/  */
const MAILJET_PUBLIC_KEY = 'x';
const MAILJET_PRIVATE_SECRET = 'x';

//AMAZONSES
const AMAZONSES_accessKey = "Access Key here";
const AMAZONSES_secretKey = "Secret Key here";
const AMAZONSES_region = "Region here";
const AMAZONSES_verifyPeer = "verifyPeer here";
const AMAZONSES_verifyHost = "verifyHost here";

//MANDRILL_API_KEY
const MANDRILL_API_KEY = "Key here";

//POSTMARK_serverApiToken
const POSTMARK_serverApiToken = "API token here";

//SendGrid
const SENDGRID_API_KEY = "xxxxxxxxxxxxx";

//SEND IN BLUE // Join Here https://www.sendinblue.com/?tap_a=30591-fb13f0&tap_s=269382-3fd2bd
const SENDINBLUE_API_KEY = "xxxxxxxx-x-x";



//Go through Manual Look for coinpayments
const COINPAY_MERCHANT_ID = 'enteryours';
const COINPAY_SECRET_PIN = 'enteryours';

const DEFAULT_KYC = 4; //1 =internal 2=subsum.com 3=shuftipro 4=amlbot

//SumSub.com KYC Info put_a_secret is some strong string you need to place , do not change it often
const SUMSUB_KYC = array('status' => '0', 'mode' => 'test', 'clientid' => 'x', 'username' => 'x', 'password' => 'x', 'put_a_secret' => 'x');

//Authorize.net Settings **Make Sure your clear cache after changing this ** mode value to be live or sandbox
const AUTHORIZE_NET = array("status" => "0", "mode" => "sandbox", "clientkey" => "x", "loginid" => "x", "transactionkey" => "x", "signature" => "x");

//http://www.yoco.com/
const YOCO_GATEWAY = array("status" => "0", "mode" => "sandbox", "secret_key" => "getown", "pub_key" => "getown");

//https://payments.yo.co.ug/ybs/portal/
const YO_Uganda = array("status" => "0", "mode" => "sandbox", "sandbox_USER" => "90008360963", "sandbox_PASS" => "1116694048", "API_USER" => "90008360963", "API_PASS" => "1116694048");


const SOCKET_WS_ENABLE = 1; //This Socket server URL
//const MARKETS_WS_SOCKET = array("btc_usdt" => "btcusdt", "ltc_usd" => "ltcusd"); //This is required to read websockets
const MARKETS_WS_SOCKET = array(); //This is required to read websockets
const SOCKET_WS_URL = "ws://localhost:7272"; //This Socket server URL
// DO NOT ENABLE ABOVE IF YOU DONT KNOW WHAT IS THIS CONSULT CODONO LIVECHAT : This tool is experimental

//ColdWallet Storage Move , Applicable for BTC and ETH based coins
//coin=>address:minkeep:mintransfer
const COLD_WALLET = array("BTC" => "xxxxxxxxxxxxxxx:1.2:0.5");

//Model 1 first asks for OTP then creates account, Model 2 ..Sends Confirmation Link on email then creates account.
const SIGNUP_MODEL = 2;//Suggested value 2


const CHAT_LIMIT_LINES = 100;

//================NO MORE USED 
//For tradingview :https://tradingview.com/HTML5-stock-forex-bitcoin-charting-library/ and replace with your licensed files in /Public/Home/js/tradingview

//Enable Disable Binance Cross Trading
const BINANCE_TESTNET = 0; 
const BINANCE_API_KEY_1 = 'paste-your-key-here';
const BINANCE_API_SECRET_1 = 'paste-your-secret-here';
const BINANCE_API_KEY_2 = 'paste-your-key-here';
const BINANCE_API_SECRET_2 = 'paste-your-secret-here';


//tawk to chat widget 
const TAWK_TO_EMBED_URL = 'https://embed.tawk.to/../xxxxx';
const TAWKTO_API_KEY = '..';

//OVEX.io for OTC_ALLOWED
const OVEX_API_KEY = '..';

//CMC API KEY to Receive UCID from Coinmarketcap , Required for CMC Compatible apis.

const CMC_PRO_API_KEY = 'cmcproapikeyhere';
//https://my.cryptoapis.io/api-keys
const CRYPTOAPIS_KEY = 'xxxxxxxxxxxx';

//do not change it until you are pretty sure
const ALLOWED_TYPES=  ['spot' => 0, 'p2p'=>1, 'staking' => 4, 'stock' => 5];
const USE_STAKING_BALANCE =1;

const THEME_NAME='multiverse'; //epsilon/multiverse