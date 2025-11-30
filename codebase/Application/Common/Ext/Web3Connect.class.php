<?php

namespace Common\Ext;

use kornrunner\Ethereum\Token;
use kornrunner\Ethereum\Transaction;

class Web3Connect
{
    protected $chain, $network;
    private $successUrl;
    private $errorUrl;
    private $coin;
    private $api_key;
    private $api_url;
    const ETHER_SCAN_TX_URL = 'api?module=proxy&action=eth_getTransactionByHash';


    public function __construct($coin, $network, $api_key)
    {
        require_once('vendor/autoload.php');
        $this->coin = $coin;
        if ($coin == 'bnb' && $network == 'mainnet') {
            $host = 'https://bsc-dataseed1.binance.org';
        }
        if ($coin == 'bnb' && $network == 'testnet') {
            $host = 'https://data-seed-prebsc-1-s1.binance.org:8545/';
        }
        $this->network = $network;
        $nf = $this->networkFinder($coin, $network);
        $this->api_key = $api_key;
        $this->api_url = "https://" . $nf['prefix'] . '.' . $nf['mainurl'] . '/';
        $this->successUrl = SITE_URL . 'Dex/successPage';
        $this->errorUrl = SITE_URL . 'Dex/errorPage';
    }

    private function networkFinder($coin, $network): array
    {
        switch ($network) {
            case 'mainnet':
                $prefix = 'api';
                break;
            case 'kovan':
                $prefix = 'api-kovan';
                break;
            case 'ropsten':
                $prefix = 'api-ropsten';
                break;

            case 'testnet':
                $prefix = 'api-testnet';
                break;

            default:
                $prefix = "api";
        }
        switch ($coin) {
            case 'bnb':
                $mainurl = 'bscscan.com';
                break;
            case 'eth':
                $mainurl = 'etherscan.io';
                break;
            default:
                $mainurl = "etherscan.io";
        }
        return array('prefix' => $prefix, 'mainurl' => $mainurl);
    }

    public function txLink($tx = null)
    {
        $info = $this->networkFinder($this->coin, $this->network);
		if($this->network=='mainnet'){
			$url = "https://". $info['mainurl'] . "/tx/" . $tx;
        
		}else{
			$url = "https://" . $this->network . '.' . $info['mainurl'] . '/tx/' . $tx;
		}
        return $url;
    }

    public function findProvider(): array
    {

        if ($this->coin == 'bnb') {
            switch ($this->network) {
                case 'mainnet':
                    $info['provider'] = 'https://bsc-dataseed.binance.org/';
                    $info['chainid'] = '0x38';
                    break;

                case 'testnet':
                    $info['provider'] = 'https://data-seed-prebsc-1-s1.binance.org:8545/';
                    $info['chainid'] = '0x61';
                    break;

                default:
                    $info['provider'] = 'https://bsc-dataseed.binance.org/';
                    $info['chainid'] = '0x38';
            }
        } else {
            switch ($this->network) {
                case 'mainnet':
                    $info['provider'] = 'https://mainnet.infura.io/v3/' . INFURA_PROJECT_ID;
                    $info['chainid'] = '0x1';
                    break;

                case 'kovan':
                    $info['provider'] = 'https://kovan.infura.io/v3/' . INFURA_PROJECT_ID;
                    $info['chainid'] = '0x2a';
                    break;

                case 'ropsten':
                    $info['provider'] = 'https://ropsten.infura.io/v3/' . INFURA_PROJECT_ID;
                    $info['chainid'] = '0x3';
                    break;

                default:
                    $info['provider'] = 'https://mainnet.infura.io/v3/' . INFURA_PROJECT_ID;
                    $info['chainid'] = '0x1';
            }
        }

        return $info;

    }

    /**
     * @param $receivers
     * @param $amounts
     * @param int $qid
     * @param int $is_token
     * @param string $contractAddress
     * @param int $decimalPlaces
     * @param string $codetype =custom or webconnect
     * @return string
     */
    public function do_sendtransaction($receivers, $amounts, $qid = 0, $is_token = 0, $contractAddress = null, $decimalPlaces = 18, $codetype = "custom")
    {
        //wc_multiSendToken,wc_sendTransaction uses walletconnect where as mm_sendTransaction uses custom script
        //means sending only eth or bnb
        switch ($codetype) {
            case 'custom':
                if ($is_token == 0) {
                    return $this->mm_sendTransaction($receivers, $amounts, $qid);
                } else {
                    return $this->mm_multiSendToken($receivers, $amounts, $contractAddress, $decimalPlaces, $qid);
                }
            case 'oldwalletconect':
                if ($is_token == 0) {
                    return $this->wc_sendTransaction($receivers, $amounts, $qid);
                } else {
                    return $this->wc_multiSendToken($receivers, $amounts, $contractAddress, $decimalPlaces, $qid);
                }
            case 'walletconnect':
                if ($is_token == 0) {
                    return $this->wc_sendcoin($receivers, $amounts, $qid);
                } else {
                    return $this->wc_sendtoken($receivers, $amounts, $contractAddress, $decimalPlaces, $qid);
                }
            default:
                if ($is_token == 0) {
                    return $this->wc_sendcoin($receivers, $amounts, $qid);
                } else {
                    return $this->wc_sendtoken($receivers, $amounts, $contractAddress, $decimalPlaces, $qid);
                }
        }


    }
    private function wc_sendcoin($receiver_address, $amount, $qid = 0)
    {
        $info = $this->findProvider();
        $provider = $info['provider'];
        $chainid = $info['chainid'];
        $infura_id = INFURA_PROJECT_ID;
        return ' <script>
"use strict";

const Web3Modal = window.Web3Modal.default;
const WalletConnectProvider = window.WalletConnectProvider.default;
const Fortmatic = window.Fortmatic;
const evmChains = window.evmChains;

// Web3modal instance
let web3Modal

// Chosen wallet provider given by the dialog window
let provider;

// Address of the selected account
let selectedAccount;

function init() {


  const providerOptions = {
    
    walletconnect: {
      package: WalletConnectProvider,
       
	       options: {
          infuraId: "'.$infura_id.'",
		  qrcodeModalOptions: {
		  mobileLinks: [
             "rainbow",
             "metamask",
             "argent",
             "trust",
             "imtoken",
             "pillar"
           ]}
      }
    },

  };

  web3Modal = new Web3Modal(
    {
      
      cacheProvider: false, // optional
      providerOptions, // required
      disableInjectedProvider: false, // optional. For MetaMask / Brave / Opera.
    }
  );


  console.log("Web3Modal instance is", web3Modal);
}

async function fetchAccountData() {
  const requiredChainId="'.$chainid.'";
  const web3 = new Web3(provider);
  const chainId = await web3.eth.getChainId();
  const chainData = evmChains.getChain(chainId);
  if(chainId!=requiredChainId){
						alert("Please select correct chainid:"+requiredChainId+",Select proper coin and network and refresh this page"+chainId);
						return false;
   }
  document.querySelector("#network-name").textContent = chainData.name;
  const accounts = await web3.eth.getAccounts();
  selectedAccount = accounts[0];
  document.querySelector("#selected-account").textContent = selectedAccount;

  // Display fully loaded UI for wallet data
  document.querySelector("#prepare").style.display = "none";
  document.querySelector("#connected").style.display = "block";
  document.querySelector("#network").style.display = "block";
}


async function refreshAccountData() {

  document.querySelector("#connected").style.display = "block";
  document.querySelector("#prepare").style.display = "none";
  document.querySelector("#network").style.display = "block";
  document.querySelector("#btn-connect").setAttribute("disabled", "disabled")
  await fetchAccountData(provider);
  document.querySelector("#btn-connect").removeAttribute("disabled")
}

async function makePayment() {
  const web3 = new Web3(provider);
  let beneficiary = "' . $receiver_address . '";
  
  var amountToPay = "' . $amount . '";
  var textElem = document.querySelector("#thankyou");
						web3.eth.sendTransaction(
    {
      to: beneficiary,
      from: selectedAccount,
      value:  web3.utils.toWei(amountToPay, "ether")
    },
    (error, result) => {
      if (error) {
   
        textElem.innerHTML = error.message;
        return console.log(error);
      }
	  /* successful do something */
	  
		textElem.innerHTML = result;
		location.href="' . $this->successUrl . '?txhash="+result+"&qid=' . $qid . '&amount=' . $amount . '";
		console.log(result);
    }
  );
  
}

async function onConnect() {
  console.log("Connect");
  try {
    provider = await web3Modal.connect();
  } catch(e) {
    console.log("Could not get a wallet connection", e);
    return;
  }

  // Subscribe to accounts change
  provider.on("accountsChanged", (accounts) => {
    fetchAccountData();
  });

  // Subscribe to chainId change
  provider.on("chainChanged", (chainId) => {
    fetchAccountData();
  });

  // Subscribe to networkId change
  provider.on("networkChanged", (networkId) => {
    fetchAccountData();
  });

  await refreshAccountData();
}

async function onDisconnect() {

  if(provider.close) {
    await provider.close();
    await web3Modal.clearCachedProvider();
    provider = null;
  }

  selectedAccount = null;

  document.querySelector("#prepare").style.display = "block";
  document.querySelector("#connected").style.display = "none";
  document.querySelector("#network").style.display = "none";

}

async function onPayment() {

  console.log("Make a payment");
    pleasewait();
  await makePayment();
}


window.addEventListener("load", async () => {
  init();
  document.querySelector("#btn-connect").addEventListener("click", onConnect);
  document.querySelector("#btn-disconnect").addEventListener("click", onDisconnect);
  document.querySelector("#btn-payment").addEventListener("click", onPayment);
});
</script>';
    }

    private function wc_sendtoken($receivers, $amount, $contractAddress, $decimalPlaces, $qid, $symbol = "UNI", $abiContract = "")
    {
        $info = $this->findProvider();
        $provider = $info['provider'];
        $chainid = $info['chainid'];
        $amount = bcmul($amount, pow(10, $decimalPlaces));
        $abi_Contract = file_get_contents('Public/ABI/' . strtoupper($symbol) . '.abi');
        $infura_id = INFURA_PROJECT_ID;
        return ' <script>
"use strict";

const Web3Modal = window.Web3Modal.default;
const WalletConnectProvider = window.WalletConnectProvider.default;
const evmChains = window.evmChains;

// Web3modal instance
let web3Modal

// Chosen wallet provider given by the dialog window
let provider;

// Address of the selected account
let selectedAccount;

function init() {


const providerOptions = {
    
    walletconnect: {
      package: WalletConnectProvider,  
	       options: {
			rpc: {
			1: "https://bsc-dataseed.binance.org/",
        	56: "https://bsc-dataseed.binance.org/",
        	97: "https://data-seed-prebsc-1-s1.binance.org:8545/"
      		},
		    
			pollingInterval: 12000,
		 	network: "binance",
        	chainId: 56,
		  	qrcodeModalOptions: {
		  		mobileLinks: [
             		"rainbow",
             		"metamask",
             		"argent",
             		"trust",
             		"imtoken",
             		"pillar"
           ]}
      }
    },

  };

  web3Modal = new Web3Modal(
    {
      
      cacheProvider: false, // optional
      providerOptions, // required
      disableInjectedProvider: false, // optional. For MetaMask / Brave / Opera.
    }
  );


  console.log("Web3Modal instance is", web3Modal);
}

async function fetchAccountData() {

  const web3 = new Web3(provider);
  const chainId = await web3.eth.getChainId();
  const chainData = evmChains.getChain(chainId);
  document.querySelector("#network-name").textContent = chainData.name;
  const accounts = await web3.eth.getAccounts();
  selectedAccount = accounts[0];
  document.querySelector("#selected-account").textContent = selectedAccount;

  // Display fully loaded UI for wallet data
  document.querySelector("#prepare").style.display = "none";
  document.querySelector("#connected").style.display = "block";
  document.querySelector("#network").style.display = "block";
}


async function refreshAccountData() {

  document.querySelector("#connected").style.display = "block";
  document.querySelector("#prepare").style.display = "none";
  document.querySelector("#network").style.display = "block";
  document.querySelector("#btn-connect").setAttribute("disabled", "disabled")
  await fetchAccountData(provider);
  document.querySelector("#btn-connect").removeAttribute("disabled")
}

async function makePayment() {
  const web3 = new Web3(provider);

  
  let decimal_places = ' . $decimalPlaces . ';
  let contractAddress = "' . $contractAddress . '";
  let AbiOfContract = ' . $abi_Contract . ';
  
  let amounts = ' . $amount . ';
  let tokens_amt = web3.utils.toWei(amounts.toString(), "ether");
  let mainAmount = 0.0;
  let addrs = "' . $receivers . '";
  let final_amt=web3.utils.toHex("' . $amount . '");
  const tokenDecimals = web3.utils.toBN(decimal_places);
  const tokenAmountToTransfer = web3.utils.toBN(tokens_amt);
  const calculatedTransferValue = web3.utils.toHex(tokenAmountToTransfer.mul(web3.utils.toBN(10).pow(tokenDecimals)));
  let contractInstance = new web3.eth.Contract(AbiOfContract,contractAddress);
  let getData = contractInstance.methods.transfer(addrs,final_amt).encodeABI();
  const accounts = await web3.eth.getAccounts();
	selectedAccount = accounts[0];

   
  var textElem = document.querySelector("#thankyou");
  
      web3.eth.sendTransaction(
  {
                            from:selectedAccount,
                            to: contractAddress,
                            data: getData,
                            value: 0
                        },
    (error, result) => {
      if (error) {
   
        textElem.innerHTML = error.message;
        return console.error(error);
      }
	          location.href="' . $this->successUrl . '?txhash="+result+"&qid=' . $qid . '&amount=' . $amount . '";
		textElem.innerHTML = result;
		console.log(result);
    }
  );
  
  }

async function onConnect() {
  console.log("Connect");
  try {
    provider = await web3Modal.connect();
  } catch(e) {
    console.log("Could not get a wallet connection", e);
    return;
  }

  // Subscribe to accounts change
  provider.on("accountsChanged", (accounts) => {
    fetchAccountData();
  });

  // Subscribe to chainId change
  provider.on("chainChanged", (chainId) => {
    fetchAccountData();
  });

  // Subscribe to networkId change
  provider.on("networkChanged", (networkId) => {
    fetchAccountData();
  });

  await refreshAccountData();
}

async function onDisconnect() {

  if(provider.close) {
    await provider.close();
    await web3Modal.clearCachedProvider();
    provider = null;
  }

  selectedAccount = null;

  document.querySelector("#prepare").style.display = "block";
  document.querySelector("#connected").style.display = "none";
  document.querySelector("#network").style.display = "none";

}

async function onPayment() {

  console.log("Make a payment");
    pleasewait();
  await makePayment();
}


window.addEventListener("load", async () => {
  init();
  document.querySelector("#btn-connect").addEventListener("click", onConnect);
  document.querySelector("#btn-disconnect").addEventListener("click", onDisconnect);
  document.querySelector("#btn-payment").addEventListener("click", onPayment);
});
</script>';
    }

    private function wc_sendTransaction($receiver_address, $amount, $qid = 0)
    {
        $info = $this->findProvider();
        $provider = $info['provider'];
        $chainid = $info['chainid'];
        $infura_id = "8043bb2cf99347b1bfadfb233c5325c0";
        return '<script>            
                const Web3Modal = window.Web3Modal.default;
                const WalletConnectProvider = window.WalletConnectProvider.default;
                const Fortmatic = window.Fortmatic;
                const evmChains = window.evmChains;
			
                 let web3js = new Web3(Web3.givenProvider || "ws://localhost:8545");

let web3Modal
let provider;
let selectedAccount;

function init() {
  const providerOptions = {
    walletconnect: {
      package: WalletConnectProvider,
      options: {
        infuraId: "' . $infura_id . '",
      }
    }
  };

  web3Modal = new Web3Modal({
    cacheProvider: false, // optional
    providerOptions, // required
    disableInjectedProvider: false, // optional. For MetaMask / Brave / Opera.
  });
}


async function fetchAccountData() {
  const chainId = await web3js.eth.getChainId();
  const chainData = evmChains.getChain(chainId);
}
async function onConnect() {
 
    provider = await web3Modal.connect();
    const chainId = await ethereum.request({ method: "eth_chainId" });
if(chainId!="0x38"){
						alert("Please select correct chainid,Select proper coin and network and refresh this page"+chainId);
						return false;
}
 
if(provider){
	   
 		transaction = ({
                            from: web3js.givenProvider.selectedAddress,
                            to: "' . $receiver_address . '",
                            value: web3js.utils.toWei("' . $amount . '", "ether")
                        });
            pleasewait();
   web3.eth.sendTransaction({
                            from: web3.givenProvider.selectedAddress,
                            to: contractAddress,
                            data: getData,
                            value: 0
                        }).then(function(result){
                          location.href="' . $this->successUrl . '?txhash="+result.transactionHash+"&qid=' . $qid . '&amount=' . $amount . '";
                        }).catch(function(error) {
                      
                          alert("Transaction ERROR:"+error.message);
                          location.href="' . $this->errorUrl . '&qid=' . $qid . '";
                        });
}else{
	alert("Please install Metamask or Trustwallet");
}
}

window.addEventListener("load", async () => {
  init();
  onConnect();
});

            </script>';
    }

    private function wc_multiSendToken($receivers, $amounts, $contractAddress, $decimalPlaces, $qid, $symbol = "UNI", $abiContract = "")
    {
        $info = $this->findProvider();
        $provider = $info['provider'];
        $chainid = $info['chainid'];
        $amounts = bcmul($amounts, pow(10, $decimalPlaces));
        $abi_Contract = file_get_contents('Public/ABI/' . strtoupper($symbol) . '.abi');
        $infura_id = "8043bb2cf99347b1bfadfb233c5325c0";
        return '<script>
const Web3Modal = window.Web3Modal.default;
const WalletConnectProvider = window.WalletConnectProvider.default;
const Fortmatic = window.Fortmatic;
const evmChains = window.evmChains;
const web3js = new Web3(window.ethereum);

            //const web3js = new Web3(web3.currentProvider);
            let decimal_places = ' . $decimalPlaces . ';
            let contractAddress = "' . $contractAddress . '";
            let AbiOfContract = ' . $abi_Contract . ';
			let amounts = ' . $amounts . ';
			let tokens_amt = web3js.utils.toWei(amounts.toString(), "ether")
			let mainAmount = 0.0;
            let final_amt=web3js.utils.toHex("' . $amounts . '");
            let addrs = "' . $receivers . '";
			const tokenDecimals = web3js.utils.toBN(decimal_places);
			const tokenAmountToTransfer = web3js.utils.toBN(tokens_amt);
			const calculatedTransferValue = web3js.utils.toHex(tokenAmountToTransfer.mul(web3js.utils.toBN(10).pow(tokenDecimals)));
			
let web3Modal
let provider;
let selectedAccount;

function init() {
  const providerOptions = {
    walletconnect: {
      package: WalletConnectProvider,
      options: {
        infuraId: "' . $infura_id . '",
      }
    }
  };

  web3Modal = new Web3Modal({
    cacheProvider: false, // optional
    providerOptions, // required
    disableInjectedProvider: false, // optional. For MetaMask / Brave / Opera.
  });
}


async function fetchAccountData() {
  const chainId = await web3js.eth.getChainId();
  const chainData = evmChains.getChain(chainId);
}
async function onConnect() {
 	            let contractInstance = new web3js.eth.Contract(AbiOfContract);
                let getData = contractInstance.methods.transfer(addrs,final_amt).encodeABI();
    provider = await web3Modal.connect();
  
  const chainId = await ethereum.request({ method: "eth_chainId" });
  
if(chainId!="0x38"){
						alert("Please select correct chainid,Select proper coin and network and refresh this page"+chainId);
						return false;
}

if(provider){
                        transaction = ({
                            from: web3js.givenProvider.selectedAddress,
                            to: contractAddress,
                            data: getData,
                            value: 0
                        });

pleasewait();
                       web3js.eth.sendTransaction(transaction).then(function(result){
                          location.href="' . $this->successUrl . '?txhash="+result.transactionHash+"&qid=' . $qid . '&amount=' .$amounts . '";
                        })
                        .catch(function(error) {
                          console.error(error);
                          alert("Transaction ERROR:"+error.message);
                          location.href="' . $this->errorUrl . '&qid=' . $qid . '";
                        });
}else{
	alert("Please install Metamask or Trustwallet");
}
}

window.addEventListener("load", async () => {
  init();
  onConnect();
});

            </script>';
    }

    private function mm_sendTransaction($receiver_address, $amount, $qid = 0)
    {
        $info = $this->findProvider();
        $provider = $info['provider'];
        $chainid = $info['chainid'];
        return '<script>
            // await ethereum.enable();
            function setCookie(c_name, value, exdays) {
                var exdate = new Date();
                exdate.setDate(exdate.getDate() + exdays);
                var c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString());
                document.cookie = c_name + "=" + c_value;
            }
            let web3js = new Web3(Web3.givenProvider || "ws://localhost:8545");
            //let web3js = new Web3("' . $provider . '"||Web3.givenProvider || "ws://localhost:8545");
            window.addEventListener(\'load\', async () => {
                // Modern dapp browsers...
                if (window.ethereum) {
                    try {
					const provider =await ethereum.enable();
					//					const provider =ethereum.request({ method: "eth_requestAccounts" });
					const chainId = await ethereum.request({ method: "eth_chainId" });
					if(chainId!="' . $chainid . '"){
						alert("Please select correct chainid,Select ' . $this->coin . ' ' . $this->network . ' and refresh this page."+chainId);
						return false;
					}
					if (provider) {
					setCookie("user_addr_save",web3js.givenProvider.selectedAddress);
					transaction = ({
                            from: web3js.givenProvider.selectedAddress,
                            to: "' . $receiver_address . '",
                            value: web3js.utils.toWei("' . $amount . '", "ether")
                        });
                        // console.log(transaction);
                        web3js.eth.sendTransaction(transaction).then(function(result){
                          location.href="' . $this->successUrl . '?txhash="+result.transactionHash+"&qid=' . $qid . '&amount=' . $amount . '";
                        })
                        .catch(function(error) {
                          console.error(error);
                          alert("Transaction ERROR:"+error.message);
                          location.href="' . $this->errorUrl . '&qid=' . $qid . '";
                        });
					}else{
						alert("Please install Metamask or Trustwallet");
					}		
                    } catch (error) {
                        // User denied account access...
                        alert("Intitalization ERROR!");
                    }
                }
                // Legacy dapp browsers...
                else if (window.web3) {
                
                    transaction = ({
                        from: web3js.givenProvider.selectedAddress,
                        to: "' . $receiver_address . '",
                        value: web3js.utils.toWei("' . $amount . '", "ether")
                    });
                    console.log(transaction);
                }
                // Non-dapp browsers...
                else {
                    alert(\'Non-Ethereum browser detected. You should consider trying MetaMask!\');
                }
            });
            </script>';
    }

    private function xmm_sendTransaction($receiver_address, $amount, $qid = 0)
    {
        $info = $this->findProvider();
        $provider = $info['provider'];
        $chainid = $info['chainid'];
        return '<script>
            // await ethereum.enable();
            let web3js = new Web3(Web3.givenProvider || "ws://localhost:8545");
            //let web3js = new Web3("' . $provider . '"||Web3.givenProvider || "ws://localhost:8545");
            window.addEventListener(\'load\', async () => {
                // Modern dapp browsers...
                if (window.ethereum) {
                    try {
					const provider =await ethereum.enable();
					//					const provider =ethereum.request({ method: "eth_requestAccounts" });
					const chainId = await ethereum.request({ method: "eth_chainId" });
					if(chainId!="' . $chainid . '"){
						alert("Please select correct chainid,Select ' . $this->coin . ' ' . $this->network . ' and refresh this page");
						return false;
					}
					if (provider) {
					setCookie("user_addr_save",web3js.givenProvider.selectedAddress);
					transaction = ({
                            from: web3js.givenProvider.selectedAddress,
                            to: "' . $receiver_address . '",
                            value: web3js.utils.toWei("' . $amount . '", "ether")
                        });
                        // console.log(transaction);
                        web3js.eth.sendTransaction(transaction).then(function(result){
                          location.href="' . $this->successUrl . '?txhash="+result.transactionHash+"&qid=' . $qid . '&amount=' . $amount . '";
                        })
                        .catch(function(error) {
                          console.error(error);
                          alert("Transaction ERROR:"+error.message);
                          location.href="' . $this->errorUrl . '&qid=' . $qid . '";
                        });
					}else{
						alert("Please install Metamask or Trustwallet");
					}		

                    } catch (error) {
                        // User denied account access...
                        alert("Intitalization ERROR!");
                    }
                }
                // Legacy dapp browsers...
                else if (window.ethereum) {
                
                    transaction = ({
                        from: web3js.givenProvider.selectedAddress,
                        to: "' . $receiver_address . '",
                        value: web3js.utils.toWei("' . $amount . '", "ether")
                    });
                    console.log(transaction);
                }
                // Non-dapp browsers...
                else {
                    alert(\'Non-Ethereum browser detected. You should consider trying MetaMask!\');
                }
            });
            </script>';
    }


    private function mm_multiSendToken($receivers, $amounts, $contractAddress, $decimalPlaces, $qid, $symbol = "UNI", $abiContract = "")
    {
        $info = $this->findProvider();
        $provider = $info['provider'];
        $chainid = $info['chainid'];
        $amounts = bcmul($amounts, pow(10, $decimalPlaces));
        $abi_Contract = file_get_contents('Public/ABI/' . strtoupper($symbol) . '.abi');
        return '<script>

			let web3js = new Web3(Web3.givenProvider || "ws://localhost:8545");
            //const web3js = new Web3(web3.currentProvider);
            let decimal_places = ' . $decimalPlaces . ';
            let contractAddress = "' . $contractAddress . '";
            let AbiOfContract = ' . $abi_Contract . ';
			let amounts = ' . $amounts . ';
			let tokens_amt = web3js.utils.toWei(amounts.toString(), "ether")
			let mainAmount = 0.0;
            let final_amt=web3js.utils.toHex("' . $amounts . '");
            let addrs = "' . $receivers . '";
			
			const tokenDecimals = web3js.utils.toBN(decimal_places);
			const tokenAmountToTransfer = web3js.utils.toBN(tokens_amt);
			const calculatedTransferValue = web3js.utils.toHex(tokenAmountToTransfer.mul(web3js.utils.toBN(10).pow(tokenDecimals)));

   
            // convert amounts to Wei

           
           
            window.addEventListener(\'load\', async () => {
				
                let contractInstance = new web3js.eth.Contract(AbiOfContract);
                let getData = contractInstance.methods.transfer(addrs,final_amt).encodeABI();
				console.log(getData);
                // Modern dapp browsers...
					if (window.ethereum) {
                    try {
					const provider =await ethereum.enable();
					const chainId = await ethereum.request({ method: "eth_chainId" });
					if(chainId!="' . $chainid . '"){
						alert("Please select correct chainid,Select ' . $this->coin . ' ' . $this->network . ' and refresh this page");
						return false;
					}
					if (provider) {
					
                        transaction = ({
                            from: web3js.givenProvider.selectedAddress,
                            to: contractAddress,
                            data: getData,
                            value: 0
                        });
                        // console.log(transaction);
                         web3js.eth.sendTransaction(transaction)
                        .on(\'transactionHash\', function(hash){
                          location.href = "' . $this->successUrl . '?qid=' . $qid . '&txhash="+hash;
                        });
                        
						}else{
						alert("Please install Metamask or Trustwallet");
					}		

                    } catch (error) {
                       // alert("WEB3 intitalization. Page will reload automatically.");
                        // location.reload();
                    }
                }
                // Legacy dapp browsers...
                else if (window.ethereum) {
               
                    transaction = ({
                        from: web3js.givenProvider.selectedAddress,
                        to: contractAddress,
                        data: getData,
                        value: web3js.utils.toWei(mainAmount, "ether")
                    });
                    console.log(transaction);
                }
                // Non-dapp browsers...
                else {
                    alert(\'Non-Ethereum browser detected. You should consider trying MetaMask!\');
                }
            });
       
       
            </script>';
    }


    /*
    0x1	1	Ethereum Main Network (Mainnet)
0x3	3	Ropsten Test Network
0x4	4	Rinkeby Test Network
0x5	5	Goerli Test Network
0x2a	42	Kovan Test Network
    */
    private function chainFinder($chainid)
    {
        switch ($chainid) {
            case '0x1':
                $chain_name['id'] = 1;
                $chain_name['name'] = 'ETH Mainnet';
                break;
            case '0x2a':
                $chain_name['id'] = 42;
                $chain_name['name'] = 'Kovan Testnet';
                break;
            case '0x3':
                $chain_name['id'] = 3;
                $chain_name['name'] = 'Ropsten Testnet';
                break;
            case '0x4':
                $chain_name['id'] = 4;
                $chain_name['name'] = 'Rinkeby Testnet';
                break;
            case '0x5':
                $chain_name['id'] = 5;
                $chain_name['name'] = 'Goerli Testnet';
                break;

            default:

                $chain_name['id'] = 0;
                $chain_name['name'] = 'Invalid';
        }
        return $chain_name;
    }


    //trustwallet send coin using deeplinking
    /* Ref
    https://github.com/satoshilabs/slips/blob/master/slip-0044.md
    https://github.com/trustwallet/developer/blob/master/assets/universal_asset_id.md
    Coins:
Bitcoin: c0
Ethereum: c60
Binance Chain c714
Tokens:
DAI (Ethereum): c60_t0x6B175474E89094C44Da98b954EedeAC495271d0F
BUSD (Binance Chain): c714_tBUSD-BD1
USDT (Tron): c195_tTR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t
DAI (Smart Chain): c10000714_t0x6B175474E89094C44Da98b954EedeAC495271d0F
Template: c{coin}_t{token_id}


    */
    public function tw_send($coin, $receiver, $amount, $memo): string
    {
        $asset = $this->tw_coinToAsset($coin);
        $amount = is_numeric($amount) ? $amount : 0;
        return "https://link.trustwallet.com/send?asset=$asset&address=$receiver&amount=$amount&memo=$memo";
    }

    private function tw_coinToAsset($coin)
    {
        switch ($coin) {
            case 'btc':
                $asset = "c0";
                break;
            case 'eth':
                $asset = "c60";
                break;
            case 'bnb':
                $asset = "c714";
                break;
            case 'busd':
                $asset = "c714_tBUSD-BD1";
                break;
            default:
                $asset = "c714";
        }
        return $asset;
    }

    public function getTransactionByHash($hash)
    {
        $url = $this->api_url . self::ETHER_SCAN_TX_URL . '&txhash=' . $hash . '&apikey=' . $this->api_key;
        $resp = $this->gcurl($url);
        if ($this->isJson($resp)) {
            return json_decode($resp);
        } else {
            return false;
        }
    }

    private function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function gcurl($endpoint, $method = 'GET')
    {
        if (!$endpoint) {
            return "{'error':'No URL'}";
        }
        $call_url = $endpoint;

        $curl = curl_init();
        curl_setopt_array($curl, array(

            CURLOPT_URL => $call_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
        ));

        $response = curl_exec($curl);

        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }


    //Get Public keys
    function request($url, $params = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, TRUE);
        if (substr($url, -8) == 'get_info') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        $ret = curl_exec($ch);
        return @json_decode($ret);
        //get

    }

    public function eth_Hex2Dec($weiNumber, $decimal = 18)
    {
        $power = bcpow(10, $decimal, 0);

        $tenNumber = base_convert($weiNumber, 16, 10);
        $ethNumber = bcdiv($tenNumber, $power, 8);
        return $ethNumber;
    }


    private function eth_gasPrice()
    {
        $url = $this->api_url . 'api?module=proxy&action=eth_gasPrice&apikey=' . $this->api_key;
        $response = json_decode($this->gcurl($url, 'GET'), true);
        return $this->checkRpcResult($response);
    }

    private function eth_estimateGas($from, $to, $gas, $gasPrice, $value, $data)
    {
        $url = $this->api_url . "api?module=proxy&action=eth_estimateGas&from=" . $from . "&data=" . $data . "&to=" . $to . "&value=" . $value . "&gasPrice=" . $gasPrice . "&gas=" . $gas . "&apikey=" . $this->api_key;

        $response = json_decode($this->gcurl($url, 'GET'), true);
        return $this->checkRpcResult($response);
    }

    public function es_TokenBalance($address, $contract, $decimal = 8)
    {
        $url = $this->api_url . "api?module=account&action=tokenbalance&contractaddress=" . $contract . "&address=" . $address . "&tag=latest&apikey=" . $this->api_key;
        $result = json_decode($this->gcurl($url, 'GET'));

        // $balance=($result->result)/pow(10,$decimal); //Now converting result to actual number by dividing 10^decimals example 10^8
        $balance = ($result->result); //
        if ($result && $result->status == 1) {
            return $balance;
        } else {
            return false;
        }
    }
	
    public function es_Balance($address)
    {
        $url = $this->api_url . "api?module=account&action=balance&address=" . $address . "&tag=latest&apikey=" . $this->api_key;
		
        $result = json_decode($this->gcurl($url, 'GET'));

        $balance = ($result->result); //
        if ($result && $result->status == 1) {
            return $balance;
        } else {
            return false;
        }
    }

    /**
     * @param $address
     * @param int $fromblock
     * @param string $toblock
     * @param int $type 1=deposit | 2 =withdrawal
     * @param string $coin_type coin|token
     * @return array|false
     */
    public function findDeposits($address, $fromblock = 0, $toblock = 'latest', $type = 1, $coin_type = "coin")
    {
        $final_records['address'] = $address;
        $final_records['fromblock'] = $fromblock;
        $final_records['toblock'] = $toblock;
        $final_records['type'] = $type;
        $final_records['coin_type'] = $coin_type;
        if ($coin_type == 'coin') {
            $action = 'txlist';
        } else {
            $action = 'tokentx';
        }
        $url = $this->api_url . "api?module=account&action=" . $action . "&address=" . $address . "&startblock=" . $fromblock . "&endblock=" . $toblock . "&sort=asc&apikey=" . $this->api_key;
        $response = json_decode($this->gcurl($url, 'GET'), true);
        $result = ($response['result']); //

        if ($response && $response['status'] == 1) {
            foreach ($result as $record) {
                if ($coin_type == 'token') {
                    $record['value'] = bcdiv($record['value'], bcpow(10, $record['tokenDecimal']), $record['tokenDecimal']);
                }
                unset($record['gas']);
                unset($record['gasPrice']);
                unset($record['gasUsed']);

                unset($record['input']);
                unset($record['cumulativeGasUsed']);

                if ($type == 1) {

                    if (strtolower($record['from']) != strtolower($address)) {
                        $final_records['txs'][] = $record;
                    }
                } else {
                    if (strtolower($record['to']) != strtolower($address)) {
                        $final_records['txs'][] = $record;
                    }
                }

            }
            $final_records['count'] = count($final_records['txs']);
            return $final_records;
        } else {
            return false;
        }
    }

    private function eth_getTransactionCount($address)
    {
        $url = $this->api_url . "api?module=proxy&action=eth_getTransactionCount&address=" . $address . "&apikey=" . $this->api_key;

        $result = $this->gcurl($url, 'GET');
        if ($result) {
            $json = json_decode($result);

            if ($json->result) {
                return $json->result;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function eth_sendRawTransaction($hex)
    {
        
	
		$url = $this->api_url . "api?module=proxy&action=eth_sendRawTransaction&hex=" . $hex . "&apikey=" . $this->api_key;
	
        $result = $this->gcurl($url, 'GET');
        if ($result) {
            $json = json_decode($result);

            if ($json->result) {
                return $json->result;
            } else {
				clog(__FILE__.'_'.__LINE__,$json);
                return false;
            }
        } else {
			clog(__FILE__.'_'.__LINE__,$result);
            return false;
        }
    }

    public function sendToken($from, $privatekey, $toAddress, $amount, $token_address, $decimal)
    {
		$info = $this->findProvider();
        $provider = $info['provider'];
        $chainid = $info['chainid'];
        $gasPrice = $this->eth_gasPrice();
		
        $nonce = $this->eth_getTransactionCount($from);
        $data['to'] = $token_address;
        $data['value'] = '0x0';
        $data['data'] = '0xa9059cbb000000000000000000000000' . substr($toAddress, 2, 40);
		
        $new_val = bcmul($amount, bcpow("10", strval($decimal), 0), 0);
        $valueHex = base_convert($new_val, 10, 16);
        $zeroStr = '';
        for ($i = 1; $i <= (64 - strlen($valueHex)); $i++) {
            $zeroStr .= '0';
        }
        $data['data'] = $data['data'] . $zeroStr . $valueHex;

		$gasLimit = $this->eth_estimateGas($from, $data['to'], 0x0, $gasPrice, $data['value'], $data['data']);
		
        $transaction = new Transaction($nonce, $gasPrice, $gasLimit, $token_address, $data['value'], $data['data']);
		
        $raw = $transaction->getRaw($privatekey,hexdec($chainid));
		
        return $this->eth_sendRawTransaction('0x' . $raw);

    }

    private function hexAmount($decimal, float $amount): string
    {
        return '0x' . static::bcdechex(bcmul((string)$amount, bcpow('10', strval($decimal), 0), 0));
    }

    private function sanitizeAddress(string $address): string
    {
        $address = $this->sanitizeHex($address);
        if (strlen($address) !== 40) {
            E('Invalid address provided');
        }
        return $address;
    }

    public function sanitizeHex(string $hex): string
    {
        if (stripos($hex, '0x') === 0) {
            $hex = substr($hex, 2);
        }

        $length = strlen($hex);
        if (($length == 0)
            || (trim($hex, '0..9A..Fa..f') !== '')) {
            E('Invalid hex provided');
        }

        return $hex;
    }

    private static function bcdechex(string $dec): string
    {
        $end = bcmod($dec, '16');
        $remainder = bcdiv(bcsub($dec, $end), '16');
        return $remainder == 0 ? dechex((int)$end) : static::bcdechex($remainder) . dechex((int)$end);
    }

    private function checkRpcResult($data)
    {

        if (empty($data['error']) && !empty($data['result'])) {
            $result = $data['result'];
        } else {

            $result = $data;
        }
        return $result;
    }
}