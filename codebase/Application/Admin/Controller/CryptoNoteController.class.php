<?php

namespace Admin\Controller;

class CryptoNoteController extends AdminController
{
    private $Model;

    public function __construct()
    {
        parent::__construct();
        $this->Model = M('Coin');
        $this->Title = 'Coin Config';
    }
	public function test($coin){
		 $dj_username = C('coin')[$coin]['dj_yh'];
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
		$dj_decimal = C('coin')[$coin]['cs_qk'];
		$main_address = C('coin')[$coin]['codono_coinaddress'];
		
		if (C('coin')[$coin]['type'] == 'cryptonote') {
					$cryptonote = CryptoNote($dj_address, $dj_port);
					
					$open_wallet = $cryptonote->open_wallet($dj_username,$dj_password);
					
					$json=json_decode($cryptonote->get_height());
					
                    if (!isset($json->height) || $json->error!=0) {
						$status=1;
                        $this->error('Wallet Docking failed!'.$coin);
                    }

					$bal_info = json_decode($cryptonote->getBalance(0));
					$cryptonote_balance=$cryptonote->deAmount($bal_info->available_balance);
					echo "Your CryptoNote coin ".$coin ." is connected and has balance of ".$cryptonote_balance .' Block height of '.$json->height;
					echo "<br/>Lets Create your ".$coin." Wallet , If it does not exist it would be created with password saved in there, Else you may see an error<br/>";
		}else{
			Echo "Coin is not compatible";
		}
	}
	
	


    public function save($coin)
    {
		/*if (C('coin')[$coin]['type'] == 'cryptonote') {
					$cryptonote = CryptoNote($dj_address, $dj_port);
					
					$open_wallet = $cryptonote->open_wallet($dj_username,$dj_password);
					
					$json=json_decode($cryptonote->get_height());
					
                    if (!isset($json->height) || $json->error!=0) {
						$status=1;
                        $this->error('Wallet Docking failed!'.$coin);
                    }
					$all_info = $cryptonote->getAddress();
					$bal_info = json_decode($cryptonote->getBalance(0));
					$cryptonote_balance=$cryptonote->deAmount($bal_info->balance);
					$info['b']['balance'] =$cryptonote_balance;
					$info['b']['paytxfee'] ='Please read documentation';
					$info['b']['connection']='Block Height at '.$json->height;
		}*/
    }
}

