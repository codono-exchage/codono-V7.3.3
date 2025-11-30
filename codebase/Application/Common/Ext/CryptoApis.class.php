<?php
/* *
DEMO RSA USED FOR ACCOUNT CREATION [Do not use it for production]
 -----BEGIN PUBLIC KEY-----
MIGeMA0GCSqGSIb3DQEBAQUAA4GMADCBiAKBgHn2dasJFHGE+t/3+auIE4UXrzDj
g/dPHSf+JSpxpwfaYpCCTvyEYkpgejebSLnwweYmlAO+jEm/J4e+VT2PzRPZoqF4
AFAeglGULG2QUfCs75rRrpOKS95z++cpvBIIrVSYCXpNPhtz8cx5Zq/tYTZ/lBWy
iuI1bTmTytdgvNobAgMBAAE=
-----END PUBLIC KEY-----
-----BEGIN RSA PRIVATE KEY-----
MIICWwIBAAKBgHn2dasJFHGE+t/3+auIE4UXrzDjg/dPHSf+JSpxpwfaYpCCTvyE
YkpgejebSLnwweYmlAO+jEm/J4e+VT2PzRPZoqF4AFAeglGULG2QUfCs75rRrpOK
S95z++cpvBIIrVSYCXpNPhtz8cx5Zq/tYTZ/lBWyiuI1bTmTytdgvNobAgMBAAEC
gYAugXow889l0g1PpeEANW0sDPHytG63uOUnQNOvMZM9fVqkO+wegeRw9ATme0Hq
FRH6zq8WFmysGkXajws15EWQRyTlOyIamuYyoV1drC9K1pdptn/eryuuVQ5o1rGo
JLN22dFXGoCB5COhYWOU/sFEuiGQnErxeHzX2eGp9bUpIQJBAOMAJjYol0PCTZj5
g91SGP2Y2OnjWyuL/sa97deT/MsoWZG79w9YtfR88ZGJ1F4m0W6kCzZfFpD9jJyT
PuX5yfkCQQCJiyQ+WJM1GENV0XXdEn8tVraeCmTHIhQo8TSp9sJh49hyqbM6DFxq
PFAGuoP4EEGBOv/qIsM6PiG4ckFEiemzAkBTRVW/HkrG/3sJt9ZIlPo35R8FRXLH
WbafX0LlhxL/z5Bz5njt90PgKQlQszflReYj6Sd3zY/wpiIzucwj/uq5AkBu9HP0
Z3e5KS2ImUQ/ZqB5bq46p5/MlE03Cf217n24ghklxofyl+4lNSpJg0TaZCKzoWi4
8/oRjfWd2W2VYLvJAkEAk1lXtUsNCkjyWK6RUyk95UJ3DHC2GBMDCuO8VWqsYTPo
v3b2YwCJSaDZUzw3QwZ0hoqh2n0XvNUkFzTs3jUaKg==
-----END RSA PRIVATE KEY-----
 */

namespace Common\Ext;
class CryptoApis
{

    protected string $network;
    protected array $headers;
    protected string $debug;
    protected string $callbackSecretKey;
    protected string $callbackUrl;
    protected float $evm_minimumTransferAmount;

    public function __construct($crypto_config)
    {
        $this->debug = true;
        $this->network = $crypto_config['network'];
        $this->headers = array(
            'Content-Type:application/json',
            'X-API-Key:' . $crypto_config['api_key']
        );
        $this->callbackSecretKey = 'Uy4yBZb2S3h2qwk4';
        //$this->callbackUrl = SITE_URL . 'IPN/callback_cryptoapis';
        $this->callbackUrl = 'https://rapid.codono.com/IPN/callback_cryptoapis';
        //Evm like eth based minimum auto transfer amount
        $this->evm_minimumTransferAmount = 0.001;
    }

    public function createAddress($symbol, $walletId, $context, $main_address)
    {
        $info = $this->findBlockchain($symbol);
        $blockchain = $info[0];
        $network = $info[1];
        $type = $info[2];

        if (!$blockchain || !$walletId) {
            return false;
        }
        if ($type == 2) {
            //forwarding address
            $address = $this->createForwardingAddress($symbol, $main_address, $context);
            if ($address) {
                $this->evmSubscribe($symbol, $address, $context);
                return $address;
            } else {
                return false;
            }


        } else {
            //non-forwarding address
            return $this->generateNewAddress($symbol, $walletId, $context);
        }

    }

    public function allowedSymbols()
    {
        return ['btc', 'bch', 'ltc', 'doge', 'dash', 'eth', 'bnb', 'etc', 'zil', 'zcash', 'xrp'];
    }

    private function evmSubscribe($symbol, $address, $context)
    {
        $info = $this->findBlockchain($symbol);
        $blockchain = $info[0];
        $network = $info[1];

        if (!$blockchain || !$address) {
            $this->localDebug(__CLASS__ . '|' . __FUNCTION__ . '|' . __LINE__, array("blockchain" => $blockchain, 'address' => $address));
            return false;
        }

        $context = (string)$context;

        $url = "https://rest.cryptoapis.io/v2/blockchain-events/$blockchain/$network/subscriptions/address-coins-transactions-confirmed?context=$context";
        $body =
            ["context" => "$context", 'data' =>
                ['item' =>
                    [
                        'address' => $address,
                        'allowDuplicates' => false,
                        'callbackSecretKey' => $this->callbackSecretKey,
                        'callbackURL' => $this->callbackUrl,
                        'receiveCallbackOn' => 2,
                    ]
                ]
            ];

        $json_resp = $this->request($url, $this->headers, $body, 'POST');

        $resp = json_decode($json_resp);
        if (!$json_resp || !$resp || !$resp->data->item->fromAddress || $resp->error) {
            $this->localDebug(__CLASS__ . '|' . __FUNCTION__ . '|' . __LINE__, $json_resp);
            return false;
        }
        return $resp->data->item->fromAddress;
    }

    /*
     * $json_data : post json data
     * $json_header: header json data
     */
    private function validateSignature($json_data, $json_header): int
    {

        $full_header = json_decode($json_header, true);
        $received_signature = $full_header['X-Signature'];

        $secret = $this->callbackSecretKey;
        $calculated_signature = hash_hmac('sha256', $json_data, $secret);
        if ($received_signature == $calculated_signature) {
            return 1;
        } else {
            return 0;
        }
    }

    public function decodeIncomingTx($json_data, $json_header)
    {
        $if_valid = $this->validateSignature($json_data, $json_header);
        if ($if_valid == 1) {
            $decode_data = json_decode($json_data);
            $data = $decode_data->data;

            if ($data->product == 'BLOCKCHAIN_EVENTS' && $data->event == 'ADDRESS_COINS_TRANSACTION_CONFIRMED' && $data->item->direction == 'incoming') {
                $item = $data->item;
                return ['blockchain' => $item->blockchain, 'network' => $item->network, 'address' => $item->address, 'txid' => $item->transactionId, 'amount' => $item->amount, 'unit' => $item->unit];
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    private function findBlockchain($symbol)
    {
        $symbol = strtolower($symbol);
        $compatible_network = $this->network;
        switch ($symbol) {
            case 'bitcoin':
            case 'btc':
                return ['bitcoin', $compatible_network, 1];

            case 'ethereum':
            case 'eth':
                if ($compatible_network != 'mainnet') {
                    $compatible_network = 'ropsten';
                }
                return ['ethereum', $compatible_network, 2];
            case 'xrp':
            case 'ripple':
                return ['xrp', $compatible_network, 1];

            case 'litecoin':
            case 'ltc':
                return ['litecoin', $compatible_network, 1];

            case 'dash':
                return ['dash', $compatible_network, 1];

            case 'doge':
            case 'dogecoin':
                return ['dogecoin', $compatible_network, 1];

            case 'etc':
            case 'ethereum-classic':
                return ['ethereum-classic', $compatible_network, 2];

            case 'zcash':
                return ['zcash', $compatible_network, 1];

            case 'bitcoin-cash':
            case 'bch':
                return ['bitcoin-cash', $compatible_network, 1];

            case 'zilliqa':
            case 'zil':
                return ['zilliqa', $compatible_network, 1];
            case 'binance-smart-chain':
            case 'bnb':
                return ['binance-smart-chain', $compatible_network, 2];
            default:
                return false;
        }
    }

    public function reverseBlockchain($symbol)
    {
        $symbol = strtolower($symbol);
        $compatible_network = $this->network;
        switch ($symbol) {
            case 'bitcoin':
            case 'btc':
                return ['btc', $compatible_network, 1];

            case 'ethereum':
            case 'eth':
                if ($compatible_network != 'mainnet') {
                    $compatible_network = 'ropsten';
                }
                return ['eth', $compatible_network, 2];
            case 'xrp':
            case 'ripple':
                return ['xrp', $compatible_network, 1];

            case 'litecoin':
            case 'ltc':
                return ['ltc', $compatible_network, 1];

            case 'dash':
                return ['dash', $compatible_network, 1];

            case 'doge':
            case 'dogecoin':
                return ['doge', $compatible_network, 1];

            case 'etc':
            case 'ethereum-classic':
                return ['etc', $compatible_network, 2];

            case 'zcash':
                return ['zcash', $compatible_network, 1];

            case 'bitcoin-cash':
            case 'bch':
                return ['bch', $compatible_network, 1];

            case 'zilliqa':
            case 'zil':
                return ['zil', $compatible_network, 1];
            case 'binance-smart-chain':
            case 'bnb':
                return ['bnb', $compatible_network, 2];
            default:
                return false;
        }
    }

    /*
     * For ETH and EVM
     */
    private function createForwardingAddress($symbol, $main_address, $context)
    {
        $info = $this->findBlockchain($symbol);
        $blockchain = $info[0];
        $network = $info[1];

        if (!$blockchain || !$main_address) {
            $this->localDebug(__CLASS__ . '|' . __FUNCTION__ . '|' . __LINE__, array("blockchain" => $blockchain, 'main_address' => $main_address));
            return false;
        }

        $context = (string)$context;

        $url = "https://rest.cryptoapis.io/v2/blockchain-automations/$blockchain/$network/coins-forwarding/automations?context=$context";
        $body =
            ["context" => "$context", 'data' =>
                ['item' =>
                    ['callbackSecretKey' => $this->callbackSecretKey,
                        'callbackUrl' => $this->callbackUrl,
                        'confirmationsCount' => 2,
                        'feePriority' => 'slow',
                        'minimumTransferAmount' => "$this->evm_minimumTransferAmount",
                        'toAddress' => "$main_address"
                    ]
                ]
            ];

        $json_resp = $this->request($url, $this->headers, $body, 'POST');

        $resp = json_decode($json_resp);
        if (!$json_resp || !$resp || !$resp->data->item->fromAddress || $resp->error) {
            $this->localDebug(__CLASS__ . '|' . __FUNCTION__ . '|' . __LINE__, $json_resp);
            return false;
        }
        return $resp->data->item->fromAddress;
    }

    private function localDebug($name, $data)
    {
        $name = $name ?: 'CryptoApis';
        if ($this->debug) {
            dblog($name, $data);
        }
    }

    private function request($url, $header, $data, $method = 'POST')
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }

    private function generateNewAddress($symbol, $walletId, $context)
    {
        $info = $this->findBlockchain($symbol);
        $blockchain = $info[0];
        $network = $info[1];

        if (!$blockchain || !$walletId) {
            return false;
        }

        $context = (string)$context;
        $url = "https://rest.cryptoapis.io/v2/wallet-as-a-service/wallets/$walletId/$blockchain/$network/addresses?context=$context";

        $body = ["context" => "$context", 'data' => ['item' => ['label' => $context]]];

        $json_resp = $this->request($url, $this->headers, $body);
        $resp = json_decode($json_resp);
        if (!$json_resp || !$resp || !$resp->data->item->address || $resp->error) {
            $this->localDebug(__CLASS__ . '|' . __FUNCTION__ . '|' . __LINE__, $json_resp);
            return false;
        }
        return $resp->data->item->address;
    }

    /*
     * Setup Call Run Once only , This will setup a master address where all the funds will be transfered to, Such a feature is required for
     * ethereum like EVM chains only
     */

    public function withdraw($symbol, $walletId, $main_address, $to_address, $amount, $tx_note, $context, $contractAddress = null)
    {
        $info = $this->findBlockchain($symbol);
        $blockchain = $info[0];
        $network = $info[1];
        $type = $info[2];

        if (!$blockchain || !$walletId) {
            return false;
        }
        if ($type == 2) {
            //forwarding address based on evm
            return $this->transferFromAddress($symbol, $walletId, $main_address, $to_address, $amount, $tx_note, $context, $contractAddress);
        } else {
            //non-forwarding address
            return $this->transferFromWallet($symbol, $walletId, $to_address, $amount, $tx_note, $context);
        }
    }

    private function transferFromAddress($symbol, $walletId, $main_address, $to_address, $amount, $tx_note = null, $context = null, $contractAddress = null)
    {
        $info = $this->findBlockchain($symbol);
        $blockchain = $info[0];
        $network = $info[1];
        if (!$blockchain || !$main_address || !$to_address || !$amount) {
            $this->localDebug('auto', array("blockchain" => $blockchain, 'main_address' => $main_address));
            return false;
        }

        $context = (string)$context;

        $url = "https://rest.cryptoapis.io/v2/wallet-as-a-service/wallets/$walletId/$blockchain/$network/addresses/$main_address/transaction-requests?context=$context";
        if ($contractAddress) {
            $body =
                ["context" => "$context", 'data' =>
                    ['item' =>
                        [
                            'amount' => "$amount",
                            'callbackSecretKey' => $this->callbackSecretKey,
                            'callbackUrl' => $this->callbackUrl,
                            'feePriority' => 'slow',
                            'note' => $tx_note,
                            'recipientAddress' => "$to_address",
                            'tokenIdentifier' => "$contractAddress"
                        ]
                    ]
                ];
        } else {
            $body =
                ["context" => "$context", 'data' =>
                    ['item' =>
                        [
                            'amount' => "$amount",
                            'callbackSecretKey' => $this->callbackSecretKey,
                            'callbackUrl' => $this->callbackUrl,
                            'feePriority' => 'slow',
                            'note' => $tx_note,
                            'recipientAddress' => "$to_address"
                        ]
                    ]
                ];
        }


        $json_resp = $this->request($url, $this->headers, $body, 'POST');

        $resp = json_decode($json_resp);
        if (!$json_resp || !$resp || !$resp->data->item || $resp->error) {
            $this->localDebug('CryptoApis-transferFromAddress', $json_resp);
            return false;
        }
        return $resp->data->item;
    }

    private function transferFromWallet($symbol, $walletId, $to_address, $amount, $tx_note = null, $context = null)
    {
        $info = $this->findBlockchain($symbol);
        $blockchain = $info[0];
        $network = $info[1];
        if (!$blockchain || !$walletId || !$to_address || !$amount) {
            $this->localDebug('auto', array("blockchain" => $blockchain, 'walletId' => $walletId));
            return false;
        }

        $context = (string)$context;

        $url = "https://rest.cryptoapis.io/v2/wallet-as-a-service/wallets/$walletId/$blockchain/$network/transaction-requests?context=$context";

        $body =
            ["context" => "$context",
                'data' =>
                    ['item' =>
                        [
                            'callbackSecretKey' => $this->callbackSecretKey,
                            'callbackUrl' => $this->callbackUrl,
                            'feePriority' => 'slow',
                            'note' => $tx_note,
                            'recipients' => [array(
                                'address' => "$to_address",
                                'amount' => "$amount"
                            )]
                        ]
                    ]
            ];

        $json_resp = $this->request($url, $this->headers, $body, 'POST');

        $resp = json_decode($json_resp);
        if (!$json_resp || !$resp || !$resp->data->item || $resp->error) {
            $this->localDebug('CryptoApis-transferFromAddress', $json_resp);
            return false;
        }
        return $resp->data->item;
    }

    /*
     * Create Forwarding of Token balance from existing account to main account
     */
    public function createTokenForwarding($symbol, $main_address, $fromAddress, $contractAddress, $context)
    {
        $info = $this->findBlockchain($symbol);
        $blockchain = $info[0];
        $network = $info[1];

        if (!$blockchain || !$main_address) {
            $this->localDebug(__CLASS__ . __FUNCTION__, array("blockchain" => $blockchain, 'main_address' => $main_address));
            return false;
        }

        $context = (string)$context;

        $url = "https://rest.cryptoapis.io/v2/blockchain-automations/$blockchain/$network/tokens-forwarding/automations/add-token?context=$context";

        $body =
            ["context" => "$context", 'data' =>
                ['item' =>
                    ['callbackSecretKey' => $this->callbackSecretKey,
                        'callbackUrl' => $this->callbackUrl,
                        'confirmationsCount' => 2,
                        'feePriority' => 'slow',
                        'fromAddress' => $fromAddress,
                        'minimumTransferAmount' => "$this->evm_minimumTransferAmount",
                        'toAddress' => "$main_address",
                        'tokenData' => ['contractAddress ' => $contractAddress],
                    ]
                ]
            ];

        $json_resp = $this->request($url, $this->headers, $body, 'POST');

        $resp = json_decode($json_resp);
        if (!$json_resp || !$resp || !$resp->data->item || $resp->error) {
            $this->localDebug(__CLASS__ . '|' . __FUNCTION__ . '|' . __LINE__, $json_resp);
            return false;
        }
        return $resp->data->item;
    }

    public function listTransactions($symbol, $walletId, $context, $offset = 0, $limit = 50)
    {
        $info = $this->findBlockchain($symbol);
        $blockchain = $info[0];
        $network = $info[1];

        if (!$blockchain || !$walletId) {
            return false;
        }


        $context = (string)$context;

        $url = "https://rest.cryptoapis.io/v2/wallet-as-a-service/wallets/$walletId/$blockchain/$network/transactions?context=$context&limit=$limit&offset=$offset";
        $body = [];

        $json_resp = $this->request($url, $this->headers, $body, 'GET');
        $resp = json_decode($json_resp);
        if (!$json_resp || !$resp || !$resp->data->items || $resp->error) {
            $this->localDebug(__CLASS__ . '|' . __FUNCTION__ . '|' . __LINE__, $json_resp);
            return false;
        }
        return $resp->data->items;
    }

}