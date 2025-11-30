<?php
$name = 'default.png';
$fp = fopen($name, 'rb');

// send the right headers
header("Content-Type: image/png");
header("Content-Length: " . filesize($name));
fpassthru($fp);
exit;
?>