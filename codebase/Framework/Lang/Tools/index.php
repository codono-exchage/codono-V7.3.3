<?php
include_once('../../../pure_config.php');
define('ACCESS',1);
$password=ADMIN_KEY;
$lang=clean(@$_GET['lang']);

if(@$_GET['pass']!=$password){die('Use Proper Password . <br/>Open this file\'s php source for password in it ');}
//Echo "Please Use below file<br/>";
if($lang!=''){
include_once('csv2array.php');
}
else{
include_once('array2csv.php');
Echo "<br/><strong>Import this file to Google Sheets</strong><br/>
Translate second column of CSV to your desired language<br/>
Now save the 2 column csv file[First column key, Second column translation] as say fr.csv to this directory<br/>
Now go this this location and make sure you change yourlang with your lang char example lang=fr<br/>
<a href ='/Tools/index.php?pass=$password&lang=yourlang'>Click here for Step 2</a><br/>

On Next Page you will get PHP code , Save it to Lang directory[One directory above] just like say fr.php<br/>

";}

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}