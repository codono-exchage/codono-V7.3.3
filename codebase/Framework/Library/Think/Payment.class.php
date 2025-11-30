<?php

namespace Think;
vendor("vendor.autoload");

use Omnipay\Omnipay;
use Omnipay\AuthorizeNet;

class Payment
{
	public function authorize($name,$amount){
		$input=$_POST;
        $gateway=Omnipay::create('AuthorizeNet_AIM');
		$request = $gateway->purchase(
    [
        'notifyUrl' => SITE_URL.'Content/save',
        'amount' => $amount,
        'opaqueDataDescriptor' => $input['dataDescriptor'],
        'opaqueDataValue' => $input['dataValue'],
    ])->send();
	if ($request->isSuccessful()) {

    // Payment was successful
    return json_encode($request);

} elseif ($request->isRedirect()) {

    // Redirect to offsite payment gateway
   return $request->redirect();

} else {
    // Payment failed
    return $request->getMessage();
}
	
		
	}
    

}
