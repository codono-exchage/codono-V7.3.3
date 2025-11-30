<?php

header('X-Content-Type-Options: nosniff');
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
mb_internal_encoding("UTF-8");
header('Content-Type: text/html; charset=utf-8');

//header('X-Frame-Options: DENY'); // Prevent clickjacking.
//header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; object-src \'none\';'); // Basic CSP.

//header("Content-Security-Policy-Report-Only: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");

session_regenerate_id(true);

use Think\Exception;

if (M_DEBUG == 1) {
    header('X-Powered-By: ' . SHORT_NAME);
}
function getIncidentCode($length = 25) {
    // Generate a random hex code of the specified length
    $hexCode = bin2hex(random_bytes(ceil($length / 2)));
    $hexCode = substr($hexCode, 0, $length);

    // Insert dashes after every 5 characters
    $dashedHexCode = '';
    for ($i = 0; $i < strlen($hexCode); $i += 5) {
        if ($i > 0) {
            $dashedHexCode .= '-';
        }
        $dashedHexCode .= substr($hexCode, $i, 5);
    }

    return strtoupper($dashedHexCode);
}

function cleaninput($input)
{
    $search = array(
        '@<script[^>]*?>.*?</script>@si', // Strip out javascript
        '@<[/!]*?[^<>]*?>@i', // Strip out HTML tags
        '@<style[^>]*?>.*?</style>@siU', // Strip style tags properly
        '@<![\s\S]*?--[ \t\n\r]*>@' // Strip multi-line comments
    );

    return preg_replace($search, '', $input);
}

function sanitize($input)
{
    $input=superSanitize($input);
    $output = [];
    if (is_array($input)) {
        foreach ($input as $var => $val) {
            $output[$var] = sanitize($val);
        }
    } else {
        $input = str_replace('"', "", $input);
        $input = str_replace("'", "", $input);
        $input = cleaninput($input);
        $output = htmlentities($input, ENT_QUOTES);
    }
    return $output;
}

function superSanitize($input){
    if (is_array($input)) {
        return filter_var_array($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }
    return filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
}

function executeSecurity()
{
    $access_ip=hacker_ip();
    $_POST = sanitize($_POST);
    $_GET = sanitize($_GET);
    $_REQUEST = sanitize($_REQUEST);
    $_COOKIE = sanitize($_COOKIE);
    if (isset($_SESSION)) {
        $_SESSION = sanitize($_SESSION);
    }

    $query_string = @$_SERVER['QUERY_STRING'];

    $patterns = array(
        "codono_",
        "union",
        "coockie",
        "cookie",
        "session",
        "concat",
        "alter",
        "table",
        "where",
        "exec",
        "shell",
        "wget",
        "**/",
        "/**",
        "0x3a",
        "null",
        "DR/**/OP/",
        "/*",
        "*/",
        "*",
        "--",
        ";",
        "||",
        "'",
        "' #",
        "or 1=1",
        "'1'='1",
        "BUN",
        "S@BUN",
        "char ",
        "OR%",
        "`",
        "[",
        "]",
        "<",
        ">",
        "++",
        "script",
        "select",
        "1,1",
        "substring",
        "ascii",
        "sleep(",
        "&&",
        "insert",
        "between",
        "values",
        "truncate",
        "benchmark",
        "sql",
        "mysql",
        "%27",
        "%22",
        "(",
        ")",
        "<?",
        "<?php",
        "?>",
        "../",
        "/localhost",
        "127.0.0.1",
        "loopback",
        ":",
        "%0A",
        "%0D",
        "%3C",
        "%3E",
        "%00",
        "%2e%2e",
        "input_file",
        "execute",
        "mosconfig",
        "environ",
        "scanner",
        "path=.",
        "mod=.",
        "eval\(",
        "javascript:",
        "base64_",
        "boot.ini",
        "etc/passwd",
        "self/environ",
        "md5",
        "echo.*kae",
        "=%27$"
    );
	$query_allowed_length=350;
	$query_length=strlen($query_string);
    foreach ($patterns as $pattern) {
        if ($query_length > $query_allowed_length or strpos(strtolower($query_string), strtolower($pattern)) !== false) {
            if($query_length> $query_allowed_length){
				$pattern="Query string is too long $query_length allowed is $query_allowed_length";
			}
			if($pattern=='select' && $query_string=='s=/Public/template/js/bootstrap-select.js' ){
				
				continue;
			}
            $incident_code = getIncidentCode();
			$activity = json_encode(['incident'=>$incident_code,'pattern_found' => $pattern,'query'=>$query_string, 'post' => $_POST, 'get' => $_GET, 'request' => $_REQUEST, 'url' => $_SERVER['REQUEST_URI'], 'IP' => $access_ip, 'time' => date('d-m-Y H:i:s')]);
            $new_filename = date('d-m-Y') . "_" . 'suspicious_activity';
            file_put_contents('Public/Log/' . $new_filename . '.log', $activity, FILE_APPEND);
            http_response_code(403);
            //echo "IP RECORDED and ACTIVITY BLOCKED".$access_ip;
            if (function_exists('pushBanToList')) {
             pushBanToList($access_ip,time(),'banned_list');
			//echo "added to ban list";
            }
            
            $string = file_get_contents('.'.DIRECTORY_SEPARATOR.'Public'.DIRECTORY_SEPARATOR.'inline-error.html');
            $string = str_replace('We\'ll be back soon!', '<h1> Invalid Pattern detected</h1>', $string);
      
            $string = str_replace('$error', '<h2> Incident Code:  '.$incident_code.'</h2> <small>ClientIP: '.$access_ip.'</small>', $string);
            $string = str_replace('wolfuman.webp', 'shield.png', $string);
            
            $string = str_replace('SITE_URL', SITE_URL, $string);
            $string = str_replace('SITE_NAME', 'Visit Home Page', $string);
            echo $string;    


            exit(1);
        }
    }

}

function hacker_ip()
{
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if (getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if (getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if (getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if (getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if (getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

executeSecurity();
// **PREVENTING SESSION HIJACKING**
// Prevents javascript XSS attacks aimed to steal the session ID
ini_set('session.cookie_httponly', 1);

// **PREVENTING SESSION FIXATION**
// Session ID cannot be passed through URLs
ini_set('session.use_only_cookies', 1);

// Uses a secure connection (HTTPS) if possible
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}

if ( M_DEBUG == 0) {
    define('APP_DEBUG', 0);
    require dirname(__FILE__) . DIRECTORY_SEPARATOR.'Bootstrap.php';
} else {

    define('APP_DEBUG', 1);
    if (defined('APP_DEBUG') && APP_DEBUG && isset($_GET['debug']) && $_GET['debug'] === ADMIN_KEY) {
        setcookie('ADBUG', 'codono', time() + 60 * 3600);
        exit('ok');
    }

    if (isset($_COOKIE['ADBUG']) && $_COOKIE['ADBUG'] == ADMIN_KEY) {
        // Open debugging mode
        require __DIR__  .DIRECTORY_SEPARATOR. 'Bootstrap.php';

    } else {
        // Open debugging mode
        if (!defined('APP_DEBUG')) {
            define('APP_DEBUG', 0);
        }

        try {
            require __DIR__  . DIRECTORY_SEPARATOR.'Bootstrap.php';
        } catch (Exception $exception) {

                if (isset($exception->xdebug_message)) {
                    if (property_exists($exception, 'xdebug_message')) {
                        echo '<table>'.$exception->xdebug_message.'</table>';
                    } else {
                        echo '<table></table>';
                    }
                }

            send_http_status(404);
            $string = file_get_contents('.'.DIRECTORY_SEPARATOR.'Public'.DIRECTORY_SEPARATOR.'inline-error.html');
            if (M_DEBUG==1) {
                $string = str_replace('$error', $exception->getMessage(), $string);
            } else {
                clog('exception', [$exception->getMessage(), $exception]);
                $string = str_replace('$error', 'Please try in sometime', $string);
            }
            $string = str_replace('SITE_URL', SITE_URL, $string);
            $string = str_replace('SITE_NAME', SHORT_NAME, $string);
            echo $string;
        }
    }
}