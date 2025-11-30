<?php
if(!defined('ACCESS')){die('NO ACCESS');}
//Type file name to be saved as language file
$lang=clean(@$_GET['lang']);
$filex=$lang.".csv";
if(!file_exists($filex)){
 die(sprintf('file %s does not exist, Make sure you have uploaded your file to this directory and in place of yourlang replaced the lang chars',$filex));
}

//Do not change line below 
function readCSV($csvFile){
    $file_handle = fopen($csvFile, 'r');
    while (!feof($file_handle) ) {
        $line_of_text[] = fgetcsv($file_handle, 1024);
    }
    fclose($file_handle);
    return $line_of_text;
}
 
 

$array = readCSV($filex);
header('Content-Type: text/html; charset=OEM 857'); //ansi or utf-8
echo "&lt;?php<br/>
return array(<br/>";

foreach($array as $key => $value){
	//Escaping Single Quote
	$source=str_replace("'", "\'", $value[0]);
	$translation=str_replace("'", "\'", $value[1]);
	
	$template= '"'.$source.'"=>"'.$translation.'",</br>';
	echo $template;
}
echo ");"
?>