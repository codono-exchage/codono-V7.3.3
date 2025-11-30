<?php
include_once(dirname(dirname(__DIR__)).'/codebase/pure_config.php');
$content=file_get_contents("backswagger.json");

$to_find1="http://{{siteurl}}";
$to_replace1=SITE_URL;

$to_find2="{{siteurl}}";
$to_replace2=SITE_URL;
$to_find3="//Api";
$to_replace3='/Api';
$content=str_replace($to_find1,$to_replace1,$content);
$content=str_replace($to_find2,$to_replace2,$content);
$content=str_replace($to_find3,$to_replace3,$content);


$new_content=str_replace('NAMEHERE',SHORT_NAME,$content);
echo $new_content;
?>