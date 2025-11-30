<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
	
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Redirecting...</title>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500&display=swap" rel="stylesheet">
<style>
    body {
        margin: 0;
        padding: 0;
        background: linear-gradient(135deg, #f7f8fa, #e2e8f0);
        font-family: 'Open Sans', sans-serif;
        color: #333;
        text-align: center;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .container {
        max-width: 600px;
        margin: auto;
        padding: 20px;
        background: #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    h1 {
        font-size: 24px;
        color: #333;
        margin-bottom: 20px;
        font-weight: 600;
        font-family: 'Roboto', sans-serif;
    }
    p {
        font-size: 16px;
        line-height: 1.5;
        margin: 20px 0;
    }
    a {
        color: #007bff;
        text-decoration: none;
        font-weight: 600;
    }
    a:hover {
        text-decoration: underline;
    }
    .footer {
        margin-top: 30px;
        font-size: 14px;
        color: #666;
    }
    .highlight {
        color: #007bff;
    }
    .enormous-font{
        font-size: 48px;
        margin-bottom: 0.5em;
        color: #4a5568; /* Darker gray */
    }
    .big-font{
        font-size: 20px;
        color: #2d3748; /* Gray */
    }
    .success {
        color: #38a169; /* Green */
    }
    .error {
        color: #e53e3e; /* Red */
    }
</style>
</head>
<body>
<div class="container">
    <div id="main-body">
        <?php if(isset($message)) {?>
        <p class="enormous-font success">Success!</p>
        <p class="big-font"><?php echo htmlspecialchars($message); ?></p>
        <?php }else{ ?>
        <p class="enormous-font error">Uh-oh...</p>
        <p class="big-font error"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <p class="big-font">Redirecting to <a id="href" href="<?php echo htmlspecialchars($jumpUrl); ?>" class="underline">previous page</a> in <span id="wait" class="highlight"><?php echo intval($waitSecond); ?></span> seconds.</p>
    </div>
    <p class="footer">Powered by <span class="highlight"></span></p>
</div>
<script type="text/javascript">
(function(){
    var wait = document.getElementById('wait'), href = document.getElementById('href').href;
    var interval = setInterval(function(){
        var time = --wait.innerHTML;
        if(time <= 0) {
            location.href = href;
            clearInterval(interval);
        };
    }, 2000);
})();
</script>
</body>
</html>
