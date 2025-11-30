<?php

namespace Home\Controller;

use Think\Page;

class P2pController extends HomeController
{
    public const allowed_cryptos = ['USDT', 'BTC', 'ETH', 'BNB'];  // all caps Coins to be buy and sold
    public const require_coin = 'USDT';  // caps
    public const require_balance = 100;
    public const release_time = ['15', '30', '60', '120', '240', '360', '720'];
    public const article_category = 'faq';  // category to show articles from

    //Set transaction currency price

    public function _initialize()
    {
        $this->error('P2P is Not available');
        //Judgment to log in, you cannot directly access the database without logging in
        parent::_initialize();
        
    }
    
}