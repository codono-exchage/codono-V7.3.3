<?php

namespace Api\Controller;

class CmcController extends CommonController
{
    private $url = "https://pro-api.coinmarketcap.com/v1/cryptocurrency/map?listing_status=active&CMC_PRO_API_KEY=".CMC_PRO_API_KEY;

    public function __construct()
    {
        parent::__construct();
    }

    public function cronData()
    {
        checkcronkey();
        $timestamp = time();
        echo "Starting to retrive at:" . $timestamp . "<br/>";


        $mo = M();
        $max_id = $mo->query('select MAX(`id`) as max from codono_ucid');
        $last_updated = $max_id[0]['max'];
        if ($last_updated > 0) {
            $trimmed_result = $last_updated;
        } else {
            $trimmed_result = 0;
        }
        echo "<br/>System has already $trimmed_result UCIDs saved<br/>";
        $request = $this->gcurl($this->url);

        if (!isset($request) || $request != null) {

            $response = json_decode($request, true);

            if ($response['data'] && $response['status']['error_code'] == 0) {

                $coins = $response['data'];
                if ($trimmed_result >= sizeof($coins)) {
                    echo "All records already added";
                    exit;
                }
                $i=0;
                foreach ($coins as $coin) {

                    if ($coin['id'] <= $trimmed_result) {
                        continue;
                    }


                    $info = $mo->table('codono_ucid')->where(array('symbol' => $coin['symbol']))->find();
                    if (!$info) {
                        $symbol = strtolower($coin['symbol']);
                        $if_saved = $mo->table('codono_ucid')->add($coin);
                        $coin_save = false;
                        foreach (C('coin') as $a_coin) {
                            $i++;
                            if ($a_coin['symbol'] == $symbol) {
                                $coin_save = M('Coin')->where(array('id' => $a_coin['id']))->save(array('sort' => $coin['id']));
                            } elseif ($a_coin['name'] == $symbol) {
                                $coin_save = M('Coin')->where(array('id' => $a_coin['id']))->save(array('sort' => $coin['id']));
                            }
                        }
                        echo "Saved status:$if_saved for coin status$coin_save <strong>" . $coin['name'] . "</strong> to DB<br/>";

                    }
                }
                if ($i > 0) {
                    S('home_coin', NULL);
                }

            } else {
                echo "<pre>";
                print_r($response);
                echo "</pre>";
                die('Error Reading Data');
            }
        } else {
            exit('Could not get response');
        }
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
            //return "cURL Error #:" . $err;
            return null;
        } else {
            return $response;
        }
    }

    public function Summary()
    {
        $markets=[];
        foreach (C('market') as $mark) {
            $xnb = explode('_', $mark['name'])[0];
            $rmb = explode('_', $mark['name'])[1];
            $markets[strtoupper($mark['name'])] =
                array(
                    'trading_pairs' => strtoupper($mark['name']),
                    'base_currency' => $rmb,
                    'quote_currency' => $xnb,
                    'last_price' => TrimTrailingZeroes(format_num($mark['new_price'])),
                    'lowest_ask' => TrimTrailingZeroes(format_num($mark['buy_price'])),
                    'highest_bid' => TrimTrailingZeroes(format_num($mark['sell_price'])),
                    'base_volume' =>  $mark['volume'],
                    'quote_volume' =>TrimTrailingZeroes(bcmul($mark['volume'],$mark['new_price'],8)),
                    'price_change_percent_24h' => $mark['change'],
                    'highest_price_24h' => TrimTrailingZeroes(format_num($mark['max_price'])),
                    'lowest_price_24h' => TrimTrailingZeroes(format_num($mark['min_price'])),
                );
        }
        $return = array(
            'code' => '200',
            'msg' => 'success',
            'data' => $markets);
        $this->jsonResp($return);
    }

    private function jsonResp($resp)
    {
        header('Content-type: application/json');
        echo json_encode($resp);
        exit;
    }

    public function old_Summary()
    {
        $cryptos = $this->crypto();
        foreach ($cryptos as $coin) {
            $coins[strtoupper($coin['name'])] =
                array(
                    'name' => $coin['title'],
                    'deposit' => $coin['zr_jz'] ? 'ON' : 'OFF',
                    'withdraw' => $coin['zr_jz'] ? 'ON' : 'OFF',
                );
        }
        foreach (C('market') as $mark) {
            $markets[strtoupper($mark['name'])] =
                array(
                    'id' => $mark['id'],
                    'last' => $mark['new_price'],
                    'lowestAsk' => $mark['sell_price'],
                    'highestBid' => $mark['buy_price'],
                    'percentChange' => $mark['change'],
                    'baseVolume' => $mark['volume'],
                    'quoteVolume' => $mark['volume'],
                    'isFrozen' => '0',
                    'high24hr' => $mark['max_price'],
                    'low24hr' => $mark['min_price'],
                );
        }
        $return = array(
            'code' => '200',
            'msg' => 'success',
            'data' => $markets,
            'coins' => $coins);
        $this->jsonResp($return);
    }

    private function crypto(): array
    {
        $coins=[];
        $coins_list = C('coin');
        foreach ($coins_list as $coin) {
            if ($coin['type'] != "rmb" && $coin['type'] != "offline") {
                $coins[] = $coin;
            }
        }
        return $coins;
    }

    public function assets()
    {
        $cryptos = $this->crypto();
        foreach ($cryptos as $coin) {
            $symbol = strtoupper($coin['name']);
            $coins[$symbol] =
                array(
                    "name" => $coin['title'],
                    "unified_cryptoasset_id" => $coin['sort'],
                    "can_withdraw" => (bool)$coin['zc_jz'],
                    "can_deposit" => (bool)$coin['zr_jz'],
                    "min_withdraw" => $coin['zc_min'],
                    "max_withdraw " => $coin['zc_max'],
                    "maker_fee" => $this->findFees($coin['name'], 1),
                    "taker_fee" => $this->findFees($coin['name'], 2)
                );
        }

        $return = array('code' => '200', 'msg' => 'success', 'data' => $coins);
        $this->jsonResp($return);
    }

    private function findFees($coin, $type = 1)
    {
        $market = C('market')[$coin . '_usdt'];
        if ($market) {
            if ($type == 1) {
                return $market['fee_buy'];
            } else {
                return $market['fee_sell'];
            }
        } else {
            return '0.01';
        }
    }

    public function ticker()
    {
    $market=[];
        foreach (C('market') as $market) {
            $symbol = strtoupper($market['name']);
            $xnb = explode('_', $market['name'])[0];
            $rmb = explode('_', $market['name'])[1];
            $markets[$symbol] =
                array(
                    //"market"=>$market['name'],
                    "base_id" => $this->getUCID($rmb),
                    "quote_id" => $this->getUCID($xnb),
                    "last_price" => format_num($market['new_price'], 8),
                    "quote_volume" => $this->getQuoteVol($market['volume'], $market['new_price']),
                    "base_volume" => $market['volume'],
                    "isFrozen" => 0,
                );
        }

        $return = array('code' => '200', 'msg' => 'success', 'data' => $markets);
        $this->jsonResp($return);
    }

    private function getUCID($symbol)
    {

        $symbol = strtolower($symbol);

        if (C('coin')[$symbol]['name'] == $symbol) {
            $ucid = C('coin')[$symbol]['sort'];
        } else {
            $ucid = null;
        }
        return $ucid;
    }

    private function getQuoteVol($vol, $lastprice)
    {
        if ($vol <= 0 || $lastprice <= 0) {
            return 0;
        }
        return bcdiv($vol, $lastprice, 8);
    }

    public function orderbook()
    {
        $market_pair = I('get.market_pair');
        $market = strtolower($market_pair);
        $market_info = C('market')[$market];
        if (!check($market, 'market') || !$market_info) {
            $this->localerror("Invalid market");

        }
        $info = $this->ob($market);
        $orderbook['timestamp'] = microtime(true);
        $orderbook['bids'] = $info['depth']['buy'];
        $orderbook['asks'] = $info['depth']['sell'];
        $return = array(
            'code' => '200',
            'msg' => 'success',
            'data' => $orderbook);
        $this->jsonResp($return);
    }

    private function localerror($msg)
    {
        $array['code'] = 0;
        $array['message'] = $msg;
        $array['data'] = null;
        exit(json_encode($array));
    }

    private function ob($market)
    {
        $trade_moshi = 1;
        $ajax = 'json';
        $market = strtolower($market);
        if (!C('market')[$market]) {
            return null;
        }

        $round = C('market')[$market]['round'] ? C('market')[$market]['round'] : 8;

        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->localerror(L('Incorrect Core Config'));
        } else {
            $codono_putong = $codono_getCoreConfig['codono_userTradeNum'];
            $codono_teshu = $codono_getCoreConfig['codono_specialUserTradeNum'];
        }


        $data_getDepth = (APP_DEBUG ? null : S('getActiveDepth' . $market));

        if (!isset($data_getDepth[$market][$trade_moshi])) {
            $limt = 15;
            $trade_moshi = intval($trade_moshi);
            $mo = M();
            if (C('market')[$market]['ext_charts'] == 1) {
                $lastupdateid = $mo->query('select MAX(`flag`) as flag from codono_trade where market =\'' . $market . '\' AND status=0 AND userid=0');
                $flag = $lastupdateid[0]['flag'];
            } else {
                $flag = 0;
            }

            if ($trade_moshi == 1 && $flag != 0) {
                $buy_query = 'select price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' and flag =\'' . $flag . '\' group by price order by price desc limit ' . $limt;
                $buy = $mo->query($buy_query);
                $sell = ($mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' and flag =\'' . $flag . '\' group by price order by price asc limit ' . $limt . ';'));
            }

            if ($trade_moshi == 1 && $flag == 0) {
                $buy_query = 'select price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt;

                $buy = $mo->query($buy_query);

                $sell = ($mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));

            }

            if ($trade_moshi == 3) {
                $buy = $mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
                $sell = null;
            }

            if ($trade_moshi == 4) {
                $buy = null;
                $sell = array_reverse($mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));
            }
            $data['buyvol'] = 0;
            $data['sellvol'] = 0;
            if (isset($buy)) {
                $data['buyvol'] = 0;
                foreach ($buy as $k => $v) {
                    $data['depth']['buy'][$k] = array($v['price'] > 1 ? $v['price'] : format_num($v['price'], $round), (float)($v['nums'] + 0));
                    $data['buyvol'] = bcadd($data['buyvol'], $v['nums'], 8);
                }

            } else {
                $data['depth']['buy'] = '';
            }

            if (isset($sell)) {
                $data['sellvol'] = 0;
                foreach ($sell as $k => $v) {

                    $data['depth']['sell'][$k] = array($v['price'] > 1 ? $v['price'] : format_num($v['price'], $round), (float)($v['nums'] + 0));
                    $data['sellvol'] = bcadd($data['sellvol'], $v['nums'], 8);
                }
            } else {
                $data['depth']['sell'] = '';
            }

            $data_getDepth[$market][$trade_moshi] = $data;
            S('getActiveDepth' . $market, $data_getDepth);
        } else {

            $data = $data_getDepth[$market][$trade_moshi];
        }

        return $data;

    }

    public function trades()
    {
        $market_pair = I('get.market_pair');
        $market = strtolower($market_pair);
        $market_info = C('market')[$market];
        if (!check($market, 'market') || !$market_info) {
            $this->localerror("Invalid market");
        }
        $info = $this->getTradelog($market);
        $return = array(
            'code' => '200',
            'msg' => 'success',
            'data' => $info);
        $this->jsonResp($return);
    }

    private function getTradelog($market)
    {

        $data = null;//(APP_DEBUG ? null : S('getTradelog' . $market));
        if (!$data) {

            $tradeLog = M('TradeLog')->where(array('status' => 1, 'market' => $market))->order('id desc')->limit(25)->select();

            if ($tradeLog) {
                foreach ($tradeLog as $k => $v) {
                    $data[$k]['trade_id'] = $v['id'];
                    $data[$k]['price'] = format_num($v['price']);
                    $data[$k]['quote_volume'] = format_num($v['num'], 8);
                    $data[$k]['base_volume'] = format_num($v['mum'], 8);
                    $data[$k]['timestamp'] = $v['addtime'] . '000';
                    $data[$k]['type'] = $this->getTradeLogType($v['type']);
                }

                S('getTradelog' . $market, $data);
            }
        }

        return $data;
    }

    private function getTradeLogType($type)
    {
        if ($type == 1) {
            return 'buy';
        } else {
            return 'sell';
        }
    }

    private function coinType($coinType): string
    {
        $types = array('qbb' => 'BTC Clone', 'esmart' => 'Sweep to Main Account', 'coinpay' => 'Sweep to Main Account');
        if (array_key_exists($coinType, $types)) {
            return $types[$coinType];
        } else {
            return "Sweep to Main Account";
        }
    }

    private function getBlockchain($coin, $tokenof = null)
    {

        if ($tokenof == 0 || !$tokenof) {
            return strtoupper($coin);
        } else {
            return strtoupper($tokenof);
        }
    }

}