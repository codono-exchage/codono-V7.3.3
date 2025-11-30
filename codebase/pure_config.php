<?php
/**
 * System with the file
 * All system-level configuration
 */

const SHORT_NAME = 'Codono';
const HOST_IP = '127.0.0.1';
// SITE URL
const SITE_URL = 'http://exchange.local/'; // keep it with trailing slash

// Database Type
const DB_TYPE = 'mysql';
// DB Host
const DB_HOST = '127.0.0.1';
// DB Name
const DB_NAME = 'codonoexchange';
// DB User
const DB_USER = 'root';
// DB PASSWORD
const DB_PWD = '';
//DB PORT Dont change if you are not sure
const DB_PORT = '3306';
// Exchange Goes into DEMO mode if 1 : Let it be 0
const APP_DEMO = 0;

// Keep it 0 only [1 means SMS authentication will be bypassed quite risky]
const MOBILE_CODE = 0;


// Keep it 0 only [1 means MOBILE APP LOGIN is integrated and ready to work]
const MOBILE_LAUNCHED = 1;


const ENABLE_MOBILE_VERIFY = 1;

//SMS Verification is must
const M_ONLY = 0;

//SITE WIDE DEBUGGING ON : Not recommended for Production mode [For production site Put it 0]
const M_DEBUG = 0;

//Admin  Debugging FULL DEBUGGING
const ADMIN_DEBUG =0;

//Show debug window on everypage
const DEBUG_WINDOW =0;

//Turn On KYC on Signup and make optional
const KYC_OPTIONAL = 0;

//If you enforce kyc only people with KYC true will able to Withdraw and trade , So keep it zero
const ENFORCE_KYC = 0;

// Backend Security
const ADMIN_KEY = 'securekey';

// Key to ACCESS CRONS 
const CRON_KEY = 'cronkey';

//Your License Number Or Codono ORDERID
const CODONOLIC = 'XXXXXX';

//NEVER CHANGE ETH_USER_PASS AGAIN .. IF YOU DO, YOUR ETH USER WALLET PASSWORDS WOULD NEVER WORK
const ETH_USER_PASS = 'xxxxxxx'; //YOU CAN CHANGE THIS ONLY ONCE IN LIFE TIME BEFORE USING ETHEREUM



const DIR_SECURE_CONTENT = 'ACCESS DENIED!';
//path of php on your server https://askubuntu.com/questions/1152920/how-can-i-find-the-executable-path-of-php
const PHP_PATH = 'php';

//Redis caching is required
const REDIS_ENABLED = 1; 
//Set this as Redis password 
const REDIS_PASSWORD = 'xxxxxxxxx';