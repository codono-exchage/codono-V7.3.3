<?php
namespace Common\Ext;

/**
 * CoinpaymentsCurlRequest and CoinpaymentsValidator are also combined in this file
 * CoinPayments.net PHP API Wrapper
 *
 * @link https://www.coinpayments.net/apidoc Official CoinPayments.net API Documentation.
 * @copyright (c) 2018, CoinPayments.net
 *
 * Many API commands require a currency or currencies values to be passed. These are always in the
 * format of a currency ticker code. See https://www.coinpayments.net/supported-coins for these ticker codes,
 * located in the CODE column.
 *
 * LTCT (Litecoin Testnet) is a test currency with no value and should be used for development purposes when possible
 * in order to save on real currency network transaction fees.
 *
 * The commands for converting coins and getting coin conversion information rely on mainnet transactions of a
 * non-testing currency and are excluded from the capabilities of LTCT in the CoinPayments API.
 *
 * $PayByName tag commands also rely on having available or claimed tags on the production CoinPayments.net website to
 * manipulate.
 */
class CoinpaymentsAPI
{
    private $private_key = '';
    private $public_key = '';
    private $request_handler;
    private $format;

    /**
     * CoinpaymentsAPI constructor.
     * @param $private_key
     * @param $public_key
     * @param $format
     */
    public function __construct($private_key, $public_key, $format)
    {

        // Set the default format to json if a value was not passed
        if (empty($format)) {
            $format = 'json';
        }

        // Set keys and format passed to class
        $this->private_key = $private_key;
        $this->public_key = $public_key;
        $this->format = $format;

        // Throw an error if the keys are not both passed
        try {
            if (empty($this->private_key) || empty($this->public_key)) {
                //throw new Exception("Your private and public keys are not both set!");
                E("Your private and public keys are not both set!");
            }
        } catch (Exception $e) {
            //echo 'Error: ' . $e->getMessage();
            clog('coinpay','Error: ' . $e->getMessage());
        }

        // Initiate a cURL request object
        $this->request_handler = new CoinpaymentsCurlRequest($this->private_key, $this->public_key, $format);
    }

    /** ------------------------------------------------ **/
    /** ------------ Informational Commands ------------ **/
    /** ------------------------------------------------ **/

    /**
     * function GetBasicInfo
     * Get basic account information.
     *
     * @return array|object
     * Successful result includes the following values:
     *      - username (string)
     *      - merchant_id (string)
     *      - email (string)
     *      - public_name (string)
     *
     * @throws Exception
     */
    public function GetBasicInfo()
    {
        return $this->request_handler->execute('get_basic_info');
    }

    /**
     * function GetRates
     * Basic call to get rates, with full currency names and no reference to if the coin is enabled.
     *
     * @return array|object
     * Successful result includes the following values (sample):
     *      - Currency ticker (example string: BTC)
     *          - is_fiat (integer)
     *          - rate_btc (string)
     *          - last_update (string)
     *          - tx_fee (string)
     *          - status (string)
     *          - name (string)
     *          - confirms (string)
     *          - can_convert (integer)
     *          - capabilities (array)
     *              - [payments, wallet, transfers, dest_tag, convert] (each a string)
     *
     * @throws Exception
     */
    public function GetRates()
    {
        return $this->request_handler->execute('rates');
    }

    /**
     * function GetRatesWithAccepted
     * Call to get rates, with full currency names and reference to if the coin is enabled.
     *
     * @return array|object
     * Successful result includes the following values (sample):
     *      - Currency ticker (example string: BTC)
     *          - is_fiat (integer)
     *          - rate_btc (string)
     *          - last_update (string)
     *          - tx_fee (string)
     *          - status (string)
     *          - name (string)
     *          - confirms (string)
     *          - can_convert (integer)
     *          - capabilities (array)
     *              - [payments, wallet, transfers, dest_tag, convert] (each a string)
     *          - accepted
     *
     * @throws Exception
     */
    public function GetRatesWithAccepted()
    {
        $fields = [
            'accepted' => 1
        ];
        return $this->request_handler->execute('rates', $fields);
    }

    /**
     * function GetShortRates
     * Call to get short rates, without full currency names or confirms and no reference to if the coin is enabled.
     *
     * @return array|object
     * Successful result includes the following values (sample):
     *      - Currency ticker (example string: BTC)
     *          - is_fiat (integer)
     *          - rate_btc (string)
     *          - last_update (string)
     *          - tx_fee (string)
     *          - status (string)
     *          - capabilities (array)
     *              - [payments, wallet, transfers, dest_tag, convert] (each a string)
     *
     * @throws Exception
     */
    public function GetShortRates()
    {
        $fields = [
            'short' => 1
        ];
        return $this->request_handler->execute('rates', $fields);
    }

    /**
     * function GetShortRatesWithAccepted
     * Call to get short rates, without full currency names or confirms and with reference to if the coin is enabled.
     *
     * @return array|object
     * Successful result includes the following values (sample):
     *     - Currency ticker (example string: BTC)
     *          - is_fiat (integer)
     *          - rate_btc (string)
     *          - last_update (string)
     *          - tx_fee (string)
     *          - status (string)
     *          - capabilities (array)
     *              - [payments, wallet, transfers, dest_tag, convert] (each a string)
     *          - accepted (integer)
     *
     * @throws Exception
     */
    public function GetShortRatesWithAccepted()
    {
        $fields = [
            'short' => 1,
            'accepted' => 1
        ];
        return $this->request_handler->execute('rates', $fields);
    }

    /**
     * function GetCoinBalances
     * Get balances of only coins with a positive balance.
     *
     * @return array|object
     * Successful result includes the following values (sample) for each coin.
     *      - Currency ticker (example string: BTC)
     *          - balance (10000000) | The coin balance as an integer in Satoshis. (integer)
     *          - balancef (0.10000000) | The coin balance as a floating point number. (string)
     *          - status (string) | If the coin is locked in the vault or available.
     *          - coin_status (string) | If the coin is online or offline for maintenance.
     *
     * @throws Exception
     */
    public function GetCoinBalances()
    {
        return $this->request_handler->execute('balances');
    }

    /**
     * function GetAllCoinBalances
     * Get balances of all coins, even those with a 0 balance.
     *
     * @return array|object
     * Successful result includes the following values (sample) for each coin.
     *      - Currency ticker (example string: BTC)
     *          - balance (10000000) | The coin balance as an integer in Satoshis. (integer)
     *          - balancef (0.10000000) | The coin balance as a floating point number. (string)
     *          - status (string) | If the coin is locked in the vault or available.
     *          - coin_status (string) | If the coin is online or offline for maintenance.
     *
     * @throws Exception
     */
    public function GetAllCoinBalances()
    {
        $fields = [
            'all' => 1
        ];
        return $this->request_handler->execute('balances', $fields);
    }

    /**
     * function GetDepositAddress
     * Get addresses for personal use deposits.
     * Deposits to these addresses don't send IPNs!
     * For commercial-use addresses and/or ones that send IPNs see GetCallBackAddresses function.
     *
     * @param string $currency The ticker of currency the buyer will be sending.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - address (string)
     *      - pubkey | (string) NXT Only: The pubkey to attach the 1st time you send to the address to activate it.
     *      - dest_tag | (string|integer) For coins needing a destination tag, example: XRP (Ripple)
     *
     * @throws Exception
     */
    public function GetDepositAddress($currency)
    {
        $fields = [
            'currency' => $currency
        ];
        return $this->request_handler->execute('get_deposit_address', $fields);
    }

    /** ------------------------------------------------ **/
    /** -------------- Receiving Payments -------------- **/
    /** ------------------------------------------------ **/

    /**
     * function CreateSimpleTransaction
     * Use this function to create a transaction when you only want to specify the minimum fields
     * and do not require a currency conversion.
     *
     * @param integer $amount The amount for the transaction in the $currency.
     * @param string $currency The ticker of the currency for the transaction.
     * @param string $buyer_email Email address for the buyer of the transaction.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - amount (string)
     *      - address (string)
     *      - txn_id (string)
     *      - confirms_needed (string)
     *      - timeout (integer)
     *      - status_url (string)
     *      - qrcode_url (string)
     *
     * @throws Exception
     */
    public function CreateSimpleTransaction($amount, $currency, $buyer_email)
    {
        $fields = [
            'amount' => $amount,
            'currency1' => $currency,
            'currency2' => $currency,
            'buyer_email' => $buyer_email
        ];
        return $this->request_handler->execute('create_transaction', $fields);
    }

    /**
     * function CreateSimpleTransactionWithConversion
     * Use this function to create a transaction when you only want to specify the minimum fields
     * and require a currency conversion. For example if your products are priced in USD but you
     * are receiving BTC, you would use currency1 = USD and currency2 = BTC.
     *
     * @param integer $amount The amount in the original currency (currency1)
     * @param string $currency1 Original currency ticker for the transaction.
     * @param string $currency2 Actual currency ticker the buyer will send.
     * @param string $buyer_email Email address for the buyer of the transaction.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - amount (string)
     *      - address (string)
     *      - txn_id (string)
     *      - confirms_needed (string)
     *      - timeout (integer)
     *      - status_url (string)
     *      - qrcode_url (string)
     *
     * @throws Exception
     */
    public function CreateSimpleTransactionWithConversion($amount, $currency1, $currency2, $buyer_email)
    {
        $fields = [
            'amount' => $amount,
            'currency1' => $currency1,
            'currency2' => $currency2,
            'buyer_email' => $buyer_email
        ];
        return $this->request_handler->execute('create_transaction', $fields);
    }

    /**
     * function CreateComplexTransaction
     * Use this function to create a transaction when you only want to specify all optional fields
     * and require a currency conversion. For example if your products are priced in USD but you
     * are receiving BTC, you would use currency1 = USD and currency2 = BTC. To use this function
     * without a currency conversion, simply set currency1 and currency2 to the same value.
     *
     * @param integer $amount The amount in the original currency (currency1)
     * @param string $currency1 Original currency ticker for the transaction.
     * @param string $currency2 Actual currency ticker the buyer will send.
     * @param string $buyer_email Email address for the buyer of the transaction.
     * @param string $address An address in currency2's network.
     * @param string $buyer_name A buyer name for the seller's reference.
     * @param string $item_name An item name to associate with the transaction.
     * @param integer $item_number An item number to associate with the transaction.
     * @param string $invoice An invoice identifying string to associate with the transaction.
     * @param string $custom A custom field for the seller to populate with extra information.
     * @param string $ipn_url The URL for instant payment notifications to be sent to regarding this transaction.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - amount (string)
     *      - address (string)
     *      - txn_id (string)
     *      - confirms_needed (string)
     *      - timeout (integer)
     *      - status_url (string)
     *      - qrcode_url (string)
     *
     * @throws Exception
     */
    public function CreateComplexTransaction($amount, $currency1, $currency2, $buyer_email, $address, $buyer_name, $item_name, $item_number, $invoice, $custom, $ipn_url)
    {
        $fields = [
            'amount' => $amount,
            'currency1' => $currency1,
            'currency2' => $currency2,
            'buyer_email' => $buyer_email,
            'address' => $address,
            'buyer_name' => $buyer_name,
            'item_name' => $item_name,
            'item_number' => $item_number,
            'invoice' => $invoice,
            'custom' => $custom,
            'ipn_url' => $ipn_url
        ];
        return $this->request_handler->execute('create_transaction', $fields);
    }

    /**
     * function CreateCustomTransaction
     * Use this function when you want to specify some but not all of the optional fields.
     *
     * @param array $fields The following required fields, with your chosen combination of optional fields.
     *      - field_name (required)
     *      -----------------------
     *        - amount (Yes)
     *        - currency1 (Yes)
     *        - currency2 (Yes)
     *        - buyer_email (Yes)
     *        - address (No)
     *        - buyer_name (No)
     *        - item_name (No)
     *        - item_number (No)
     *        - invoice (No)
     *        - custom (No)
     *        - ipn_url (No)
     *
     * @return array|object
     * Successful result includes the following values.
     *      - amount (string)
     *      - address (string)
     *      - txn_id (string)
     *      - confirms_needed (string)
     *      - timeout (integer)
     *      - status_url (string)
     *      - qrcode_url (string)
     *
     * @throws Exception
     */
    public function CreateCustomTransaction($fields)
    {
        return $this->request_handler->execute('create_transaction', $fields);
    }

    /**
     * function GetOnlyCallbackAddress
     * Since callback addresses are designed for commercial use they incur the same 0.5% fee on deposits as
     * transactions created with 'create_transaction' command. For personal use deposits that reuse the same personal
     * address(es) in your wallet that have no fee but don't send IPNs see GetDepositAddress function.
     *
     * Note: Will default to using IPN URL if there is one set on your Edit Settings page.
     *
     * @param string $currency The ticker of the currency for the callback address.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - address (string)
     *      - pubkey | (string) NXT Only: The pubkey to attach the 1st time you send to the address to activate it.
     *      - dest_tag | (string|integer) For coins needing a destination tag, example: XRP (Ripple)
     *
     * @throws Exception
     */
    public function GetOnlyCallbackAddress($currency)
    {
        $fields = [
            'currency' => $currency
        ];
        return $this->request_handler->execute('get_callback_address', $fields);
    }

    /**
     * function GetCallbackAddressWithIpn
     * Same as GetOnlyCallbackAddress function but includes an IPN url for your callbacks.
     *
     * @param string $currency The ticker of the currency for the callback address.
     * @param string $ipn_url Your Instant Payment Notifications server URL to be associated with the address.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - address (string)
     *      - pubkey | (string) NXT Only: The pubkey to attach the 1st time you send to the address to activate it.
     *      - dest_tag | (string|integer) For coins needing a destination tag, example: XRP (Ripple)
     *
     * @throws Exception
     */
    public function GetCallbackAddressWithIpn($currency, $ipn_url)
    {
		if($currency=='USDT'){
			$currency='USDT.ERC20';
		}
        $fields = [
            'currency' => $currency,
            'ipn_url' => $ipn_url
        ];
        return $this->request_handler->execute('get_callback_address', $fields);

    }

    /**
     * function GetTxInfoMulti
     * Retrieves transaction information for up to 25 individual transaction IDs.
     * The API key being used must belong to the seller of the transaction(s).
     *
     * Important note: It is recommended to handle IPNs instead of using this command when possible,
     *                 it is more efficient and places less load on our servers.
     *
     *
     * @param string $transaction_ids Multiple transaction IDs separated with a pipe symbol: |
     *        example: aFakeTxId1|aFakeTxId2|aFakeTxId3
     *
     * @return array|object
     * See GetTxInfoSingle result values.
     *
     * @throws Exception
     */
    public function GetTxInfoMulti($transaction_ids)
    {
        $fields = [
            'txid' => $transaction_ids
        ];
        return $this->request_handler->execute('get_tx_info_multi', $fields);
    }

    /**
     * function GetTxInfoSingle
     * Retrieves transaction information for a single transaction.
     *
     * Important note: It is recommended to handle IPNs instead of using this command when possible,
     *                 it is more efficient and places less load on our servers.
     *
     * @param string $transaction_id The transaction ID for which you want to retrieve information.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - error (string)
     *      - time_created (integer)
     *      - time_expires (integer)
     *      - status (integer)
     *      - status_text (string)
     *      - type (string)
     *      - coin (string)
     *      - amount (integer)
     *      - amountf (string)
     *      - received (integer)
     *      - receivedf (string)
     *      - recv_confirms (integer)
     *      - payment_address (string)
     *      - time_completed (integer)
     *
     * @throws Exception
     */
    public function GetTxInfoSingle($transaction_id)
    {
        $fields = [
            'txid' => $transaction_id
        ];
        return $this->request_handler->execute('get_tx_info', $fields);
    }

    /**
     * function GetTxInfoSingleWithRaw
     * Retrieves transaction information for a single transaction including raw checkout and shipping data.
     *
     * Important note: It is recommended to handle IPNs instead of using this command when possible,
     *                 it is more efficient and places less load on our servers.
     *
     * @param string $transaction_id The transaction ID for which you want to retrieve information.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - time_created (integer)
     *      - time_expires (integer)
     *      - status (integer)
     *      - status_text (string)
     *      - type (string)
     *      - coin (string)
     *      - amount (integer)
     *      - amountf (string)
     *      - received (integer)
     *      - receivedf (string)
     *      - recv_confirms (integer)
     *      - payment_address (string)
     *      - time_completed (integer)
     *      - checkout (array)
     *          - subtotal (integer)
     *          - tax (integer)
     *          - shipping (integer)
     *          - total (integer)
     *          - currency (string)
     *          - amount (integer)
     *          - item_name (string)
     *          - item_number (string)
     *          - invoice (string)
     *          - custom (string)
     *          - ipn_url (string)
     *          - amountf (double/float)
     *     - shipping (array)
     *          - first_name (string)
     *          - last_name (string)
     *          - company (string)
     *          - address1 (string)
     *          - address2 (string)
     *          - city (string)
     *          - state (string)
     *          - zip (string)
     *          - country (string)
     *          - phone (string)
     *
     * @throws Exception
     */
    public function GetTxInfoSingleWithRaw($transaction_id)
    {
        $fields = [
            'txid' => $transaction_id,
            'full' => 1
        ];
        return $this->request_handler->execute('get_tx_info', $fields);
    }

    /**
     * function GetSellerTransactionList
     * Retrieves a list of transaction IDs only where you are the seller.
     *
     * Important note: It is recommended to handle IPNs instead of using this command when possible,
     *                 it is more efficient and places less load on our servers.
     *
     * @param int $limit The maximum number of transaction IDs to return from 1-100.
     * @param int $start The transaction number to start from.
     * @param int $newer A Unix timestamp that when defined will return transactions from that time or later.
     *
     * @return array|object The transaction IDs (string) found in the given range.
     *
     * @throws Exception
     */
    public function GetSellerTransactionList($limit = 25, $start = 0, $newer = 0)
    {
        $fields = [
            'limit' => $limit,
            'start' => $start,
            'newer' => $newer
        ];
        return $this->request_handler->execute('get_tx_ids', $fields);
    }

    /**
     * function GetFullTransactionList
     * Retrieves a list of transaction IDs where you are the seller or buyer.
     *
     * Important note: It is recommended to handle IPNs instead of using this command when possible,
     *                 it is more efficient and places less load on our servers.
     *
     * @param int $limit The maximum number of transaction IDs to return from 1-100.
     * @param int $start The transaction number to start from.
     * @param int $newer A Unix timestamp that when defined will return transactions from that time or later.
     *
     * @return array|object Contains an array for each transaction ID
     *      - txid (string)
     *      - user_is (string) If the user is the seller or buyer.
     *
     * @throws Exception
     */
    public function GetFullTransactionList($limit = 100, $start = 0, $newer = 0)
    {
        $fields = [
            'limit' => $limit,
            'start' => $start,
            'newer' => $newer,
            'all' => 1
        ];
        return $this->request_handler->execute('get_tx_ids', $fields);
    }

    /** ------------------------------------------------ **/
    /** ------------ Withdrawals/Transfers ------------- **/
    /** ------------------------------------------------ **/

    /**
     * function CreateMerchantTransfer
     *
     * @param int $amount The amount of the transfer in the given $currency.
     * @param string $currency The currency for the transfer.
     * @param string $merchant The merchant ID (not a username) for the user that will receive the transfer.
     * @param int $autoconfirm Set to 1 to withdraw without an email confirmation.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - id (string)
     *      - status (integer)
     *      - amount (string)
     *
     * @throws Exception
     */
    public function CreateMerchantTransfer($amount, $currency, $merchant, $autoconfirm = 0)
    {
        $fields = [
            'amount' => $amount,
            'currency' => $currency,
            'merchant' => $merchant,
            'auto_confirm' => $autoconfirm
        ];
        return $this->request_handler->execute('create_transfer', $fields);
    }

    /**
     * function CreatePayByNameTransfer
     *
     * @param int $amount The amount of the transfer in the given $currency.
     * @param string $currency The currency for the transfer.
     * @param string $paybyname The $PayByName tag that will recieve the transfer.
     * @param int $autoconfirm Set to 1 to withdraw without an email confirmation.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - id (string)
     *      - status (integer)
     *      - amount (string)
     *
     * @throws Exception
     */
    public function CreatePayByNameTransfer($amount, $currency, $paybyname, $autoconfirm = 0)
    {
        $fields = [
            'amount' => $amount,
            'currency' => $currency,
            'pbntag' => $paybyname,
            'auto_confirm' => $autoconfirm
        ];
        return $this->request_handler->execute('create_transfer', $fields);
    }

    /**
     * function CreateWithdrawal
     * Withdrawals send coins to a specified address or $PayByName tag over the coin networks and optionally send
     * an IPN when complete. If you are sending to another CoinPayments user and you have their $PayByName tag or
     * merchant ID you can also use 'create_transfer' for faster sends that don't go over the coin networks when possible.
     *
     * @param array $fields Parameters to pass with the withdrawal command. See below.
     *     - amount (integer) The amount of the withdrawal.
     *     - currency (string) The ticker of the currency to withdraw.
     *     - add_tx_fee (integer) Set to 1 to add the coin TX fee to the withdrawal amount, so that the sender pays the TX fee
     *                        instead of the receiver.
     *
     *     - currency2 (string) Optional currency to use to to withdraw 'amount' worth of 'currency2' in 'currency' coin.
     *                          This is for exchange rate calculation only and will not convert coins or change which
     *                          currency is withdrawn. For example, to withdraw 1.00 USD worth of BTC you would specify
     *                          currency1=BTC, currency2=USD, and amount=1.00.
     *
     *     - address (string) The address to send the funds to, either this OR paybyname must be specified. Must be an
     *                        address in currency1's network.
     *
     *     - pbntag (string) A $PayByName tag to send the withdrawal to, either this OR address must be specified.
     *     - dest_tag (string|integer) The extra tag to use if applicable to $currency1 (destination tag).
     *                         This could be a Destination Tag for Ripple, Payment ID for Monero, Message for XEM, etc.
     *
     *     - ipn_url (string) URL for your IPN callbacks. If not set it will use the IPN URL in your Edit Settings page if you have one set.
     *     - auto_confirm (integer) If set to 1, withdrawal will complete without email confirmation.
     *     - note (string) This lets you set the note for the withdrawal.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - id (string)
     *      - status (integer)
     *      - amount (string)
     *
     * @throws Exception
     */
    public function CreateWithdrawal($fields)
    {
        if (!empty($fields['address']) && !empty($fields['paybyname'])) {
            //throw new Exception('You must specify either an address or a $PayByName tag, but not both!');
            systemexception('<b>[30001CP]</b>:We are upgrading the system!, Please refresh in sometime','You must specify either an address or a $PayByName tag, but not both!',false);
        }
		if($fields['currency']=='USDT'){
			$fields['currency']='USDT.ERC20';
		}
        return $this->request_handler->execute('create_withdrawal', $fields);
    }

    /**
     * function CreateMassWithdrawal
     * Execute multiple withdrawals with one command.
     * The withdrawals are passed in an associative array of $withdrawals, each having it's own array of fields from the
     * CreateWithdrawal function except auto_confirm which is always 1 in mass withdrawals. The key of each withdrawal
     * is used to return the result (same as CreateWithdrawal again.) The key can contain ONLY a-z, A-Z, and 0-9.
     * Withdrawals with empty keys or containing other characters will be silently ignored.
     *
     * @param array $withdrawals The associative array of withdrawals.
     *
     * @return array|object
     * Successful result is an associative array with matching keys from the given $withdrawals parameter.
     * Each withdrawal key is associated with an array containing the following values.
     *      - id (string)
     *      - status (integer)
     *      - amount (string)
     *
     * @throws Exception
     */
    public function CreateMassWithdrawal($withdrawals)
    {
        $fields = [
            'wd' => $withdrawals
        ];
        return $this->request_handler->execute('create_mass_withdrawal', $fields);
    }

    /**
     * function ConvertCoins
     * Convert a given amount of one currency to a different currency.
     *
     * @param int $amount The amount of the $from currency to convert.
     * @param string $from The ticker of the currency to convert from.
     * @param string $to The ticker of the currency to conver to.
     * @param string $address The address to send funds to. If blank or not included the coins will go to your CoinPayments Wallet.
     * @param string $dest_tag The destination tag to use for the withdrawal (for Ripple.) If 'address' is not included this has no effect.
     *
     * @return array|object Contains only one value, the ID of the conversion (string).
     *
     * @throws Exception
     */
    public function ConvertCoins($amount, $from, $to, $address = null, $dest_tag = null)
    {
        $fields = [
            'amount' => $amount,
            'from' => $from,
            'to' => $to
        ];

        if (!is_null($address)) {
            $fields['address'] = $address;
        }

        if (!is_null($dest_tag) && !is_null($address)) {
            $fields['dest_tag'] = $dest_tag;
        }
        return $this->request_handler->execute('convert', $fields);
    }

    /**
     * function GetConversionLimits
     * Check the amount limits for conversions between two currencies.
     * Notes:
     *      - A 'max' value of 0.00000000 is valid and means there is no known upper limit available.
     *      - Due to provider fluctuation limits do vary often.
     *
     *
     * @param string $from The ticker of the currency to convert from.
     * @param string $to The ticker of the currency to convert to.
     *
     * @return array|object
     * Successful result includes the following values.
     *      - min (string)
     *      - max (string)
     *
     * @throws Exception
     */
    public function GetConversionLimits($from, $to)
    {
        $fields = [
            'from' => $from,
            'to' => $to
        ];
        return $this->request_handler->execute('convert_limits', $fields);
    }

    /**
     * function GetWithdrawalHistory
     * Retrieve historical information for multiple withdrawals.
     *
     * @param int $limit The maximum number of withdrawals to return from 1-100.
     * @param int $start The withdrawal number to start from.
     * @param int $newer Return withdrawals submitted at the given Unix timestamp or later.
     *
     * @return array|object
     * Successful result includes the following values for each withdrawal.
     *      - id (string)
     *      - time_created (integer)
     *      - status (integer)
     *      - status_text (string)
     *      - coin (string)
     *      - amount (integer)
     *      - amountf (string)
     *      - send_address (string)
     *      - send_dest_tag (string|integer) Applicable coins only.
     *      - send_txid (string)
     *
     *
     * @throws Exception
     */
    public function GetWithdrawalHistory($limit = 25, $start = 0, $newer = 0)
    {
        $fields = [
            'limit' => $limit,
            'start' => $start,
            'newer' => $newer
        ];
        return $this->request_handler->execute('get_withdrawal_history', $fields);
    }

    /**
     * function GetWithdrawalInformation
     * Retrieve information for a single withdrawal.
     *
     * @param string $id The withdrawal ID to query.
     *
     * @return array|object
     * Successful result includes the following values:
     *      - time_created (integer)
     *      - status (integer)
     *      - status_text (string)
     *      - coin (string)
     *      - amount (integer)
     *      - amountf (string)
     *      - send_address (string)
     *      - send_txid (string)
     *
     * @throws Exception
     */
    public function GetWithdrawalInformation($id)
    {
        $fields = [
            'id' => $id
        ];
        return $this->request_handler->execute('get_withdrawal_info', $fields);
    }

    /**
     * function GetConversionInformation
     * Retrieve information for a currency conversion.
     *
     * @param string $id The conversion ID to query.
     *
     * @return array|object
     * Successful result includes the following values:
     *      - time_created
     *      - status
     *      - status_text
     *      - coin1
     *      - coin2
     *      - amount_sent
     *      - amount_sentf
     *      - received
     *      - receivedf
     *
     * @throws Exception
     */
    public function GetConversionInformation($id)
    {
        $fields = [
            'id' => $id
        ];
        return $this->request_handler->execute('get_conversion_info', $fields);
    }

    /** ------------------------------------------------ **/
    /** ------------------ $PayByName ------------------ **/
    /** ------------------------------------------------ **/

    /**
     * function GetProfileInformation
     * Retrieve information on a given $PayByName tag.
     *
     * @param string $pbntag The $PayByName tag to lookup, with or without a $ at the beginning.
     *
     * @return array|object
     * Successful result includes the following values:
     *      - pbntag (string)
     *      - merchant (string)
     *      - profile_name (string)
     *      - profile_url (string)
     *      - profile_email (string)
     *      - profile_image (string)
     *      - member_since (integer)
     *      - feedback (array)
     *          - pos (integer)
     *          - neg (integer)
     *          - neut (integer)
     *          - total (integer)
     *          - percent (string)
     *          - percent_str (string)
     *
     * @throws Exception
     */
    public function GetProfileInformation($pbntag)
    {
        $fields = [
            'pbntag' => $pbntag
        ];
        return $this->request_handler->execute('get_pbn_info', $fields);
    }

    /**
     * function GetTagList
     * Retrieve a list of $PayByName tags and tag IDs associated with your account (claimed & unclaimed).
     *
     * @return array|object
     * Successful result includes an array for each tag with the following values:
     *      - tagid (string)
     *      - pbntag (string)
     *      - time_expires (integer)
     *
     * @throws Exception
     */
    public function GetTagList()
    {
        return $this->request_handler->execute('get_pbn_list');
    }

    /**
     * function UpdateTagProfile
     * Use to update profile information associated with a $PayByName tag.
     *
     * @param int $tagid The tag ID of the profile you wish to update. Use GetTagList function to see tag list with IDs.
     * @param string $name A new name for the profile.
     * @param string $email A new email for the profile.
     * @param string $url A new website URL for the profile.
     * @param string $image HTTP POST with a JPG or PNG image 250KB or smaller.
     *                      This is an actual "multipart/form-data" file POST and not a URL to a file.
     *
     * @return array|object
     * Successful result includes only an "error" equal to "ok" value.
     * The result array inside the response will be empty regardless of success.
     *
     * @throws Exception
     */
    public function UpdateTagProfile($tagid, $name, $email, $url, $image)
    {
        $fields = [
            'tagid' => $tagid,
            'name' => $name,
            'email' => $email,
            'url' => $url,
            'image' => $image
        ];

        return $this->request_handler->execute('update_pbn_tag', $fields);

    }

    /**
     * function ClaimPayByNameTag
     * Used to claim a $PayByName tag for which a slot has been purchased but the tag itself not yet claimed.
     *
     * @param int $tagid The tag ID of the profile you wish to update. Use GetTagList function to see tag list with IDs.
     * @param string $name The name for the tag. For example a value of 'Apple' would be the PayByName tag $Apple.
     *                     Note this value is case sensitive and the casing you enter here will be the final casing for the tag.
     *
     * @return array|object
     * Successful result includes only an "error" equal to "ok" value.
     *
     * @throws Exception
     */
    public function ClaimPayByNameTag($tagid, $name)
    {
        $fields = [
            'tagid' => $tagid,
            'name' => $name
        ];

        return $this->request_handler->execute('claim_pbn_tag', $fields);
    }
}
class CoinpaymentsCurlRequest
{
    private $private_key = '';
    private $public_key = '';
    private $format = 'json';
    private $curl_handle;

    public function __construct($private_key, $public_key, $format)
    {
        $this->private_key = $private_key;
        $this->public_key = $public_key;
        $this->format = $format;
        $this->curl_handle = null;
    }

    public function __destruct() {
        if ($this->curl_handle !== null) {
            // Close the cURL session
            curl_close($this->curl_handle);
        }
    }

    /**
     * Executes a cURL request to the CoinPayments.net API.
     *
     * @param string $command The API command to call.
     * @param array $fields The required and optional fields to pass with the command.
     *
     * @return array Result data with error message. Error will equal "ok" if call was a success.
     *
     * @throws Exception If an invalid format is passed.
     */
    public function execute($command, array $fields = [])
    {
        // Define the API url
        $api_url = 'https://www.coinpayments.net/api.php';

        // Validate the passed fields
        $validator = new CoinpaymentsValidator($command, $fields);
        $validate_fields = $validator->validate();
        if (strpos($validate_fields, 'Error') !== FALSE) {
            echo $validate_fields;
            exit();
        } else {
            // Set default field values
            $fields['version'] = 1;
            $fields['format'] = $this->format;
            $fields['key'] = $this->public_key;
            $fields['cmd'] = $command;

            // Throw an error if the format is not equal to json or xml
            if ($fields['format'] != 'json' && $fields['format'] != 'xml') {
                return ['error' => 'Invalid response format passed. Please use "json" or "xml" as a format value'];
            }

            // Generate query string from fields
            $post_fields = http_build_query($fields, '', '&');

            // Generate the HMAC hash from the query string and private key
            $hmac = hash_hmac('sha512', $post_fields, $this->private_key);

            // Check the cURL handle has not already been initiated
            if ($this->curl_handle === null) {

                // Initiate the cURL handle and set initial options
                $this->curl_handle = curl_init($api_url);
                curl_setopt($this->curl_handle, CURLOPT_FAILONERROR, TRUE);
                curl_setopt($this->curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($this->curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($this->curl_handle, CURLOPT_POST, TRUE);
            }

            // Set HMAC header for cURL
            curl_setopt($this->curl_handle, CURLOPT_HTTPHEADER, array('HMAC:' . $hmac));

            // Set HTTP POST fields for cURL
            curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $post_fields);

            // Execute the cURL session
            $response = curl_exec($this->curl_handle);

            // Check the response of the cURL session
            if ($response !== FALSE) {
                $result = false;

                // Check the requested format
                if ($fields['format'] == 'json') {

                    // Prepare json result
                    if (PHP_INT_SIZE < 8 && version_compare(PHP_VERSION, '5.4.0') >= 0) {
                        // We are on 32-bit PHP, so use the bigint as string option.
                        // If you are using any API calls with Satoshis it is highly NOT recommended to use 32-bit PHP
                        $decoded = json_decode($response, TRUE, 512, JSON_BIGINT_AS_STRING);
                    } else {
                        $decoded = json_decode($response, TRUE);
                    }

                    // Check the json decoding and set an error in the result if it failed
                    if ($decoded !== NULL && count($decoded)) {
                        $result = $decoded;
                    } else {
                        $result = ['error' => 'Unable to parse JSON result (' . json_last_error() . ')'];
                    }
                } else {
                    // Set the result to the response
                    $result = $response;
                }
            } else {
                // Throw an error if the response of the cURL session is false
                $result = ['error' => 'cURL error: ' . curl_error($this->curl_handle)];
            }

            return $result;
        }
    }
}

class CoinpaymentsValidator
{

    protected $command;
    protected $fields;
    /**
     * @var array $commands_reference An array of the optional and required fields
     * for each API command, including their value's types, specific permitted values
     * and any mutually exclusive rules.
     */
    protected $commands_reference = [
        'get_basic_info' => [],
        'rates' => [
            'optional' => [
                'accepted' => [
                    'type' => 'integer',
                    'permitted' => [0, 1]
                ],
                'short' => [
                    'type' => 'integer',
                    'permitted' => [0, 1]
                ]
            ]
        ],
        'balances' => [
            'optional' => [
                'all' => [
                    'type' => 'integer',
                    'permitted' => [0, 1]
                ]
            ]
        ],
        'get_deposit_address' => [
            'required' => [
                'currency' => [
                    'type' => 'string'
                ]
            ]
        ],
        'create_transaction' => [
            'required' => [
                'amount' => [
                    'type' => 'integer'
                ],
                'currency1' => [
                    'type' => 'string'
                ],
                'currency2' => [
                    'type' => 'string'
                ],
                'buyer_email' => [
                    'type' => 'email'
                ]
            ],
            'optional' => [
                'address' => [
                    'type' => 'string'
                ],
                'buyer_name' => [
                    'type' => 'string'
                ],
                'item_name' => [
                    'type' => 'string'
                ],
                'item_number' => [
                    'type' => 'string'
                ],
                'invoice' => [
                    'type' => 'string'
                ],
                'custom' => [
                    'type' => 'string'
                ],
                'ipn_url' => [
                    'type' => 'url'
                ],
            ]
        ],
        'get_callback_address' => [
            'required' => [
                'currency' => [
                    'type' => 'string'
                ]
            ],
            'optional' => [
                'ipn_url' => [
                    'type' => 'url'
                ]
            ]
        ],
        'get_tx_info' => [
            'required' => [
                'txid' => [
                    'type' => 'string'
                ]
            ],
            'optional' => [
                'full' => [
                    'type' => 'integer',
                    'permitted' => [0, 1]
                ]
            ]
        ],
        'get_tx_info_multi' => [
            'required' => [
                'txid' => [
                    'type' => 'string'
                ]
            ]
        ],
        'get_tx_ids' => [
            'optional' => [
                'limit' => [
                    'type' => 'integer'
                ],
                'start' => [
                    'type' => 'integer'
                ],
                'newer' => [
                    'type' => 'integer'
                ],
                'all' => [
                    'type' => 'integer',
                    'permitted' => [0, 1]
                ]
            ]
        ],
        'create_transfer' => [
            'required' => [
                'amount' => [
                    'type' => 'integer'
                ],
                'currency' => [
                    'type' => 'string'
                ]
            ],
            'one_of' => [
                'merchant' => [
                    'type' => 'string'
                ],
                'pbntag' => [
                    'type' => 'string'
                ]
            ],
            'optional' => [
                'auto_confirm' => [
                    'type' => 'integer',
                    'permitted' => [0, 1]
                ]
            ]
        ],
        'create_withdrawal' => [
            'required' => [
                'amount' => [
                    'type' => 'integer'
                ],
                'currency' => [
                    'type' => 'string'
                ]
            ],
            'one_of' => [
                'address' => [
                    'type' => 'string'
                ],
                'pbntag' => [
                    'type' => 'string'
                ]
            ],
            'optional' => [
                'add_tx_fee' => [
                    'type' => 'integer',
                    'permitted' => [0, 1]
                ],
                'currency2' => [
                    'type' => 'string'
                ],
                'dest_tag' => [
                    'type' => 'string'
                ],
                'ipn_url' => [
                    'type' => 'url'
                ],
                'auto_confirm' => [
                    'type' => 'integer',
                    'permitted' => [0, 1]
                ],
                'note' => [
                    'type' => 'string'
                ]
            ]
        ],
        'create_mass_withdrawal' => [
            'required' => [
                'wd' => [
                    'type' => 'array'
                ],
            ]
        ],
        'convert' => [
            'required' => [
                'amount' => [
                    'type' => 'integer'
                ],
                'from' => [
                    'type' => 'string'
                ],
                'to' => [
                    'type' => 'string'
                ]
            ],
            'optional' => [
                'address' => [
                    'type' => 'string'
                ],
                'dest_tag' => [
                    'type' => 'string'
                ]
            ]
        ],
        'convert_limits' => [
            'required' => [
                'from' => [
                    'type' => 'string'
                ],
                'to' => [
                    'type' => 'string'
                ]
            ]
        ],
        'get_withdrawal_history' => [
            'optional' => [
                'limit' => [
                    'type' => 'integer'
                ],
                'start' => [
                    'type' => 'integer'
                ],
                'newer' => [
                    'type' => 'integer'
                ]
            ]
        ],
        'get_withdrawal_info' => [
            'required' => [
                'id' => [
                    'type' => 'string'
                ]
            ]
        ],
        'get_conversion_info' => [
            'required' => [
                'id' => [
                    'type' => 'string'
                ]
            ]
        ],
        'get_pbn_info' => [
            'required' => [
                'pbntag' => [
                    'type' => 'string'
                ]
            ]
        ],
        'get_pbn_list' => [],
        'update_pbn_tag' => [
            'required' => [
                'tagid' => [
                    'type' => 'string'
                ]
            ],
            'optional' => [
                'name' => [
                    'type' => 'string'
                ],
                'email' => [
                    'type' => 'email'
                ],
                'url' => [
                    'type' => 'url'
                ],
                'image' => [
                    'type' => 'string'
                ]
            ]
        ],
        'claim_pbn_tag' => [
            'required' => [
                'tagid' => [
                    'type' => 'string'
                ],
                'name' => [
                    'type' => 'string'
                ]
            ]
        ],
    ];

    public function __construct($command, $fields)
    {
        $this->command = $command;
        $this->fields = $fields;
    }

    /**
     * function validate
     *
     * The actual validation function run before the curl execute method.
     *
     * @return bool|string
     * @throws Exception
     */
    public function validate()
    {
        $valid_command = $this->validateCommand();
        try {
            if ($valid_command === TRUE) {
                $valid_fields = $this->validateFields();
                try {
                    if ($valid_fields === TRUE) {
                        return TRUE;
                    } else {
                        //throw new Exception($valid_fields);
                        systemexception('<b>[30002CP]</b>:We are upgrading the system! , Please refresh in sometime','valid fields!',false);
                    }
                } catch (Exception $e) {
                    return 'Error: ' . $e->getMessage();
                }
            } else {
                systemexception('<b>[30003CP]</b>:We are upgrading the system! , Please refresh in sometime','Invalid command name!!',false);
                //throw new Exception('Invalid command name!');
            }
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * function validateCommand
     *
     * Validates the name of the command.
     *
     * @return bool|string
     * @throws Exception
     */
    private function validateCommand()
    {
        try {
            if (array_key_exists($this->command, $this->commands_reference)) {
                return TRUE;
            }
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * function validateFields
     *
     * Validates the existence of fields, respective to their field groups.
     *
     * @return bool True on success of validated fields.
     * @throws Exception
     */
    private function validateFields()
    {
        // Set top level field groups
        $field_groups = ['required', 'one_of', 'optional'];

        // Compile array of accepted fields
        $accepted_fields = [];
        foreach ($field_groups as $field_group) {

            // Check if the field group exists for the command
            if (array_key_exists($field_group, $this->commands_reference[$this->command])) {

                // For each field group that exists, add the fields within to the accepted fields array
                foreach ($this->commands_reference[$this->command][$field_group] as $field_key => $field_value) {
                    $accepted_fields[] = $field_key;
                }
            }
        }

        // Validate all passed fields are valid
        foreach ($this->fields as $field_key => $field_value) {

            // Throw an error if an invalid field was passed
            if (!in_array($field_key, $accepted_fields)) {
                //throw new Exception('The field "' . $field_key . '" was passed but is not a valid field for the "' . $this->command . '" command!');
                systemexception('<b>[30003CP]</b>:We are upgrading the system! , Please refresh in sometime','The field "' . $field_key . '" was passed but is not a valid field for the "' . $this->command . '" command!',false);
            }
        }

        // Check required fields
        if (array_key_exists('required', $this->commands_reference[$this->command])) {
            foreach ($this->commands_reference[$this->command]['required'] as $required_field_key => $required_field_value) {
                if (!array_key_exists($required_field_key, $this->fields)) {
                    //throw new Exception('The required field "' . $required_field_key . '" was not passed!');
                    systemexception('<b>[30004CP]</b>:We are upgrading the system! , Please refresh in sometime','The required field "' . $required_field_key . '" was not passed!',false);
                } else {
                    $field_type = $this->commands_reference[$this->command]['required'][$required_field_key]['type'];
                    $is_valid_type = $this->validateFieldType($required_field_key, $this->fields[$required_field_key], $field_type, 'required');
                    if (!$is_valid_type) {
                        //throw new Exception($is_valid_type);
                        systemexception('<b>[30003CP]</b>:We are upgrading the system! , Please refresh in sometime','is valid type!',false);
                    }
                }
            }
        }

        // Check one_of (mutually exclusive) fields
        if (array_key_exists('one_of', $this->commands_reference[$this->command])) {
            $count_one_of = 0;
            $expected_one_of = [];
            $expected_one_of_message = '';
            $one_of_field_key = '';
            $passed_one_of_key = '';

            // Compile an array of all passed fields in the one_of group
            foreach ($this->commands_reference[$this->command]['one_of'] as $one_of_field_key => $one_of_field_value) {
                $expected_one_of[] = $one_of_field_key;
                if (array_key_exists($one_of_field_key, $this->fields)) {
                    $count_one_of++;
                    $passed_one_of_key = $one_of_field_key;
                }
            }

            // Set a message defining the possible one_of fields, if there was more or less than one passed
            if ($count_one_of != 1) {
                $expected_one_of_message = implode(' | ', $expected_one_of);
            }

            // Throw an error if less than or more than 1 field was passed
            if ($count_one_of < 1) {
                //throw new Exception('At least one of the following fields must be passed: [ ' . $expected_one_of_message . ' ]');
                systemexception('<b>[30006CP]</b>:We are upgrading the system! , Please refresh in sometime','At least one of the following fields must be passed: [ ' . $expected_one_of_message . ' ]',false);
            } elseif ($count_one_of > 1) {
                systemexception('<b>[30007CP]</b>:We are upgrading the system! , Please refresh in sometime','No more than one of the following fields can be passed: [ ' . $expected_one_of_message . ' ]',false);
                //throw new Exception('No more than one of the following fields can be passed: [ ' . $expected_one_of_message . ' ]');
            } else {
                $field_type = $this->commands_reference[$this->command]['one_of'][$one_of_field_key]['type'];
                $is_valid_type = $this->validateFieldType($one_of_field_key, $this->fields[$passed_one_of_key], $field_type, 'one_of');
                if (!$is_valid_type) {
                    //throw new Exception($is_valid_type);
                    systemexception('<b>[30008CP]</b>:We are upgrading the system! , Please refresh in sometime','is valid type2',false);
                }
            }
        }

        // Check if optional fields exist
        if (array_key_exists('optional', $this->commands_reference[$this->command])) {
            $optional_fields_to_check = [];

            // Build array of the optional fields passed
            foreach ($this->fields as $field_key => $field_value) {
                if (array_key_exists($field_key, $this->commands_reference[$this->command]['optional'])) {
                    $optional_fields_to_check[] = $field_key;
                }
            }

            // Loop through optional fields passed and validate their value's type
            foreach ($optional_fields_to_check as $optional_field) {
                $field_type = $this->commands_reference[$this->command]['optional'][$optional_field]['type'];
                $is_valid_type = $this->validateFieldType($optional_field, $this->fields[$optional_field], $field_type, 'optional');
                if (!$is_valid_type) {
                    //throw new Exception($is_valid_type);
                    systemexception('<b>[30009CP]</b>:We are upgrading the system! , Please refresh in sometime','is valid type3',false);
                }
            }
        }
        return TRUE;
    }

    /**
     * function validateFieldType
     *
     * Validates the data type for a field's passed value.
     *
     * @param string $field_key The name/key for the field.
     * @param mixed $field_value The value for the field being checked.
     * @param string $expected_type The expected type from the $commands_reference.
     * @param string $field_group The group the field belongs to within the $commands_reference.
     * @return bool True if field value's type is valid.
     * @throws Exception
     */
    private function validateFieldType($field_key, $field_value, $expected_type, $field_group)
    {
        $actual_type = FALSE;
        switch ($expected_type) {
            case 'string':
                if (!is_string($field_value)) {
                    $actual_type = gettype($field_value);
                }
                break;
            case 'integer':
                if (!is_numeric($field_value)) {
                    $actual_type = gettype($field_value);
                }
                break;
            case 'url':
                if (filter_var($field_value, FILTER_VALIDATE_URL) === FALSE) {
                    $actual_type = gettype($field_value);
                }
                break;
            case 'email':
                if (filter_var($field_value, FILTER_VALIDATE_EMAIL) === FALSE) {
                    $actual_type = gettype($field_value);
                }
                break;
            case 'array':
                if (!is_array($field_value)) {
                    $actual_type = gettype($field_value);
                }
                break;
            default:
                systemexception('<b>[30009CP]</b>:We are upgrading the system! , Please refresh in sometime','Expected type "' . $expected_type . '" is not valid for the given command',false);
                //throw new Exception('Expected type "' . $expected_type . '" is not valid for the given command.');

        }

        if (is_array($field_value)) {
            $field_value = '[ArrayData]';
        }

        if ($actual_type != FALSE) {
            //throw new Exception('Field "' . $field_key . '" passed with value of "' . $field_value . '" and data type of "' . $actual_type . '", but expected type is "' . $expected_type . '".');
            systemexception('<b>[30009CP]</b>:We are upgrading the system! , Please refresh in sometime','Field "' . $field_key . '" passed with value of "' . $field_value . '" and data type of "' . $actual_type . '", but expected type is "' . $expected_type . '".',false);
        }

        if (array_key_exists('permitted', $this->commands_reference[$this->command][$field_group][$field_key])) {
            $permitted_check = $this->validateFieldPermittedValue($field_value, $this->commands_reference[$this->command][$field_group][$field_key]['permitted']);
            if (!$permitted_check) {
                $permitted_message = implode(' | ', $this->commands_reference[$this->command][$field_group][$field_key]['permitted']);
                systemexception('<b>[30009CP]</b>:We are upgrading the system! , Please refresh in sometime','Permitted values for the field "' . $field_key . '" are [ ' . $permitted_message . ' ] but the value passed was: ' . $field_value,false);
                //throw new Exception('Permitted values for the field "' . $field_key . '" are [ ' . $permitted_message . ' ] but the value passed was: ' . $field_value);
            }
        }
        return TRUE;
    }

    /**
     * function validateFieldPermittedValue
     *
     * Validates the passed field value is in a permitted range of values.
     *
     * @param mixed $field_value The value of the field to check against permitted values.
     * @param array $permitted An array containing the permitted values.
     * @return bool True if field value is in permitted values array, false if it's not.
     */
    private function validateFieldPermittedValue($field_value, $permitted)
    {
        // Check the passed field value is within the permitted values
        if (!in_array($field_value, $permitted)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
}
