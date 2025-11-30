<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title>Ooops : Something is Wrong</title>
<style type="text/css">
*{ padding: 0; margin: 0; }
html{ overflow-y: scroll; }
body{ background: #fff; font-family: 'Tahoma'; color: #333; font-size: 16px; }
img{ border: 0; }
.error{ padding: 24px 48px; }
.face{ font-size: 100px; font-weight: normal; line-height: 120px; margin-bottom: 12px; }
h1{ font-size: 32px; line-height: 48px; }
.error .content{ padding-top: 10px}
.error .info{ margin-bottom: 12px; }
.error .info .title{ margin-bottom: 3px; }
.error .info .title h3{ color: #000; font-weight: 700; font-size: 16px; }
.error .info .text{ line-height: 24px; }
.copyright{ padding: 12px 48px; color: #999; }
.copyright a{ color: #000; text-decoration: none; }
</style>
</head>
<body>
<div class="error">
<p style="font-weight:600;color:red">Debug Mode:ON</p>

<h1><?php echo strip_tags($e['message']);?></h1>
<div class="content">
<?php if(isset($e['file'])) {?>
	<div class="info">
		<div class="title">
			<h3>File Location!</h3>
		</div>
		<div class="text">
			<code>FILE: <?php echo $e['file'] ;?> &#12288;LINE: <?php echo $e['line'];?></code>
		</div>
	</div>
<?php }?>
<?php if(isset($e['trace'])) {?>
	<div class="info">
		<div class="title">
			<h3>TRACE</h3>
		</div>
		<div class="text">
			<code><?php echo nl2br($e['trace']);?></code>
		</div>
	</div>
<?php }?>
</div>
</div>
<div class="copyright">
<p>You can turn off debug mode, By editing config file and set : M_DEBUG to 0</p>
</div>
</body>
</html>
