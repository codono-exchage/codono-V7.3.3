<?php

namespace Home\Controller;


/**
 *
 */
class CronlistController extends HomeController
{
    //All queue in one controller

    public function __construct(){
        parent::__construct();
        checkcronkey();
    }
    
    /**
     * List of crons
     * @return void
     */
    public function index(){
        //Queue
        //Queue2
        $crontabs=[];
        $array['Coin']=[
            ['name'=>'deposit_btctype','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'Bitcoin Deposit'],
            ['name'=>'deposit_cryptoapis','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'CryptoApis Deposit'],
            ['name'=>'substrate_deposit','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'Substrate Deposit'],
            ['name'=>'getWithdrawalIdSubstrate','params'=>[],'frequency'=>'every 5 mins','crontime'=>'*/5 * * * *','about'=>'Substrate Withdrawal hash retrieve'],
            ['name'=>'blockgum_deposit','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'Blockgum Deposit'],
            ['name'=>'getWithdrawalIdBlockgum','params'=>[],'frequency'=>'every 5 mins','crontime'=>'*/5 * * * *','about'=>'Blockgum Withdrawal hash retrieve'],
            ['name'=>'blockgum_token_to_main','params'=>['coinname'=>'tokenNameHere'],'frequency'=>'When required','about'=>'Blockgum Move tokens to main'],
            ['name'=>'blockgum_coin_to_main','params'=>['coinname'=>'coinNameHere'],'frequency'=>'When required','about'=>'Blockgum Move Coins to main'],
            ['name'=>'blockgum_watch','params'=>[],'frequency'=>'When required','about'=>'Blockgum Token watch: Run after adding new EVM Token'],
            
            ['name'=>'wallet_cryptonote_deposit','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'Cryptonote deposit'],
            ['name'=>'wallet_cryptonote2_deposit','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'Cryptonote deposit'],
            ['name'=>'wallet_blockio_deposit','params'=>[],'frequency'=>'every 5 mins','crontime'=>'*/5 * * * *','about'=>'Block.IO deposit'],
            ['name'=>'wallet_blockio_withdraw','params'=>[],'frequency'=>'every 5 mins','crontime'=>'*/5 * * * *','about'=>'Block.IO Withdrawal'],
            ['name'=>'esmart_deposit','params'=>['chain'=>'chainNameHere'],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'EVM Deposit Detection'],
            ['name'=>'esmart_to_main','params'=>['coin'=>'coinNameHere'],'frequency'=>'When Required','about'=>'Tool to move EVM Deposit to Main'],
            ['name'=>'esmart_token_to_main','params'=>['coinname'=>'coinNameHere'],'frequency'=>'When required','about'=>'Tool to move EVM Token Deposit to Main'],
            ['name'=>'esmart_move_all_tokens_to_main','params'=>[],'frequency'=>'When required','about'=>'Tool to move All EVM Token Deposit to Main'],
            ['name'=>'xrpdeposit','params'=>[],'frequency'=>'every 1 min','about'=>'EVM Deposit Detection'],
            ['name'=>'wallet_waves_deposit','params'=>[],'frequency'=>'every 1 min','about'=>'Waves Deposit Detection'],
            ['name'=>'MoveFundsToWaveMainAccount','params'=>['name'=>'tokeNameHere'],'frequency'=>'When Required','about'=>'Tool to move Wames Assets Deposit to Main'],
            ['name'=>'MoveWaves2MainAccount','params'=>[],'frequency'=>'When required','about'=>'Tool to move Waves Deposit to Main'],
            ['name'=>'wallet_coinpay_deposit','params'=>[],'frequency'=>'every 5 mins','crontime'=>'*/5 * * * *','about'=>'Cron to detect Deposits'],
            ['name'=>'wallet_coinpay_withdraw','params'=>[],'frequency'=>'every 5 mins','crontime'=>'*/5 * * * *','about'=>'Cron to get withdrawal hash'],
            ['name'=>'move2cold','params'=>[],'frequency'=>'When required','about'=>'**EXPERIMENTAL** Use with caution make sure coldwallets are properly defined.'],
        ];
        $array['Tron']=[

            ['name'=>'cronDeposits','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'Tron Deposit Detection'],
            
            ['name'=>'MoveAsset','params'=>[],'frequency'=>'When required','about'=>'Tool to move TRX/TRC20/TRC10 Token Deposit to Main']
        ];

        $array['Xtrade']=[
            ['name'=>'cronMe','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'When Binance keys are set in other_config run this to cross trade with Spot orders'],
            ['name'=>'otcTrade','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'When Binance keys are set in other_config run this to cross trade with Otc orders'],
        ];
        $array['Selfengine']=[
            ['name'=>'CreateOrderbook','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'When Using a market with selfengine'],
            ['name'=>'cleanUp','params'=>[],'frequency'=>'every day','crontime'=>'0 9 * * *','about'=>'Delete Trade_log where userid,peerid is 0 and older than 2 days']
        ];

        $array['Queue']=[
            ['name'=>'checkStaking','params'=>[],'frequency'=>'every day','crontime'=>'0 9 * * *','about'=>'Staking Release'],
            ['name'=>'BinanceUpdate','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'Binance Pricing Update'],
            ['name'=>'ExchangeBinanceUpdate','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'Liquidity Update '],
            ['name'=>'cmcUpdate','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'cmcPricing Update Run1'],
            ['name'=>'cmcUpdateRate','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'cmcPricing Update Run2'],
            ['name'=>'send_notifications','params'=>[],'frequency'=>'every min','crontime'=>'* * * * *','about'=>'Sending Emails'],
            ['name'=>'fix_user_coin','params'=>[],'frequency'=>'When required','about'=>'Fixes Missing column for user balance , only run when you add new coins'],
            ['name'=>'genInternalCharts','params'=>[],'frequency'=>'every 10 min','crontime'=>'*/10 * * * *','about'=>'Run if internal trades are being used'],
            ['name'=>'tendency','params'=>[],'frequency'=>'When required','about'=>'Optional: To generate Tendency Chart'],
            ['name'=>'setHourlyPrice','params'=>[],'frequency'=>'When Required','about'=>'Optional: Tool to Generate Pricing '],
            ['name'=>'setMarketCoinStats','params'=>[],'frequency'=>'When required','about'=>'Not in use any more'],
            ['name'=>'matchOrdersManually','params'=>[],'frequency'=>'When required','about'=>'Not in use any more'],
            ['name'=>'clearRedisForLiquidity','params'=>[],'frequency'=>'When required','about'=>'Use when need to clear cache redis cache of orderbooks'],
            ['name'=>'fixTrades','params'=>[],'frequency'=>'When required','about'=>'**EXPERIMENTAL** Not required'],
        ];
		$array['Tools']=[
            ['name'=>'restartSocket','params'=>[],'frequency'=>'When needed','crontime'=>'0 */3 * * *','about'=>'Restart Socket bot'],
            ['name'=>'cacheClear','params'=>[],'frequency'=>'When needed','crontime'=>'0 0 * * *','about'=>'Clear cache when needed']
        ];
        foreach($array as $key=>$cron){
            echo "Controller:".$key."<br/>";
            foreach($cron as $Q2) {
                $print=$this->infoMarker($key,$Q2);
                $crontime= $Q2['crontime'] ?? '';
                echo "key=".$crontime;
                if($crontime){
                    $crontabs[]=$print['command'];
                }
                echo "***************<pre>";
                echo implode('<br/>',$print);
                echo "</pre>***************";
            }
        }
        echo "<br/><strong>*******LINUX CRONS********</strong><pre>";
        foreach($crontabs as $ct) {
        echo '<br/>'.$ct." > /dev/null";
        }
        echo "</pre>***************<br/>";
        echo "Run this command to refresh crontab <br/>";
        echo "<strong>/etc/init.d/cron reload</strong>";
    }

    public function move(){
        $eligible_coins=[];
        foreach(C('Coin') as $coins){

            if($coins['type']=='blockgum'){
                $eligible_coins['blockgum'][$coins['name']]['name']=$coins['name'];
                $eligible_coins['blockgum'][$coins['name']]['tokenof']=$coins['tokenof'];
                $eligible_coins['blockgum'][$coins['name']]['main_address']=$coins['codono_coinaddress'];
            }
            if($coins['type']=='tron'){
                $eligible_coins['tron'][$coins['name']]['name']=$coins['name'];
                $eligible_coins['tron'][$coins['name']]['tokenof']=$coins['tokenof'];
            }

        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $selected_coin = $_POST['coin'];
            
            if (!empty($selected_coin)) {
                list($type, $coinName) = explode('-', $selected_coin);
                
                foreach ($eligible_coins[$type] as $coin) {
                    if ($type === 'blockgum' && $coin['name'] === $coinName) {
                        if ($coin['tokenof'] === 0  || $coin['tokenof'] == '' || $coin['tokenof'] == null || $coin['tokenof'] == '0') {
                            header("Location: /Coin/blockgum_coin_to_main/coinname/$coinName/securecode/" . CRON_KEY);
                        } else {

                            header("Location: /Coin/blockgum_token_to_main/coinname/$coinName/securecode/" . CRON_KEY);
                        }
                        exit;
                    }
                    if ($type === 'tron' && $coin['name'] === $coinName) {
                        
                        if ($coin['tokenof'] === 0  || $coin['tokenof'] == '' || $coin['tokenof'] == null || $coin['tokenof'] == '0') {
                            header("Location: /Tron/moveTronToMain/coinname/$coinName/securecode/" . CRON_KEY);
                        } else {

                            header("Location: /Tron/moveTokenToMain/token/$coinName/securecode/" . CRON_KEY);
                        }
                        exit;
                    }
                }
            }
        }
        
       echo ' <form method="post" action="">
        <select name="coin">
            <option value="">Select a coin</option>';

foreach ($eligible_coins as $type => $coins) {
    foreach ($coins as $coin) {
        echo '<option value="' . htmlspecialchars($type . '-' . $coin['name'], ENT_QUOTES, 'UTF-8') . '">'
             . htmlspecialchars(ucfirst($coin['name']), ENT_QUOTES, 'UTF-8') . '</option>';
    }
}

echo '        
        </select>
        <button type="submit">Go</button>
    </form>';
       
    }
    private function infoMarker($key,$Q2): array
    {
        $params='';
        
        //non sef
        /*
        if($Q2['params']) {
        $params='?'.http_build_query($Q2['params']);
        }
        */
        if ($Q2['params']) {
            // Build parameters as a forward slash separated string
            $paramPairs = [];
            foreach ($Q2['params'] as $param => $value) {
                $paramPairs[] = $param . '/' . $value;
            }
            $params = implode('/', $paramPairs);
        }
        $crontime= $Q2['crontime'] ?? '';
        $ret['command']=$crontime.' cd '.getcwd().' && '.PHP_PATH .' index.php '.$key.'/'. $Q2['name'].'/securecode/'.CRON_KEY.'/'.$params;
        $ret['url']=SITE_URL . $key . "/" . $Q2['name'].'/securecode/'.CRON_KEY.'/'.$params;
        $ret['frequency']=$Q2['frequency'];
        $ret['about']=$Q2['about'];
        $ret['run']="<a href='".$ret['url']."' target='_blank'>".$ret['url']."</a>";
        return $ret;
    }
    public function test(){
        $Q2=['params'=>['coinname'=>'eth']];
        if($Q2['params']) {
            var_dump($Q2['params']);
            $params=http_build_query($Q2['params']);
            var_dump(10,$params);
        }

    }
}
