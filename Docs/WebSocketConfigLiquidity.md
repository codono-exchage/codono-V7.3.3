Disclaimer.

Socket for Huobi Only shows pricing on market with websockets, it does not include the Orderbook from exchange.

# Step1. Open other_config.php


and make sure following values are enabled /added
```
define('SOCKET_WS_ENABLE',1); //This Socket server URL
define('MARKETS_WS_SOCKET',array("btc_usd"=>"btcusdt","ltc_usd"=>"ltcusd")); //This is required to read websockets
define('SOCKET_WS_URL',"wss://localhost:7272"); //wss for live sites
```
"btc_usd"=>"btcusdt" means market on your exchange btc_usd will be fulfilled with btcusdt of huobi socket.


# Step2.

Enabling 7272 port for wss .

You would need to enable 7272 on your server for websocket, so system can broadcast message and read from it .
Example
```
wss://rapid.codono.com/wsocket
```
# Step3. Starting Sockets

On root of your installation Type in
```
php socketbot.php start
```
You can make it to run in background as well.





# Step4 NGINX forwarding

```
location ~/wsocket(.*)$ {
    proxy_set_header X-Real-IP  $remote_addr;
    proxy_set_header X-Forwarded-For $remote_addr;
    proxy_set_header Host $host;
    proxy_pass http://localhost:7272;
}
```

# Step
5
Setting Socket bot in Supervisorctl to always running

Goto SSH -> and make sure supervisor is installed if not then
```
sudo apt-get install supervisor

cd /etc/supervisor/conf.d/

sudo nano socketbot.conf
```

Paste Following

```
[program:socketbot]
directory=/var/www/vhosts/yourdomain.com/codebase
command=/opt/plesk/php/7.4/bin/php socketbot.php start
autostart=true  
autorestart=true  
stderr_logfile=/var/log/supervisor/socketbot.err.log  
stdout_logfile=/var/log/supervisor/socketbot.out.log
```
and then run following commands
```
supervisorctl reload  
supervisorctl
tail -f socketbot
```
